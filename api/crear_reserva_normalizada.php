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
    
    // 1. Verificar si el cliente ya existe por email
    $clienteData = $input['cliente_responsable'];
    $clienteEmail = $clienteData['email'];
    
    $stmt = $pdo->prepare("SELECT cliente_id FROM clientes WHERE email = ?");
    $stmt->execute([$clienteEmail]);
    $clienteExistente = $stmt->fetch();
    
    if ($clienteExistente) {
        // Cliente ya existe, usar el ID existente
        $clienteResponsableId = $clienteExistente['cliente_id'];
        
        // Opcionalmente, actualizar los datos del cliente existente
        $stmt = $pdo->prepare("
            UPDATE clientes SET 
                nombre = ?, apellido = ?, celular = ?, celular_contacto = ?, 
                documento = ?, tipo_documento = ?, edad = ?, pais = ?, direccion = ?
            WHERE cliente_id = ?
        ");
        
        $stmt->execute([
            $clienteData['nombre'],
            $clienteData['apellido'],
            $clienteData['telefono'] ?? $clienteData['celular'] ?? null,
            $clienteData['celular_contacto'] ?? null,
            $clienteData['documento_identidad'] ?? $clienteData['documento'],
            $clienteData['tipo_documento'] ?? 'DNI',
            $clienteData['edad'] ?? null,
            $clienteData['pais'] ?? 'Perú',
            $clienteData['ciudad'] ?? $clienteData['direccion'] ?? null,
            $clienteResponsableId
        ]);
    } else {
        // Cliente no existe, crear nuevo
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
    }
    
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
                documento, tipo_documento, edad, pais, direccion, descripcion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
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
            $participante['pais'] ?? null,
            $participante['direccion'] ?? null,
            'Participante registrado desde reserva online'
        ]);
    }
    
    // 4. Crear registro de pago inicial (usar estructura real de la tabla pagos)
    error_log("💳 Procesando pago tipo: " . $input['tipo_pago']);
    if ($input['tipo_pago'] === 'completo') {
        // Pago completo
        $codigoTransaccion = 'PAY-' . date('Ymd') . '-' . str_pad($reservaId, 6, '0', STR_PAD_LEFT);
        error_log("💰 Pago completo, código: $codigoTransaccion");
        
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
            'referencia_interna' => $codigoTransaccion,
            'transaction_id' => $input['transaction_id'] ?? null
        ]);
        
        error_log("📋 Datos de pago: " . $datosMetodoPago);
        
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
        error_log("✅ Reserva confirmada como pagada completamente");
        
    } else if ($input['tipo_pago'] === 'cuotas') {
        // Sistema de cuotas - crear un pago principal y sus cuotas
        $montoCuota1 = $total * 0.5;
        $montoCuota2 = $total * 0.5;
        error_log("💸 Sistema cuotas: C1=$montoCuota1, C2=$montoCuota2");
        
        // Crear el registro de pago principal para las cuotas
        $codigoTransaccionPrincipal = 'PAY-' . date('Ymd') . '-' . str_pad($reservaId, 6, '0', STR_PAD_LEFT) . '-CUOTAS';
        
        $stmt = $pdo->prepare("
            INSERT INTO pagos (
                reserva_id, codigo_transaccion, monto_total, metodo_pago, 
                estado_pago, fecha_pago, datos_pago
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        $datosMetodoPago = json_encode([
            'metodo' => $input['metodo_pago'],
            'tipo_pago' => 'cuotas',
            'fecha_procesamiento' => date('Y-m-d H:i:s'),
            'referencia_interna' => $codigoTransaccionPrincipal,
            'transaction_id' => $input['transaction_id'] ?? null
        ]);
        
        $stmt->execute([
            $reservaId, 
            $codigoTransaccionPrincipal,
            $total, 
            ucfirst($input['metodo_pago']), 
            'Pendiente', // El estado general será pendiente hasta que se paguen todas las cuotas
            $datosMetodoPago
        ]);
        
        $pagoId = $pdo->lastInsertId();
        
        // Crear las dos cuotas asociadas al pago principal
        // Cuota 1 - Inmediata
        $stmt = $pdo->prepare("
            INSERT INTO cuotas (pago_id, numero_cuota, monto_cuota, estado_cuota, fecha_vencimiento) 
            VALUES (?, 1, ?, 'Pendiente', NOW())
        ");
        $stmt->execute([$pagoId, $montoCuota1]);
        
        // Cuota 2 - 15 días antes del tour
        $fechaSegundaCuota = date('Y-m-d', strtotime($input['fecha_tour'] . ' -15 days'));
        $stmt = $pdo->prepare("
            INSERT INTO cuotas (pago_id, numero_cuota, monto_cuota, estado_cuota, fecha_vencimiento) 
            VALUES (?, 2, ?, 'Pendiente', ?)
        ");
        $stmt->execute([$pagoId, $montoCuota2, $fechaSegundaCuota]);
        
        // Actualizar estado de reserva
        $stmt = $pdo->prepare("UPDATE reservas SET estado_reserva = 'Pendiente' WHERE reserva_id = ?");
        $stmt->execute([$reservaId]);
        error_log("✅ Reserva configurada como pendiente con sistema de cuotas");
    }
    
    // 5. Generar código de reserva único
    $codigoReserva = 'JE' . str_pad($reservaId, 6, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("UPDATE reservas SET codigo_reserva = ? WHERE reserva_id = ?");
    $stmt->execute([$codigoReserva, $reservaId]);
    error_log("🎟️ Código de reserva generado: $codigoReserva");
    
    $pdo->commit();
    error_log("✅ ¡TRANSACCIÓN COMPLETADA EXITOSAMENTE!");
    
    // Respuesta exitosa
    $response = [
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
    ];
    
    error_log("📤 Respuesta: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("❌ ERROR EN RESERVA: " . $e->getMessage());
    error_log("❌ Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear la reserva: ' . $e->getMessage()
    ]);
}
?>
