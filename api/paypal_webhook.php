<?php
/**
 * WEBHOOK RECEPTOR PAYPAL
 * Recibe notificaciones automáticas de PayPal cuando se completan pagos
 */

require_once '../config/config.php';
require_once 'PayPalConfig.php';

// Registrar todas las solicitudes para depuración
file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Webhook recibido\n", FILE_APPEND);

// Obtener datos del webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Registrar datos recibidos
file_put_contents('webhook_log.txt', "Datos: " . $input . "\n", FILE_APPEND);

// Verificar que se recibieron datos
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibieron datos válidos']);
    exit;
}

// Obtener headers para verificación
$headers = getallheaders();
file_put_contents('webhook_log.txt', "Headers: " . json_encode($headers) . "\n", FILE_APPEND);

/**
 * Verificar firma del webhook (opcional pero recomendado)
 */
function verifyWebhookSignature($data, $headers) {
    // Aquí puedes implementar verificación de firma si tienes configurado
    // Por ahora, aceptamos todos los webhooks
    return true;
}

/**
 * Procesar evento de pago
 */
function procesarPago($eventData) {
    try {
        $db = getDB();
        
        // Obtener información del pago
        $paymentId = $eventData['resource']['id'] ?? null;
        $status = $eventData['resource']['status'] ?? null;
        $amount = $eventData['resource']['amount']['total'] ?? null;
        $currency = $eventData['resource']['amount']['currency'] ?? null;
        
        file_put_contents('webhook_log.txt', "Procesando pago ID: $paymentId, Status: $status\n", FILE_APPEND);
        
        if ($status === 'COMPLETED' || $status === 'approved') {
            // Buscar la reserva en la base de datos
            $stmt = $db->prepare("
                SELECT * FROM reservas 
                WHERE paypal_payment_id = ? OR paypal_order_id = ?
            ");
            $stmt->execute([$paymentId, $paymentId]);
            $reserva = $stmt->fetch();
            
            if ($reserva) {
                // Actualizar estado de la reserva
                $updateStmt = $db->prepare("
                    UPDATE reservas 
                    SET estado_pago = 'completado',
                        estado_reserva = 'confirmada',
                        fecha_pago = NOW(),
                        webhook_verified = 1
                    WHERE id = ?
                ");
                $updateStmt->execute([$reserva['id']]);
                
                file_put_contents('webhook_log.txt', "Reserva ID {$reserva['id']} actualizada a confirmada\n", FILE_APPEND);
                
                // Enviar email de confirmación
                enviarEmailConfirmacion($reserva);
                
                return true;
            } else {
                file_put_contents('webhook_log.txt', "No se encontró reserva para payment ID: $paymentId\n", FILE_APPEND);
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        file_put_contents('webhook_log.txt', "Error procesando pago: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

/**
 * Enviar email de confirmación
 */
function enviarEmailConfirmacion($reserva) {
    try {
        // Aquí puedes integrar con tu sistema de emails
        file_put_contents('webhook_log.txt', "Email de confirmación enviado para reserva ID: {$reserva['id']}\n", FILE_APPEND);
        
        // Ejemplo básico con mail()
        $to = $reserva['email'];
        $subject = "Confirmación de Reserva - Jaguar Expeditions";
        $message = "
        Estimado/a {$reserva['nombre']},
        
        Su reserva ha sido confirmada exitosamente.
        
        Detalles:
        - ID Reserva: {$reserva['id']}
        - Tour: {$reserva['tour_nombre']}
        - Fecha: {$reserva['fecha_tour']}
        - Monto: {$reserva['monto_total']} USD
        
        Gracias por elegir Jaguar Expeditions.
        ";
        
        $headers = "From: no-reply@jaguarexpeditions.com\r\n";
        $headers .= "Reply-To: info@jaguarexpeditions.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($to, $subject, $message, $headers);
        
    } catch (Exception $e) {
        file_put_contents('webhook_log.txt', "Error enviando email: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// PROCESAR EL WEBHOOK
try {
    // Verificar firma (opcional)
    if (!verifyWebhookSignature($input, $headers)) {
        http_response_code(401);
        echo json_encode(['error' => 'Firma inválida']);
        exit;
    }
    
    // Procesar según el tipo de evento
    $eventType = $data['event_type'] ?? '';
    
    file_put_contents('webhook_log.txt', "Evento tipo: $eventType\n", FILE_APPEND);
    
    switch ($eventType) {
        case 'PAYMENT.CAPTURE.COMPLETED':
        case 'CHECKOUT.ORDER.APPROVED':
        case 'PAYMENT.PAYOUTS.SUCCESS':
            $success = procesarPago($data);
            break;
            
        default:
            file_put_contents('webhook_log.txt', "Evento no manejado: $eventType\n", FILE_APPEND);
            $success = true; // No error, solo no procesamos este tipo
    }
    
    // Responder a PayPal
    if ($success) {
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error']);
    }
    
} catch (Exception $e) {
    file_put_contents('webhook_log.txt', "Error general: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
