<?php
/**
 * API PARA OBTENER FECHAS DISPONIBLES
 * ===================================
 * 
 * Endpoint para obtener las fechas disponibles de un tour específico
 */

require_once '../config/config.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respuestaJSON(false, 'Método no permitido');
}

// Obtener parámetros
$tour_id = $_GET['tour_id'] ?? null;

if (!$tour_id) {
    respuestaJSON(false, 'ID del tour es requerido');
}

try {
    $db = getDB();
    
    // Verificar que el tour existe y está activo
    $sqlTour = "SELECT id, nombre, max_personas FROM tours WHERE id = ? AND estado = 'Activo'";
    $stmtTour = $db->prepare($sqlTour);
    $stmtTour->execute([$tour_id]);
    $tour = $stmtTour->fetch();
    
    if (!$tour) {
        respuestaJSON(false, 'Tour no encontrado o no disponible');
    }
    
    // Obtener fechas disponibles (solo fechas futuras)
    $sql = "
        SELECT fecha, cupos_disponibles, cupos_reservados, 
               precio_especial, estado, notas
        FROM disponibilidad_tours 
        WHERE tour_id = ? 
          AND fecha >= CURDATE() 
          AND estado = 'Disponible'
          AND (cupos_disponibles - IFNULL(cupos_reservados, 0)) > 0
        ORDER BY fecha ASC
        LIMIT 30
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$tour_id]);
    $fechas = $stmt->fetchAll();
    
    // Formatear datos
    $fechasFormateadas = [];
    foreach ($fechas as $fecha) {
        $cuposLibres = $fecha['cupos_disponibles'] - ($fecha['cupos_reservados'] ?? 0);
        
        $fechasFormateadas[] = [
            'fecha' => $fecha['fecha'],
            'fecha_formateada' => date('d/m/Y', strtotime($fecha['fecha'])),
            'cupos_disponibles' => $fecha['cupos_disponibles'],
            'cupos_reservados' => $fecha['cupos_reservados'] ?? 0,
            'cupos_libres' => $cuposLibres,
            'precio_especial' => $fecha['precio_especial'] ? floatval($fecha['precio_especial']) : null,
            'estado' => $fecha['estado'],
            'notas' => $fecha['notas']
        ];
    }
    
    respuestaJSON(true, 'Fechas obtenidas correctamente', [
        'tour' => [
            'id' => $tour['id'],
            'nombre' => $tour['nombre'],
            'max_personas' => $tour['max_personas']
        ],
        'fechas' => $fechasFormateadas,
        'total_fechas' => count($fechasFormateadas)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en obtener_fechas_disponibles.php: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
} catch (Exception $e) {
    error_log("Error general en obtener_fechas_disponibles.php: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}
?>
