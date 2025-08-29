<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuración de base de datos
$host = 'localhost';
$dbname = 'jaguar_expeditions';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit();
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos de entrada
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos válidos']);
    exit();
}

$codigo_reserva = trim($input['codigo_reserva'] ?? '');
$transaction_id = trim($input['transaction_id'] ?? '');
$monto_pagado = floatval($input['monto'] ?? 0);

// Validar datos requeridos
if (empty($codigo_reserva) || empty($transaction_id) || $monto_pagado <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos requeridos faltantes: codigo_reserva, transaction_id y monto']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Buscar la reserva y su información de cuotas
    $stmt = $pdo->prepare("
        SELECT r.reserva_id, r.codigo_reserva, r.total, r.tipo_pago,
               p.pago_id, p.estado_pago,
               c.cuota_id, c.numero_cuota, c.monto_cuota, c.estado_cuota
        FROM reservas r
        INNER JOIN pagos p ON r.reserva_id = p.reserva_id
        INNER JOIN cuotas c ON p.pago_id = c.pago_id
        WHERE r.codigo_reserva = ? AND r.tipo_pago = 'Cuotas' AND c.numero_cuota = 1 AND c.estado_cuota = 'Pendiente'
    ");
    $stmt->execute([$codigo_reserva]);
    $reserva_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva_info) {
        throw new Exception('Reserva no encontrada, no es de tipo cuotas, o la primera cuota ya fue pagada');
    }
    
    // Verificar que el monto coincida con la primera cuota
    if (abs($monto_pagado - $reserva_info['monto_cuota']) > 0.01) {
        throw new Exception('El monto pagado no coincide con el monto de la primera cuota');
    }
    
    // Actualizar el estado de la primera cuota a "Pagada"
    $stmt = $pdo->prepare("
        UPDATE cuotas 
        SET estado_cuota = 'Pagada', 
            fecha_pago = NOW(),
            codigo_transaccion = ?
        WHERE cuota_id = ?
    ");
    $stmt->execute([$transaction_id, $reserva_info['cuota_id']]);
    
    // Verificar si todas las cuotas están pagadas para actualizar el estado del pago principal
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_cuotas,
               COUNT(CASE WHEN estado_cuota = 'Pagada' THEN 1 END) as cuotas_pagadas
        FROM cuotas 
        WHERE pago_id = ?
    ");
    $stmt->execute([$reserva_info['pago_id']]);
    $estado_cuotas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si todas las cuotas están pagadas, actualizar el estado del pago principal
    if ($estado_cuotas['total_cuotas'] == $estado_cuotas['cuotas_pagadas']) {
        $stmt = $pdo->prepare("UPDATE pagos SET estado_pago = 'Completado' WHERE pago_id = ?");
        $stmt->execute([$reserva_info['pago_id']]);
        
        $stmt = $pdo->prepare("UPDATE reservas SET estado_reserva = 'Confirmada' WHERE reserva_id = ?");
        $stmt->execute([$reserva_info['reserva_id']]);
    } else {
        // Si solo se pagó la primera cuota, cambiar el estado de la reserva a "Confirmada" (parcialmente pagada)
        $stmt = $pdo->prepare("UPDATE reservas SET estado_reserva = 'Confirmada' WHERE reserva_id = ?");
        $stmt->execute([$reserva_info['reserva_id']]);
    }
    
    $pdo->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Primera cuota pagada exitosamente',
        'data' => [
            'codigo_reserva' => $codigo_reserva,
            'transaction_id' => $transaction_id,
            'monto_pagado' => $monto_pagado,
            'fecha_pago' => date('Y-m-d H:i:s'),
            'cuotas_restantes' => $estado_cuotas['total_cuotas'] - $estado_cuotas['cuotas_pagadas']
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    
    // Log del error
    error_log("Error en pago de primera cuota: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar el pago de la primera cuota: ' . $e->getMessage()
    ]);
}
?>
