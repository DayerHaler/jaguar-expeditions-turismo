<?php
/**
 * API PARA CONSULTAR ESTADO DE RESERVA
 * ====================================
 * 
 * Endpoint para consultar el estado de una reserva por código
 */

require_once '../config/config.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respuestaJSON(false, 'Método no permitido');
}

// Obtener código de reserva
$codigoReserva = $_GET['codigo'] ?? '';

if (empty($codigoReserva)) {
    respuestaJSON(false, 'Código de reserva requerido');
}

try {
    $db = getDB();
    
    // Buscar la reserva con información del tour
    $sql = "
        SELECT 
            r.*,
            t.nombre as tour_nombre,
            t.duracion as tour_duracion,
            CASE 
                WHEN r.estado_reserva = 'Pendiente_Pago' THEN DATE_ADD(r.fecha_creacion, INTERVAL 24 HOUR)
                ELSE NULL 
            END as tiempo_limite_pago
        FROM reservas r
        INNER JOIN tours t ON r.tour_id = t.id
        WHERE r.codigo_reserva = ?
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$codigoReserva]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        respuestaJSON(false, 'Reserva no encontrada');
    }
    
    // Verificar si la reserva ha expirado
    if ($reserva['estado_reserva'] === 'Pendiente_Pago' && $reserva['tiempo_limite_pago']) {
        $tiempoLimite = new DateTime($reserva['tiempo_limite_pago']);
        $ahora = new DateTime();
        
        if ($ahora > $tiempoLimite) {
            // La reserva ha expirado, actualizarla
            $updateSql = "UPDATE reservas SET estado_reserva = 'Cancelada' WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$reserva['id']]);
            
            // Liberar cupos
            $liberarSql = "
                UPDATE disponibilidad_tours 
                SET cupos_reservados = cupos_reservados - ? 
                WHERE tour_id = ? AND fecha = ?
            ";
            $liberarStmt = $db->prepare($liberarSql);
            $liberarStmt->execute([
                $reserva['total_personas'],
                $reserva['tour_id'],
                $reserva['fecha_tour']
            ]);
            
            $reserva['estado_reserva'] = 'Cancelada';
            $reserva['tiempo_limite_pago'] = null;
        }
    }
    
    // Formatear datos para respuesta
    $respuesta = [
        'id' => $reserva['id'],
        'codigo_reserva' => $reserva['codigo_reserva'],
        'tour_id' => $reserva['tour_id'],
        'tour_nombre' => $reserva['tour_nombre'],
        'tour_duracion' => $reserva['tour_duracion'],
        'fecha_tour' => $reserva['fecha_tour'],
        'num_adultos' => $reserva['num_adultos'],
        'num_ninos' => $reserva['num_ninos'],
        'num_bebes' => $reserva['num_bebes'],
        'total_personas' => $reserva['total_personas'],
        'cliente_nombre' => $reserva['cliente_nombre'],
        'cliente_apellido' => $reserva['cliente_apellido'],
        'cliente_email' => $reserva['cliente_email'],
        'cliente_telefono' => $reserva['cliente_telefono'],
        'cliente_pais' => $reserva['cliente_pais'],
        'precio_unitario' => $reserva['precio_unitario'],
        'descuento' => $reserva['descuento'],
        'subtotal' => $reserva['subtotal'],
        'impuestos' => $reserva['impuestos'],
        'total' => $reserva['total'],
        'moneda' => $reserva['moneda'],
        'estado_reserva' => $reserva['estado_reserva'],
        'estado_pago' => $reserva['estado_pago'],
        'comentarios_especiales' => $reserva['comentarios_especiales'],
        'fecha_creacion' => $reserva['fecha_creacion'],
        'tiempo_limite_pago' => $reserva['tiempo_limite_pago'],
        'total_formateado' => formatearPrecio($reserva['total'])
    ];
    
    // Agregar información de estado legible
    switch ($reserva['estado_reserva']) {
        case 'Pendiente_Pago':
            $respuesta['estado_descripcion'] = 'Pendiente de pago';
            $respuesta['puede_pagar'] = true;
            $respuesta['puede_cancelar'] = true;
            break;
            
        case 'Confirmada':
            $respuesta['estado_descripcion'] = 'Confirmada';
            $respuesta['puede_pagar'] = false;
            $respuesta['puede_cancelar'] = true;
            break;
            
        case 'Pagada':
            $respuesta['estado_descripcion'] = 'Pagada y confirmada';
            $respuesta['puede_pagar'] = false;
            $respuesta['puede_cancelar'] = false;
            break;
            
        case 'Cancelada':
            $respuesta['estado_descripcion'] = 'Cancelada';
            $respuesta['puede_pagar'] = false;
            $respuesta['puede_cancelar'] = false;
            break;
            
        case 'Completada':
            $respuesta['estado_descripcion'] = 'Completada';
            $respuesta['puede_pagar'] = false;
            $respuesta['puede_cancelar'] = false;
            break;
            
        default:
            $respuesta['estado_descripcion'] = 'Estado desconocido';
            $respuesta['puede_pagar'] = false;
            $respuesta['puede_cancelar'] = false;
    }
    
    respuestaJSON(true, 'Información de reserva obtenida', $respuesta);
    
} catch (Exception $e) {
    logError("Error al consultar reserva: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}
?>
