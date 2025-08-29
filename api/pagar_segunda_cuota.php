<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
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
    $input = $_POST;
}

$reserva_id = trim($input['reserva_id'] ?? '');
$codigo_reserva = trim($input['codigo_reserva'] ?? '');
$metodo_pago = trim($input['metodo_pago'] ?? 'Tarjeta');

// Validar datos requeridos
if (empty($reserva_id) || empty($codigo_reserva)) {
    echo json_encode(['success' => false, 'message' => 'Datos de reserva requeridos']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Verificar que la reserva existe y es elegible para segunda cuota
    $query = "
        SELECT r.*, p.pago_id
        FROM reservas r
        INNER JOIN pagos p ON r.reserva_id = p.reserva_id
        WHERE r.reserva_id = ? AND r.codigo_reserva = ? AND r.tipo_pago = 'Cuotas'
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$reserva_id, $codigo_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada o no válida para segunda cuota']);
        exit();
    }
    
    // Verificar el estado de las cuotas
    $stmt = $pdo->prepare("
        SELECT numero_cuota, monto_cuota, estado_cuota 
        FROM cuotas 
        WHERE pago_id = ? 
        ORDER BY numero_cuota
    ");
    $stmt->execute([$reserva['pago_id']]);
    $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $primera_cuota_pagada = false;
    $segunda_cuota_pendiente = false;
    $monto_segunda_cuota = 0;
    $cuota_id_segunda = null;
    
    foreach ($cuotas as $cuota) {
        if ($cuota['numero_cuota'] == 1 && $cuota['estado_cuota'] == 'Pagada') {
            $primera_cuota_pagada = true;
        }
        if ($cuota['numero_cuota'] == 2 && $cuota['estado_cuota'] == 'Pendiente') {
            $segunda_cuota_pendiente = true;
            $monto_segunda_cuota = floatval($cuota['monto_cuota']);
        }
    }
    
    if (!$primera_cuota_pagada) {
        echo json_encode(['success' => false, 'message' => 'Debe pagar la primera cuota antes de la segunda']);
        exit();
    }
    
    if (!$segunda_cuota_pendiente) {
        echo json_encode(['success' => false, 'message' => 'La segunda cuota no está disponible para pago']);
        exit();
    }
    
    // Actualizar el estado de la segunda cuota
    $codigo_transaccion = 'TXN' . date('YmdHis') . rand(1000, 9999);
    
    $update_cuota = "
        UPDATE cuotas 
        SET estado_cuota = 'Pagada', 
            fecha_pago = NOW(),
            codigo_transaccion_cuota = ?
        WHERE pago_id = ? AND numero_cuota = 2
    ";
    
    $stmt = $pdo->prepare($update_cuota);
    $stmt->execute([$codigo_transaccion, $reserva['pago_id']]);
    
    // Verificar si todas las cuotas están pagadas para actualizar el estado general
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_cuotas, 
               COUNT(CASE WHEN estado_cuota = 'Pagada' THEN 1 END) as cuotas_pagadas
        FROM cuotas 
        WHERE pago_id = ?
    ");
    $stmt->execute([$reserva['pago_id']]);
    $estado_cuotas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estado_cuotas['total_cuotas'] == $estado_cuotas['cuotas_pagadas']) {
        // Todas las cuotas están pagadas, actualizar estado de pago y reserva
        $update_pago = "UPDATE pagos SET estado_pago = 'Completado' WHERE pago_id = ?";
        $stmt = $pdo->prepare($update_pago);
        $stmt->execute([$reserva['pago_id']]);
        
        $update_reserva = "UPDATE reservas SET estado_reserva = 'Pagada' WHERE reserva_id = ?";
        $stmt = $pdo->prepare($update_reserva);
        $stmt->execute([$reserva_id]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Segunda cuota pagada exitosamente',
        'data' => [
            'monto_pagado' => $monto_segunda_cuota,
            'codigo_transaccion' => $codigo_transaccion,
            'nuevo_estado' => ($estado_cuotas['total_cuotas'] == $estado_cuotas['cuotas_pagadas']) ? 'Pagada' : 'Pago Parcial'
        ]
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar el pago: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>
