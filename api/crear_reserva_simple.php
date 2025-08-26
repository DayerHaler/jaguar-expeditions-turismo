<?php
// Configurar headers inmediatamente
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_clean();
}

// Comenzar captura de output
ob_start();

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error JSON: ' . json_last_error_msg());
    }
    
    // Simular respuesta exitosa por ahora
    $response = [
        'success' => true,
        'message' => 'Datos recibidos correctamente',
        'data' => [
            'received_data_keys' => array_keys($data),
            'method' => $_SERVER['REQUEST_METHOD']
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Limpiar buffer y enviar respuesta
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    // Limpiar buffer y enviar error
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Terminar ejecución
exit;
?>
