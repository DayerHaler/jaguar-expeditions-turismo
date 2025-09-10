<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de base de datos
$host = 'localhost';
$dbname = 'jaguar_expeditions';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tourId = $_GET['path'] ?? $_GET['id'] ?? null;
    
    if (!$tourId) {
        echo json_encode(['success' => false, 'message' => 'ID de tour requerido']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM tours WHERE id = ? AND estado = 'Activo'");
    $stmt->execute([$tourId]);
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tour) {
        echo json_encode(['success' => false, 'message' => 'Tour no encontrado']);
        exit;
    }
    
    // Formatear los datos
    $tour['precio'] = floatval($tour['precio']);
    $tour['grupo_max'] = intval($tour['grupo_max'] ?? $tour['max_personas'] ?? 10);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tour obtenido correctamente',
        'data' => $tour
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
}
?>
