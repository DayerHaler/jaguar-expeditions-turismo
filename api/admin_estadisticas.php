<?php
/**
 * API PARA ESTADÍSTICAS DEL ADMIN
 * ================================
 * 
 * Endpoint para obtener estadísticas del dashboard
 */

require_once '../config/config.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respuestaJSON(false, 'Método no permitido');
}

try {
    $db = getDB();
    
    // Estadísticas de reservas por estado
    $estadisticasEstado = $db->query("
        SELECT estado_reserva, COUNT(*) as cantidad
        FROM reservas 
        WHERE DATE(fecha_reserva) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY estado_reserva
    ")->fetchAll();
    
    // Convertir a array asociativo
    $stats = [
        'pendientes' => 0,
        'confirmadas' => 0,
        'pagadas' => 0,
        'canceladas' => 0,
        'completadas' => 0
    ];
    
    foreach ($estadisticasEstado as $stat) {
        switch ($stat['estado_reserva']) {
            case 'Pendiente_Pago':
                $stats['pendientes'] = $stat['cantidad'];
                break;
            case 'Confirmada':
                $stats['confirmadas'] = $stat['cantidad'];
                break;
            case 'Pagada':
                $stats['pagadas'] = $stat['cantidad'];
                break;
            case 'Cancelada':
                $stats['canceladas'] = $stat['cantidad'];
                break;
            case 'Completada':
                $stats['completadas'] = $stat['cantidad'];
                break;
        }
    }
    
    // Sumar confirmadas y pagadas para el total
    $stats['confirmadas'] = $stats['confirmadas'] + $stats['pagadas'];
    
    // Ingresos del día actual
    $ingresosDia = $db->query("
        SELECT COALESCE(SUM(r.total), 0) as total_ingresos
        FROM reservas r
        WHERE DATE(r.fecha_reserva) = CURDATE()
        AND r.estado_pago = 'Pagado'
    ")->fetch();
    
    $stats['ingresos'] = $ingresosDia['total_ingresos'];
    
    // Estadísticas adicionales
    $estadisticasAdicionales = [];
    
    // Tours más populares (últimos 30 días)
    $toursPopulares = $db->query("
        SELECT t.nombre, COUNT(r.id) as reservas
        FROM reservas r
        INNER JOIN tours t ON r.tour_id = t.id
        WHERE DATE(r.fecha_reserva) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND r.estado_reserva != 'Cancelada'
        GROUP BY t.id, t.nombre
        ORDER BY reservas DESC
        LIMIT 5
    ")->fetchAll();
    
    $estadisticasAdicionales['tours_populares'] = $toursPopulares;
    
    // Reservas por día (última semana)
    $reservasPorDia = $db->query("
        SELECT DATE(fecha_reserva) as fecha, COUNT(*) as cantidad
        FROM reservas
        WHERE DATE(fecha_reserva) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(fecha_reserva)
        ORDER BY fecha DESC
    ")->fetchAll();
    
    $estadisticasAdicionales['reservas_por_dia'] = $reservasPorDia;
    
    // Reservas próximas a expirar (próximas 2 horas)
    $reservasProximasExpirar = $db->query("
        SELECT COUNT(*) as cantidad
        FROM reservas
        WHERE estado_reserva = 'Pendiente_Pago'
        AND tiempo_limite_pago <= DATE_ADD(NOW(), INTERVAL 2 HOUR)
        AND tiempo_limite_pago > NOW()
    ")->fetch();
    
    $estadisticasAdicionales['proximas_expirar'] = $reservasProximasExpirar['cantidad'];
    
    // Tasa de conversión (últimos 30 días)
    $totalReservas = $db->query("
        SELECT COUNT(*) as total
        FROM reservas
        WHERE DATE(fecha_reserva) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ")->fetch();
    
    $reservasPagadas = $db->query("
        SELECT COUNT(*) as pagadas
        FROM reservas
        WHERE DATE(fecha_reserva) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND (estado_reserva = 'Pagada' OR estado_reserva = 'Confirmada' OR estado_reserva = 'Completada')
    ")->fetch();
    
    $tasaConversion = $totalReservas['total'] > 0 
        ? round(($reservasPagadas['pagadas'] / $totalReservas['total']) * 100, 2)
        : 0;
    
    $estadisticasAdicionales['tasa_conversion'] = $tasaConversion;
    
    respuestaJSON(true, 'Estadísticas obtenidas correctamente', [
        'estadisticas' => $stats,
        'adicionales' => $estadisticasAdicionales
    ]);
    
} catch (Exception $e) {
    logError("Error al obtener estadísticas: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}
?>
