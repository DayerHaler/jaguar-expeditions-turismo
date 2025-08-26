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
        INSERT INTO clientes (nombre, apellido, email, celular, celular_contacto, documento, tipo_documento, edad, genero, pais, direccion, descripcion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $clienteData['nombre'],
        $clienteData['apellido'],
        $clienteData['email'],
        $clienteData['celular'] ?? null,
        $clienteData['celular_contacto'] ?? null,
        $clienteData['documento'],
        $clienteData['tipo_documento'],
        $clienteData['edad'],
        $clienteData['genero'] ?? null,
        $clienteData['pais'] ?? null,
        $clienteData['direccion'] ?? null,
        $clienteData['descripcion'] ?? null
    ]);
    
    $clienteResponsableId = $pdo->lastInsertId();
    
    // 2. Crear reserva
    $stmt = $pdo->prepare("
        INSERT INTO reservas (tour_id, cliente_responsable_id, fecha_tour, numero_personas, precio_total, tipo_pago, metodo_pago, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");
    
    $stmt->execute([
        $input['tour_id'],
        $clienteResponsableId,
        $input['fecha_tour'],
        $input['numero_personas'],
        $input['precio_total'],
        $input['tipo_pago'],
        $input['metodo_pago']
    ]);
    
    $reservaId = $pdo->lastInsertId();
    
    // 3. Insertar participantes
    $participantes = $input['participantes'];
    foreach ($participantes as $index => $participante) {
        $stmt = $pdo->prepare("
            INSERT INTO participantes_reserva (reserva_id, nombre, apellido, email, celular, celular_contacto, documento, tipo_documento, edad, genero, pais, direccion, descripcion, es_responsable) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $esResponsable = ($index === 0) ? 1 : 0;
        
        $stmt->execute([
            $reservaId,
            $participante['nombre'],
            $participante['apellido'],
            $participante['email'] ?? null,
            $participante['celular'] ?? null,
            $participante['celular_contacto'] ?? null,
            $participante['documento'],
            $participante['tipo_documento'],
            $participante['edad'],
            $participante['genero'] ?? null,
            $participante['pais'] ?? null,
            $participante['direccion'] ?? null,
            $participante['descripcion'] ?? null,
            $esResponsable
        ]);
    }
    
    // 4. Crear registro de pago inicial
    if ($input['tipo_pago'] === 'completo') {
        // Pago completo
        $stmt = $pdo->prepare("
            INSERT INTO pagos (reserva_id, monto, metodo_pago, estado, fecha_pago) 
            VALUES (?, ?, ?, 'completado', NOW())
        ");
        $stmt->execute([$reservaId, $input['precio_total'], $input['metodo_pago']]);
        
        // Actualizar estado de reserva a confirmada
        $stmt = $pdo->prepare("UPDATE reservas SET estado = 'confirmada' WHERE id = ?");
        $stmt->execute([$reservaId]);
        
    } else if ($input['tipo_pago'] === 'cuotas') {
        // Sistema de cuotas
        $primeraCuota = $input['precio_total'] * 0.5;
        $segundaCuota = $input['precio_total'] * 0.5;
        
        // Primera cuota (inmediata)
        $stmt = $pdo->prepare("
            INSERT INTO pagos (reserva_id, monto, metodo_pago, estado, fecha_pago) 
            VALUES (?, ?, ?, 'completado', NOW())
        ");
        $stmt->execute([$reservaId, $primeraCuota, $input['metodo_pago']]);
        
        // Segunda cuota (programada)
        $fechaSegundaCuota = date('Y-m-d', strtotime($input['fecha_tour'] . ' -15 days'));
        $stmt = $pdo->prepare("
            INSERT INTO cuotas (reserva_id, numero_cuota, monto, fecha_vencimiento, estado) 
            VALUES (?, 2, ?, ?, 'pendiente')
        ");
        $stmt->execute([$reservaId, $segundaCuota, $fechaSegundaCuota]);
        
        // Actualizar estado de reserva a parcialmente pagada
        $stmt = $pdo->prepare("UPDATE reservas SET estado = 'parcialmente_pagada' WHERE id = ?");
        $stmt->execute([$reservaId]);
    }
    
    // 5. Generar código de reserva único
    $codigoReserva = 'JE' . str_pad($reservaId, 6, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("UPDATE reservas SET codigo_reserva = ? WHERE id = ?");
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
            'monto_pagado' => $input['tipo_pago'] === 'completo' ? $input['precio_total'] : ($input['precio_total'] * 0.5),
            'monto_pendiente' => $input['tipo_pago'] === 'completo' ? 0 : ($input['precio_total'] * 0.5)
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
