<?php
/**
 * API PARA ADMINISTRACIÓN DE RESERVAS
 * ===================================
 * 
 * Endpoint para obtener todas las reservas con filtros
 */

require_once '../config/config.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respuestaJSON(false, 'Método no permitido');
}

try {
    $db = getDB();
    
    // Construir consulta con filtros
    $where = ['1=1'];
    $params = [];
    
    // Filtro por estado
    if (!empty($_GET['estado'])) {
        $where[] = 'r.estado_reserva = ?';
        $params[] = $_GET['estado'];
    }
    
    // Filtro por fecha desde
    if (!empty($_GET['fecha_desde'])) {
        $where[] = 'DATE(r.fecha_reserva) >= ?';
        $params[] = $_GET['fecha_desde'];
    }
    
    // Filtro por fecha hasta
    if (!empty($_GET['fecha_hasta'])) {
        $where[] = 'DATE(r.fecha_reserva) <= ?';
        $params[] = $_GET['fecha_hasta'];
    }
    
    // Filtro por tour
    if (!empty($_GET['tour_id'])) {
        $where[] = 'r.tour_id = ?';
        $params[] = $_GET['tour_id'];
    }
    
    // Filtro de búsqueda general
    if (!empty($_GET['buscar'])) {
        $buscar = '%' . $_GET['buscar'] . '%';
        $where[] = '(r.cliente_nombre LIKE ? OR r.cliente_apellido LIKE ? OR r.cliente_email LIKE ? OR r.codigo_reserva LIKE ?)';
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Consulta principal
    $sql = "
        SELECT r.*,
               t.nombre as tour_nombre,
               t.precio_adulto,
               t.precio_nino
        FROM reservas r
        INNER JOIN tours t ON r.tour_id = t.id
        WHERE $whereClause
        ORDER BY r.fecha_reserva DESC
        LIMIT 500
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll();
    
    // Formatear datos para respuesta
    $reservasFormateadas = [];
    foreach ($reservas as $reserva) {
        $reservasFormateadas[] = [
            'id' => $reserva['id'],
            'codigo_reserva' => $reserva['codigo_reserva'],
            'tour_id' => $reserva['tour_id'],
            'tour_nombre' => $reserva['tour_nombre'],
            'cliente_nombre' => $reserva['cliente_nombre'],
            'cliente_apellido' => $reserva['cliente_apellido'],
            'cliente_email' => $reserva['cliente_email'],
            'cliente_telefono' => $reserva['cliente_telefono'],
            'fecha_tour' => $reserva['fecha_tour'],
            'num_adultos' => $reserva['num_adultos'],
            'num_ninos' => $reserva['num_ninos'],
            'total_personas' => $reserva['total_personas'],
            'total' => $reserva['total'],
            'estado_reserva' => $reserva['estado_reserva'],
            'estado_pago' => $reserva['estado_pago'],
            'fecha_reserva' => $reserva['fecha_reserva'],
            'fecha_actualizacion' => $reserva['fecha_actualizacion'],
            'tiempo_limite_pago' => $reserva['tiempo_limite_pago'],
            'comentarios' => $reserva['comentarios']
        ];
    }
    
    respuestaJSON(true, 'Reservas obtenidas correctamente', [
        'reservas' => $reservasFormateadas,
        'total' => count($reservasFormateadas),
        'filtros_aplicados' => $_GET
    ]);
    
} catch (Exception $e) {
    logError("Error al obtener reservas admin: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}
?>
