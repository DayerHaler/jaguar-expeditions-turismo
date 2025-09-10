<?php
/**
 * PROCESADOR DE PAGOS - MÚLTIPLES PASARELAS
 * =========================================
 * 
 * Sistema unificado para procesar pagos con Stripe, PayPal y MercadoPago
 */

require_once '../config/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON(false, 'Método no permitido');
}

// Obtener datos del formulario
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    respuestaJSON(false, 'Datos inválidos');
}

// Validar datos obligatorios
$reservaId = $data['reserva_id'] ?? '';
$metodoPago = $data['metodo_pago'] ?? '';
$monto = $data['monto'] ?? 0;

if (empty($reservaId) || empty($metodoPago) || $monto <= 0) {
    respuestaJSON(false, 'Datos obligatorios faltantes');
}

try {
    $db = getDB();
    
    // Verificar que la reserva existe y está pendiente de pago
    $stmt = $db->prepare("
        SELECT r.*, t.nombre as tour_nombre 
        FROM reservas r 
        JOIN tours t ON r.tour_id = t.id 
        WHERE r.id = ? AND r.estado_pago = 'Pendiente'
    ");
    $stmt->execute([$reservaId]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        respuestaJSON(false, 'Reserva no encontrada o ya ha sido pagada');
    }
    
    // Procesar pago según el método seleccionado
    switch ($metodoPago) {
        case 'stripe':
            $resultado = procesarPagoStripe($reserva, $data);
            break;
            
        case 'paypal':
            $resultado = procesarPagoPayPal($reserva, $data);
            break;
            
        case 'mercadopago':
            $resultado = procesarPagoMercadoPago($reserva, $data);
            break;
            
        default:
            respuestaJSON(false, 'Método de pago no soportado');
    }
    
    // Guardar el resultado del pago
    if ($resultado['success']) {
        registrarPago($db, $reserva, $metodoPago, $resultado);
        respuestaJSON(true, 'Pago procesado correctamente', $resultado['data']);
    } else {
        logError("Error en pago: " . json_encode($resultado));
        respuestaJSON(false, $resultado['message']);
    }
    
} catch (Exception $e) {
    logError("Error en procesamiento de pago: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}

/**
 * Procesar pago con Stripe
 */
function procesarPagoStripe($reserva, $data) {
    try {
        // Aquí necesitarás instalar la librería de Stripe
        // composer require stripe/stripe-php
        
        require_once '../vendor/stripe/stripe-php/init.php';
        
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $reserva['total'] * 100, // Stripe usa centavos
            'currency' => strtolower($reserva['moneda']),
            'payment_method' => $data['payment_method_id'],
            'confirmation_method' => 'manual',
            'confirm' => true,
            'description' => "Reserva {$reserva['codigo_reserva']} - {$reserva['tour_nombre']}",
            'metadata' => [
                'reserva_id' => $reserva['id'],
                'codigo_reserva' => $reserva['codigo_reserva'],
                'tour_nombre' => $reserva['tour_nombre']
            ]
        ]);
        
        if ($paymentIntent->status === 'succeeded') {
            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'transaction_id' => $paymentIntent->charges->data[0]->id,
                'data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Pago no completado',
                'data' => ['status' => $paymentIntent->status]
            ];
        }
        
    } catch (\Stripe\Exception\CardException $e) {
        return [
            'success' => false,
            'message' => 'Tarjeta rechazada: ' . $e->getError()->message
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error en el procesamiento: ' . $e->getMessage()
        ];
    }
}

/**
 * Procesar pago con PayPal
 */
function procesarPagoPayPal($reserva, $data) {
    try {
        // Para PayPal, típicamente trabajarías con el SDK de PayPal
        // Este es un ejemplo simplificado
        
        $paypalUrl = PAYPAL_MODE === 'sandbox' 
            ? 'https://api.sandbox.paypal.com' 
            : 'https://api.paypal.com';
        
        // Obtener token de acceso
        $tokenResponse = obtenerTokenPayPal();
        
        if (!$tokenResponse['success']) {
            return $tokenResponse;
        }
        
        $accessToken = $tokenResponse['token'];
        
        // Crear pago
        $paymentData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $reserva['moneda'],
                    'value' => number_format($reserva['total'], 2, '.', '')
                ],
                'description' => "Reserva {$reserva['codigo_reserva']} - {$reserva['tour_nombre']}"
            ]]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paypalUrl . '/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 201 && isset($responseData['id'])) {
            return [
                'success' => true,
                'payment_id' => $responseData['id'],
                'data' => [
                    'order_id' => $responseData['id'],
                    'approval_url' => $responseData['links'][1]['href'] ?? ''
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear el pago en PayPal'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error en PayPal: ' . $e->getMessage()
        ];
    }
}

/**
 * Procesar pago con MercadoPago
 */
function procesarPagoMercadoPago($reserva, $data) {
    try {
        // Para MercadoPago necesitarías el SDK
        // composer require mercadopago/dx-php
        
        require_once '../vendor/mercadopago/dx-php/src/MercadoPago.php';
        
        MercadoPago\SDK::setAccessToken(MERCADOPAGO_ACCESS_TOKEN);
        
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = (float)$reserva['total'];
        $payment->token = $data['token'];
        $payment->description = "Reserva {$reserva['codigo_reserva']} - {$reserva['tour_nombre']}";
        $payment->installments = 1;
        $payment->payment_method_id = $data['payment_method_id'];
        $payment->issuer_id = $data['issuer_id'];
        
        $payment->payer = array(
            "email" => $reserva['cliente_email']
        );
        
        $payment->save();
        
        if ($payment->status === 'approved') {
            return [
                'success' => true,
                'payment_id' => $payment->id,
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => $payment->status
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Pago no aprobado: ' . $payment->status_detail,
                'data' => ['status' => $payment->status]
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error en MercadoPago: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener token de acceso de PayPal
 */
function obtenerTokenPayPal() {
    $paypalUrl = PAYPAL_MODE === 'sandbox' 
        ? 'https://api.sandbox.paypal.com' 
        : 'https://api.paypal.com';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $paypalUrl . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return [
            'success' => true,
            'token' => $data['access_token']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al obtener token de PayPal'
        ];
    }
}

/**
 * Registrar pago en la base de datos
 */
function registrarPago($db, $reserva, $metodoPago, $resultado) {
    try {
        $db->beginTransaction();
        
        // Insertar registro de pago
        $codigoTransaccion = generarCodigoTransaccion();
        
        $sql = "INSERT INTO pagos (
            reserva_id, codigo_transaccion, metodo_pago, monto, moneda,
            stripe_payment_intent_id, paypal_payment_id, mercadopago_payment_id,
            estado, descripcion, datos_pago, respuesta_gateway, fecha_procesado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $reserva['id'],
            $codigoTransaccion,
            ucfirst($metodoPago),
            $reserva['total'],
            $reserva['moneda'],
            $metodoPago === 'stripe' ? $resultado['payment_intent_id'] ?? null : null,
            $metodoPago === 'paypal' ? $resultado['payment_id'] ?? null : null,
            $metodoPago === 'mercadopago' ? $resultado['payment_id'] ?? null : null,
            'Exitoso',
            "Pago de reserva {$reserva['codigo_reserva']}",
            json_encode($resultado['data']),
            json_encode($resultado),
        ]);
        
        // Actualizar estado de la reserva
        $stmtReserva = $db->prepare("
            UPDATE reservas 
            SET estado_pago = 'Pagado', estado_reserva = 'Confirmada'
            WHERE id = ?
        ");
        $stmtReserva->execute([$reserva['id']]);
        
        $db->commit();
        
        // Enviar email de confirmación
        enviarEmailConfirmacionPago($reserva, $codigoTransaccion);
        
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Enviar email de confirmación de pago
 */
function enviarEmailConfirmacionPago($reserva, $codigoTransaccion) {
    $asunto = "Confirmación de pago - Reserva {$reserva['codigo_reserva']}";
    
    $mensaje = "
    <h2>¡Pago confirmado!</h2>
    <p>Estimado/a {$reserva['cliente_nombre']},</p>
    
    <p>Hemos recibido tu pago correctamente. Tu reserva está confirmada.</p>
    
    <h3>Detalles de la reserva:</h3>
    <ul>
        <li><strong>Código de reserva:</strong> {$reserva['codigo_reserva']}</li>
        <li><strong>Tour:</strong> {$reserva['tour_nombre']}</li>
        <li><strong>Fecha:</strong> {$reserva['fecha_tour']}</li>
        <li><strong>Personas:</strong> {$reserva['total_personas']}</li>
        <li><strong>Total pagado:</strong> " . formatearPrecio($reserva['total'], $reserva['moneda']) . "</li>
        <li><strong>Código de transacción:</strong> {$codigoTransaccion}</li>
    </ul>
    
    <p>Te contactaremos 24 horas antes del tour para confirmar detalles y punto de encuentro.</p>
    
    <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
    
    <p>¡Esperamos verte pronto en esta increíble aventura!</p>
    
    <p>Saludos cordiales,<br>Equipo de " . EMPRESA_NOMBRE . "</p>
    ";
    
    enviarEmail($reserva['cliente_email'], $asunto, $mensaje);
}
?>
