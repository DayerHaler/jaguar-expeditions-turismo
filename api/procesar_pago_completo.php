<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'jaguar_expeditions';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener datos JSON del request
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }

    // Validar datos obligatorios
    $required_fields = ['tour_id', 'fecha_tour', 'num_clientes', 'clientes', 'metodo_pago', 'total'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Campo obligatorio faltante: $field");
        }
    }

    // Comenzar transacción
    $pdo->beginTransaction();

    try {
        // 1. Crear la reserva principal
        $codigo_reserva = 'JE' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $cliente_principal = $input['clientes'][0];
        
        $stmt_reserva = $pdo->prepare("
            INSERT INTO reservas (
                codigo_reserva, tour_id, cliente_nombre, cliente_apellido, 
                cliente_email, cliente_telefono, cliente_pais, cliente_documento,
                fecha_tour, total_personas, precio_unitario, descuento, 
                subtotal, impuestos, total, estado_reserva, estado_pago
            ) VALUES (
                :codigo_reserva, :tour_id, :nombre, :apellido, 
                :email, :telefono, :pais, :documento,
                :fecha_tour, :total_personas, :precio_unitario, :descuento,
                :subtotal, :impuestos, :total, 'Confirmada', 'Pagado'
            )
        ");

        $stmt_reserva->execute([
            ':codigo_reserva' => $codigo_reserva,
            ':tour_id' => $input['tour_id'],
            ':nombre' => $cliente_principal['nombre'],
            ':apellido' => $cliente_principal['apellido'] ?? '',
            ':email' => $cliente_principal['email'] ?? '',
            ':telefono' => $cliente_principal['celular'] ?? '',
            ':pais' => $cliente_principal['pais'] ?? '',
            ':documento' => $cliente_principal['documento'] ?? '',
            ':fecha_tour' => $input['fecha_tour'],
            ':total_personas' => $input['num_clientes'],
            ':precio_unitario' => $input['subtotal'] / $input['num_clientes'],
            ':descuento' => $input['descuento'] ?? 0,
            ':subtotal' => $input['subtotal'],
            ':impuestos' => $input['impuestos'] ?? 0,
            ':total' => $input['total']
        ]);

        $reserva_id = $pdo->lastInsertId();

        // 2. Guardar acompañantes (si hay más de 1 cliente)
        if (count($input['clientes']) > 1) {
            $stmt_acompanante = $pdo->prepare("
                INSERT INTO acompanantes (
                    reserva_id, nombre, apellido, documento, tipo
                ) VALUES (
                    :reserva_id, :nombre, :apellido, :documento, :tipo
                )
            ");

            for ($i = 1; $i < count($input['clientes']); $i++) {
                $acompanante = $input['clientes'][$i];
                $stmt_acompanante->execute([
                    ':reserva_id' => $reserva_id,
                    ':nombre' => $acompanante['nombre'],
                    ':apellido' => $acompanante['apellido'] ?? '',
                    ':documento' => $acompanante['documento'] ?? '',
                    ':tipo' => 'Adulto' // Por defecto, se puede mejorar con edad
                ]);
            }
        }

        // 3. Registrar el pago en la tabla pagos
        $codigo_transaccion = strtoupper($input['metodo_pago']) . '-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
        
        // Preparar datos del pago según el método
        $datos_pago = [
            'codigo_transaccion' => $codigo_transaccion,
            'metodo_pago' => $input['metodo_pago'],
            'fecha_procesado' => date('Y-m-d H:i:s'),
            'monto' => $input['total'],
            'moneda' => 'USD'
        ];

        // Generar detalles específicos del método de pago
        switch ($input['metodo_pago']) {
            case 'tarjeta':
                $datos_pago['procesador'] = 'Stripe';
                $datos_pago['tarjeta_ultimos_4'] = '****' . mt_rand(1000, 9999);
                $datos_pago['tipo_tarjeta'] = 'Visa';
                $datos_pago['autorizacion'] = 'AUTH-' . strtoupper(substr(md5(time()), 0, 8));
                $datos_pago['referencia_banco'] = 'REF-' . strtoupper(substr(md5(time() . mt_rand()), 0, 10));
                break;
            case 'yape':
                $datos_pago['procesador'] = 'Yape';
                $datos_pago['telefono_origen'] = '+51 9' . str_repeat('*', 8);
                $datos_pago['operacion_yape'] = 'YP-' . strtoupper(substr(md5(time()), 0, 8));
                break;
            case 'plin':
                $datos_pago['procesador'] = 'Plin';
                $datos_pago['telefono_origen'] = '+51 9' . str_repeat('*', 8);
                $datos_pago['operacion_plin'] = 'PL-' . strtoupper(substr(md5(time()), 0, 8));
                break;
            case 'transferencia':
                $datos_pago['procesador'] = 'Banco';
                $datos_pago['banco_origen'] = 'BCP';
                $datos_pago['numero_operacion'] = 'OP-' . strtoupper(substr(md5(time()), 0, 10));
                $datos_pago['cuenta_destino'] = 'XXXX-XXXX-XXXX-1234';
                break;
        }

        $stmt_pago = $pdo->prepare("
            INSERT INTO pagos (
                reserva_id, codigo_transaccion, metodo_pago, 
                monto, moneda, estado, descripcion, 
                datos_pago, fecha_procesado
            ) VALUES (
                :reserva_id, :codigo_transaccion, :metodo_pago,
                :monto, :moneda, 'Exitoso', :descripcion,
                :datos_pago, NOW()
            )
        ");

        $stmt_pago->execute([
            ':reserva_id' => $reserva_id,
            ':codigo_transaccion' => $codigo_transaccion,
            ':metodo_pago' => ucfirst($input['metodo_pago']),
            ':monto' => $input['total'],
            ':moneda' => 'USD',
            ':descripcion' => 'Pago por reserva de tour - ' . ucfirst($input['metodo_pago']),
            ':datos_pago' => json_encode($datos_pago, JSON_UNESCAPED_UNICODE)
        ]);

        // Confirmar transacción
        $pdo->commit();

        // Respuesta exitosa con todos los detalles
        echo json_encode([
            'success' => true,
            'message' => 'Reserva y pago procesados exitosamente',
            'data' => [
                'reserva_id' => $reserva_id,
                'codigo_reserva' => $codigo_reserva,
                'codigo_transaccion' => $codigo_transaccion,
                'estado_reserva' => 'Confirmada',
                'estado_pago' => 'Pagado',
                'total' => $input['total'],
                'metodo_pago' => $input['metodo_pago'],
                'fecha_pago' => date('Y-m-d H:i:s'),
                'detalles_pago' => $datos_pago
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
