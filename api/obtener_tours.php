<?php
/**
 * API PARA OBTENER TOURS
 * =======================
 * 
 * Endpoint para obtener todos los tours disponibles
 */

require_once '../config/config.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respuestaJSON(false, 'Método no permitido');
}

try {
    $db = getDB();
    
    // Obtener todos los tours
    $sql = "
        SELECT id, nombre, descripcion_corta, precio, precio_descuento,
               duracion, dificultad, estado, max_personas, min_personas
        FROM tours 
        WHERE estado = 'Activo'
        ORDER BY nombre ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $tours = $stmt->fetchAll();
    
    // Formatear datos para respuesta
    $toursFormateados = [];
    foreach ($tours as $tour) {
        $toursFormateados[] = [
            'id' => $tour['id'],
            'nombre' => $tour['nombre'],
            'descripcion_corta' => $tour['descripcion_corta'],
            'precio' => floatval($tour['precio']),
            'precio_descuento' => $tour['precio_descuento'] ? floatval($tour['precio_descuento']) : null,
            'duracion' => $tour['duracion'],
            'dificultad' => $tour['dificultad'],
            'max_personas' => $tour['max_personas'],
            'min_personas' => $tour['min_personas'],
            'estado' => $tour['estado'],
            'disponible' => ($tour['estado'] === 'Activo')
        ];
    }
    
    respuestaJSON(true, 'Tours obtenidos correctamente', [
        'tours' => $toursFormateados,
        'total' => count($toursFormateados)
    ]);
    
} catch (Exception $e) {
    logError("Error al obtener tours: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}
?>
