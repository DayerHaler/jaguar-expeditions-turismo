<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "jaguar_expeditions";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("No se recibieron datos válidos");
    }
    
    // Validar datos requeridos
    $requiredFields = ['tour_id', 'fecha_tour', 'numero_personas', 'precio_total', 'tipo_pago', 'metodo_pago', 'cliente_responsable', 'participantes'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    $pdo->beginTransaction();
    
    // 1. Insertar cliente responsable
    $clienteData = $input['cliente_responsable'];
    $stmt = $pdo->prepare("
        INSERT INTO clientes (nombre, apellido, email, celular, celular_contacto, documento, tipo_documento, edad, pais, direccion, descripcion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $clienteData['nombre'],
        $clienteData['apellido'],
        $clienteData['email'],
        $clienteData['telefono'] ?? $clienteData['celular'] ?? null,
        $clienteData['celular_contacto'] ?? null,
        $clienteData['documento_identidad'] ?? $clienteData['documento'],
        $clienteData['tipo_documento'] ?? 'DNI',
        $clienteData['edad'] ?? null,
        $clienteData['pais'] ?? 'Perú',
        $clienteData['ciudad'] ?? $clienteData['direccion'] ?? null,
        'Cliente creado desde reserva online'
    ]);
    
    $clienteResponsableId = $pdo->lastInsertId();
    
    // 2. Crear reserva (usar estructura real de la tabla)
    $stmt = $pdo->prepare("
        INSERT INTO reservas (
            cliente_id, tour_id, fecha_tour, num_personas, precio_por_persona, 
            subtotal, descuento, impuestos, total, estado_reserva, tipo_pago
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $precioPersona = $input['precio_total'] / $input['numero_personas'];
    $subtotal = $input['precio_total'];
    $descuento = 0.00;
    $impuestos = $subtotal * 0.18; // 18% IGV
    $total = $subtotal + $impuestos;
    
    $stmt->execute([
        $clienteResponsableId,
        $input['tour_id'],
        $input['fecha_tour'],
        $input['numero_personas'],
        $precioPersona,
        $subtotal,
        $descuento,
        $impuestos,
        $total,
        'Pendiente',
        $input['tipo_pago'] === 'completo' ? 'Completo' : 'Cuotas'
    ]);
    
    $reservaId = $pdo->lastInsertId();
    
    // 3. Insertar participantes
    $participantes = $input['participantes'];
    foreach ($participantes as $index => $participante) {
        $stmt = $pdo->prepare("
            INSERT INTO participantes_reserva (
                reserva_id, nombre, apellido, email, celular, celular_contacto, 
                documento, tipo_documento, edad, genero, pais, direccion, 
                descripcion, es_responsable
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $esResponsable = ($index === 0) ? 1 : 0;
        
        $stmt->execute([
            $reservaId,
            $participante['nombre'],
            $participante['apellido'],
            $participante['email'] ?? null,
            $participante['celular'] ?? null,
            $participante['celular_contacto'] ?? null,
            $participante['documento_identidad'] ?? $participante['documento'],
            $participante['tipo_documento'],
            $participante['edad'],
            $participante['genero'] ?? null,
            $participante['pais'] ?? null,
            $participante['direccion'] ?? null,
            $participante['descripcion'] ?? null,
            $esResponsable
        ]);
    }
    
    // 4. Crear registro de pago inicial (usar estructura real de la tabla pagos)
    if ($input['tipo_pago'] === 'completo') {
        // Pago completo
        $codigoTransaccion = 'PAY-' . date('Ymd') . '-' . str_pad($reservaId, 6, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("
            INSERT INTO pagos (
                reserva_id, codigo_transaccion, monto_total, metodo_pago, 
                estado_pago, fecha_pago, datos_pago
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        $datosMetodoPago = json_encode([
            'metodo' => $input['metodo_pago'],
            'tipo_pago' => 'completo',
            'fecha_procesamiento' => date('Y-m-d H:i:s'),
            'referencia_interna' => $codigoTransaccion
        ]);
        
        $stmt->execute([
            $reservaId, 
            $codigoTransaccion,
            $total, 
            ucfirst($input['metodo_pago']), 
            'Completado',
            $datosMetodoPago
        ]);
        
        // Actualizar estado de reserva a confirmada
        $stmt = $pdo->prepare("UPDATE reservas SET estado_reserva = 'Confirmada' WHERE reserva_id = ?");
        $stmt->execute([$reservaId]);
        
    } else if ($input['tipo_pago'] === 'cuotas') {
        // Sistema de cuotas
        $primeraCuota = $total * 0.5;
        $segundaCuota = $total * 0.5;
        
        // Primera cuota (inmediata)
        $codigoTransaccion1 = 'PAY-' . date('Ymd') . '-' . str_pad($reservaId, 6, '0', STR_PAD_LEFT) . '-1';
        
        $stmt = $pdo->prepare("
            INSERT INTO pagos (
                reserva_id, codigo_transaccion, monto_total, metodo_pago, 
                estado_pago, fecha_pago, datos_pago
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        $datosMetodoPago1 = json_encode([
            'metodo' => $input['metodo_pago'],
            'tipo_pago' => 'cuota_1',
            'fecha_procesamiento' => date('Y-m-d H:i:s'),
            'referencia_interna' => $codigoTransaccion1
        ]);
        
        $stmt->execute([
            $reservaId, 
            $codigoTransaccion1,
            $primeraCuota, 
            ucfirst($input['metodo_pago']), 
            'Completado',
            $datosMetodoPago1
        ]);
        
        // Segunda cuota (programada)
        $fechaSegundaCuota = date('Y-m-d', strtotime($input['fecha_tour'] . ' -15 days'));
        $codigoTransaccion2 = 'PAY-' . date('Ymd') . '-' . str_pad($reservaId, 6, '0', STR_PAD_LEFT) . '-2';
        
        $stmt = $pdo->prepare("
            INSERT INTO cuotas (reserva_id, numero_cuota, monto, fecha_vencimiento, estado) 
            VALUES (?, 2, ?, ?, 'pendiente')
        ");
        $stmt->execute([$reservaId, $segundaCuota, $fechaSegundaCuota]);
        
        // Actualizar estado de reserva a parcialmente pagada  
        $stmt = $pdo->prepare("UPDATE reservas SET estado_reserva = 'Confirmada' WHERE reserva_id = ?");
        $stmt->execute([$reservaId]);
    }
    
    // 5. Generar código de reserva único
    $codigoReserva = 'JE' . str_pad($reservaId, 6, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("UPDATE reservas SET codigo_reserva = ? WHERE reserva_id = ?");
    $stmt->execute([$codigoReserva, $reservaId]);
    
    $pdo->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Reserva creada exitosamente',
        'data' => [
            'reserva_id' => $reservaId,
            'codigo_reserva' => $codigoReserva,
            'cliente_responsable_id' => $clienteResponsableId,
            'estado' => $input['tipo_pago'] === 'completo' ? 'confirmada' : 'parcialmente_pagada',
            'tipo_pago' => $input['tipo_pago'],
            'monto_pagado' => $input['tipo_pago'] === 'completo' ? $total : ($total * 0.5),
            'monto_pendiente' => $input['tipo_pago'] === 'completo' ? 0 : ($total * 0.5),
            'codigo_transaccion' => $codigoTransaccion ?? null,
            'total' => $total,
            'detalles_pago' => [
                'metodo_pago' => $input['metodo_pago'],
                'estado' => 'Completado',
                'fecha' => date('Y-m-d H:i:s')
            ]
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear la reserva: ' . $e->getMessage()
    ]);
}
?>
