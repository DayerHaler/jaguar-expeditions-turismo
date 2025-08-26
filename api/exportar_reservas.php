<?php
/**
 * API PARA EXPORTAR RESERVAS A EXCEL
 * ===================================
 * 
 * Endpoint para exportar reservas a formato Excel (CSV)
 */

require_once '../config/config.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo 'Método no permitido';
    exit;
}

try {
    $db = getDB();
    
    // Construir consulta con filtros (igual que admin_reservas.php)
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
    
    // Consulta para exportación
    $sql = "
        SELECT r.codigo_reserva,
               CONCAT(r.cliente_nombre, ' ', r.cliente_apellido) as cliente_completo,
               r.cliente_email,
               r.cliente_telefono,
               t.nombre as tour_nombre,
               DATE_FORMAT(r.fecha_tour, '%d/%m/%Y') as fecha_tour,
               r.num_adultos,
               r.num_ninos,
               r.total_personas,
               r.total,
               r.estado_reserva,
               r.estado_pago,
               DATE_FORMAT(r.fecha_reserva, '%d/%m/%Y %H:%i') as fecha_reserva,
               r.comentarios
        FROM reservas r
        INNER JOIN tours t ON r.tour_id = t.id
        WHERE $whereClause
        ORDER BY r.fecha_reserva DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll();
    
    // Configurar headers para descarga CSV
    $filename = 'reservas_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    // Crear el archivo CSV
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Escribir encabezados
    $headers = [
        'Código Reserva',
        'Cliente',
        'Email',
        'Teléfono',
        'Tour',
        'Fecha Tour',
        'Adultos',
        'Niños',
        'Total Personas',
        'Total ($)',
        'Estado Reserva',
        'Estado Pago',
        'Fecha Reserva',
        'Comentarios'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Escribir datos
    foreach ($reservas as $reserva) {
        $row = [
            $reserva['codigo_reserva'],
            $reserva['cliente_completo'],
            $reserva['cliente_email'],
            $reserva['cliente_telefono'],
            $reserva['tour_nombre'],
            $reserva['fecha_tour'],
            $reserva['num_adultos'],
            $reserva['num_ninos'],
            $reserva['total_personas'],
            $reserva['total'],
            str_replace('_', ' ', $reserva['estado_reserva']),
            $reserva['estado_pago'],
            $reserva['fecha_reserva'],
            $reserva['comentarios']
        ];
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    logError("Error al exportar reservas: " . $e->getMessage());
    http_response_code(500);
    echo 'Error interno del servidor';
}
?>
