<?php
/**
 * API PARA CANCELAR RESERVA
 * =========================
 * 
 * Endpoint para cancelar una reserva
 */

require_once '../config/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON(false, 'Método no permitido');
}

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    respuestaJSON(false, 'Datos inválidos');
}

$codigoReserva = $data['codigo_reserva'] ?? '';
$motivo = $data['motivo'] ?? 'Cancelación solicitada por el cliente';

if (empty($codigoReserva)) {
    respuestaJSON(false, 'Código de reserva requerido');
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Buscar la reserva
    $stmt = $db->prepare("
        SELECT r.*, t.nombre as tour_nombre 
        FROM reservas r 
        INNER JOIN tours t ON r.tour_id = t.id 
        WHERE r.codigo_reserva = ?
    ");
    $stmt->execute([$codigoReserva]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        respuestaJSON(false, 'Reserva no encontrada');
    }
    
    // Verificar que la reserva se puede cancelar
    if ($reserva['estado_reserva'] === 'Cancelada') {
        respuestaJSON(false, 'La reserva ya está cancelada');
    }
    
    if ($reserva['estado_reserva'] === 'Completada') {
        respuestaJSON(false, 'No se puede cancelar una reserva completada');
    }
    
    // Verificar si es una cancelación tardía (menos de 24 horas antes del tour)
    $fechaTour = new DateTime($reserva['fecha_tour']);
    $ahora = new DateTime();
    $diferencia = $fechaTour->diff($ahora);
    $horasRestantes = ($diferencia->days * 24) + $diferencia->h;
    
    $esCancelacionTardia = false;
    if ($horasRestantes < 24) {
        $esCancelacionTardia = true;
        // Aquí podrías aplicar penalizaciones o restricciones adicionales
    }
    
    // Actualizar estado de la reserva
    $updateSql = "
        UPDATE reservas 
        SET estado_reserva = 'Cancelada',
            fecha_actualizacion = NOW()
        WHERE id = ?
    ";
    $stmt = $db->prepare($updateSql);
    $stmt->execute([$reserva['id']]);
    
    // Liberar cupos en disponibilidad
    $liberarSql = "
        UPDATE disponibilidad_tours 
        SET cupos_reservados = cupos_reservados - ? 
        WHERE tour_id = ? AND fecha = ?
    ";
    $stmt = $db->prepare($liberarSql);
    $stmt->execute([
        $reserva['total_personas'],
        $reserva['tour_id'],
        $reserva['fecha_tour']
    ]);
    
    // Si había pagos, marcarlos como reembolsables
    if ($reserva['estado_pago'] === 'Pagado') {
        $pagosSql = "
            UPDATE pagos 
            SET estado = 'Cancelado',
                motivo_reembolso = ?
            WHERE reserva_id = ? AND estado = 'Exitoso'
        ";
        $stmt = $db->prepare($pagosSql);
        $stmt->execute([$motivo, $reserva['id']]);
    }
    
    $db->commit();
    
    // Enviar email de confirmación de cancelación
    enviarEmailCancelacion($reserva, $motivo, $esCancelacionTardia);
    
    // Notificar al administrador
    notificarCancelacionAdmin($reserva, $motivo, $esCancelacionTardia);
    
    $mensaje = $esCancelacionTardia 
        ? 'Reserva cancelada. Nota: Cancelación tardía (menos de 24h antes del tour)'
        : 'Reserva cancelada correctamente';
    
    respuestaJSON(true, $mensaje, [
        'codigo_reserva' => $codigoReserva,
        'estado' => 'Cancelada',
        'cancelacion_tardia' => $esCancelacionTardia,
        'cupos_liberados' => $reserva['total_personas']
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    logError("Error al cancelar reserva: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}

/**
 * Enviar email de confirmación de cancelación
 */
function enviarEmailCancelacion($reserva, $motivo, $esCancelacionTardia) {
    $asunto = "Reserva cancelada - {$reserva['codigo_reserva']}";
    
    $fechaFormateada = date('d/m/Y', strtotime($reserva['fecha_tour']));
    
    $mensaje = "
    <h2>Reserva cancelada</h2>
    <p>Estimado/a {$reserva['cliente_nombre']},</p>
    
    <p>Tu reserva ha sido cancelada según tu solicitud.</p>
    
    <h3>Detalles de la reserva cancelada:</h3>
    <ul>
        <li><strong>Código de reserva:</strong> {$reserva['codigo_reserva']}</li>
        <li><strong>Tour:</strong> {$reserva['tour_nombre']}</li>
        <li><strong>Fecha:</strong> {$fechaFormateada}</li>
        <li><strong>Total:</strong> " . formatearPrecio($reserva['total']) . "</li>
        <li><strong>Motivo:</strong> {$motivo}</li>
    </ul>";
    
    if ($esCancelacionTardia) {
        $mensaje .= "
        <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>
            <strong>Nota importante:</strong> Esta cancelación se realizó con menos de 24 horas de anticipación.
            Dependiendo de nuestras políticas, esto podría afectar futuros reembolsos o reservas.
        </div>";
    }
    
    if ($reserva['estado_pago'] === 'Pagado') {
        $mensaje .= "
        <p><strong>Reembolso:</strong> El proceso de reembolso se iniciará en las próximas 24-48 horas.
        Recibirás una notificación cuando el reembolso sea procesado.</p>";
    }
    
    $mensaje .= "
    <p>Si tienes alguna pregunta sobre esta cancelación, no dudes en contactarnos.</p>
    
    <p>Esperamos poder servirte en el futuro.</p>
    
    <p>Saludos cordiales,<br>Equipo de " . EMPRESA_NOMBRE . "</p>
    ";
    
    enviarEmail($reserva['cliente_email'], $asunto, $mensaje);
}

/**
 * Notificar cancelación al administrador
 */
function notificarCancelacionAdmin($reserva, $motivo, $esCancelacionTardia) {
    $asunto = "Reserva cancelada - {$reserva['codigo_reserva']}";
    
    $mensaje = "
    <h2>Reserva cancelada por cliente</h2>
    
    <h3>Información de la reserva:</h3>
    <ul>
        <li><strong>Código:</strong> {$reserva['codigo_reserva']}</li>
        <li><strong>Tour:</strong> {$reserva['tour_nombre']}</li>
        <li><strong>Fecha tour:</strong> " . date('d/m/Y', strtotime($reserva['fecha_tour'])) . "</li>
        <li><strong>Cliente:</strong> {$reserva['cliente_nombre']} {$reserva['cliente_apellido']}</li>
        <li><strong>Email:</strong> {$reserva['cliente_email']}</li>
        <li><strong>Total:</strong> " . formatearPrecio($reserva['total']) . "</li>
        <li><strong>Estado pago:</strong> {$reserva['estado_pago']}</li>
        <li><strong>Personas:</strong> {$reserva['total_personas']}</li>
        <li><strong>Motivo:</strong> {$motivo}</li>
        <li><strong>Cancelación tardía:</strong> " . ($esCancelacionTardia ? 'SÍ' : 'NO') . "</li>
    </ul>
    
    <p><strong>Cupos liberados:</strong> {$reserva['total_personas']} personas</p>";
    
    if ($reserva['estado_pago'] === 'Pagado') {
        $mensaje .= "<p><strong>Acción requerida:</strong> Procesar reembolso</p>";
    }
    
    enviarEmail(ADMIN_EMAIL, $asunto, $mensaje);
}
?>
