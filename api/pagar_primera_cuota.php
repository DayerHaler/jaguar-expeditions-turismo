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
    
    // Verificar que la reserva existe y es válida
    $stmt = $pdo->prepare("
        SELECT r.*, p.pago_id, p.estado_pago
        FROM reservas r
        LEFT JOIN pagos p ON r.reserva_id = p.reserva_id
        WHERE r.reserva_id = ? AND r.codigo_reserva = ? AND r.tipo_pago = 'Cuotas'
    ");
    $stmt->execute([$reserva_id, $codigo_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        throw new Exception('Reserva no encontrada o no corresponde a pago en cuotas');
    }
    
    // Verificar que existe la primera cuota pendiente
    $stmt = $pdo->prepare("
        SELECT c.cuota_id, c.monto_cuota, c.estado_cuota
        FROM cuotas c
        INNER JOIN pagos p ON c.pago_id = p.pago_id
        WHERE p.reserva_id = ? AND c.numero_cuota = 1 AND c.estado_cuota = 'Pendiente'
    ");
    $stmt->execute([$reserva_id]);
    $primera_cuota = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$primera_cuota) {
        throw new Exception('La primera cuota no está disponible para pago o ya fue pagada');
    }
    
    // Marcar la primera cuota como pagada
    $stmt = $pdo->prepare("
        UPDATE cuotas 
        SET estado_cuota = 'Pagada', 
            fecha_pago = NOW()
        WHERE cuota_id = ?
    ");
    $stmt->execute([$primera_cuota['cuota_id']]);
    
    // Generar código de transacción
    $codigo_transaccion = 'TXN-' . $codigo_reserva . '-C1-' . date('YmdHis');
    
    $pdo->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Primera cuota pagada exitosamente',
        'data' => [
            'codigo_transaccion' => $codigo_transaccion,
            'monto_pagado' => floatval($primera_cuota['monto_cuota']),
            'fecha_pago' => date('Y-m-d H:i:s'),
            'metodo_pago' => $metodo_pago,
            'cuota_numero' => 1
        ]
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar el pago: ' . $e->getMessage()
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos'
    ]);
}
?>
