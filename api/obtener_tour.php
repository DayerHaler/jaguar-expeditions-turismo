<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Configuración de la base de datos
    $host = 'localhost';
    $dbname = 'jaguar_expeditions';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener el ID del tour
    $tour_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($tour_id <= 0) {
        throw new Exception('ID de tour inválido');
    }

    // Consultar información del tour
    $stmt = $pdo->prepare("
        SELECT id, nombre, descripcion, descripcion_corta, precio, precio_descuento, duracion, max_personas, min_personas,
               imagen_principal, imagenes_galeria, incluye, no_incluye, itinerario, dificultad, categoria, estado, destacado
        FROM tours 
        WHERE id = ? AND estado = 'Activo'
    ");
    
    $stmt->execute([$tour_id]);
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tour) {
        throw new Exception('Tour no encontrado');
    }

    // Convertir campos JSON si existen
    if ($tour['incluye']) {
        $tour['incluye'] = json_decode($tour['incluye'], true);
    }
    
    if ($tour['no_incluye']) {
        $tour['no_incluye'] = json_decode($tour['no_incluye'], true);
    }

    echo json_encode([
        'success' => true,
        'tour' => $tour
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
