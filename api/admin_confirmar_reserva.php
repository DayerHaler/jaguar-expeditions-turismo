<?php
/**
 * API PARA CONFIRMAR RESERVA MANUALMENTE (ADMIN)
 * ===============================================
 * 
 * Endpoint para que el administrador confirme reservas manualmente
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
    
    // Verificar que la reserva se puede confirmar
    if ($reserva['estado_reserva'] !== 'Pendiente_Pago') {
        respuestaJSON(false, 'Solo se pueden confirmar reservas pendientes de pago');
    }
    
    // Actualizar estado de la reserva
    $updateSql = "
        UPDATE reservas 
        SET estado_reserva = 'Confirmada',
            estado_pago = 'Pagado',
            fecha_actualizacion = NOW()
        WHERE id = ?
    ";
    $stmt = $db->prepare($updateSql);
    $stmt->execute([$reserva['id']]);
    
    // Crear registro de pago manual
    $pagoSql = "
        INSERT INTO pagos (
            reserva_id, metodo_pago, estado, monto, 
            fecha_pago, transaction_id, notas
        ) VALUES (?, ?, ?, ?, NOW(), ?, ?)
    ";
    $transactionId = 'MANUAL_' . time();
    $stmt = $db->prepare($pagoSql);
    $stmt->execute([
        $reserva['id'],
        'manual',
        'Exitoso',
        $reserva['total'],
        $transactionId,
        'Confirmación manual por administrador'
    ]);
    
    $db->commit();
    
    // Enviar email de confirmación
    enviarEmailConfirmacionAdmin($reserva);
    
    respuestaJSON(true, 'Reserva confirmada correctamente', [
        'codigo_reserva' => $codigoReserva,
        'estado' => 'Confirmada',
        'transaction_id' => $transactionId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    logError("Error al confirmar reserva admin: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}

/**
 * Enviar email de confirmación
 */
function enviarEmailConfirmacionAdmin($reserva) {
    $asunto = "Reserva confirmada - {$reserva['codigo_reserva']}";
    
    $fechaFormateada = date('d/m/Y', strtotime($reserva['fecha_tour']));
    
    $mensaje = "
    <h2>¡Reserva confirmada!</h2>
    <p>Estimado/a {$reserva['cliente_nombre']},</p>
    
    <p>Nos complace informarte que tu reserva ha sido confirmada por nuestro equipo.</p>
    
    <h3>Detalles de tu reserva:</h3>
    <ul>
        <li><strong>Código de reserva:</strong> {$reserva['codigo_reserva']}</li>
        <li><strong>Tour:</strong> {$reserva['tour_nombre']}</li>
        <li><strong>Fecha:</strong> {$fechaFormateada}</li>
        <li><strong>Adultos:</strong> {$reserva['num_adultos']}</li>
        <li><strong>Niños:</strong> {$reserva['num_ninos']}</li>
        <li><strong>Total:</strong> " . formatearPrecio($reserva['total']) . "</li>
    </ul>
    
    <div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>
        <p style='margin: 0; color: #155724;'>
            <strong>Estado:</strong> Confirmada y pagada ✓
        </p>
    </div>
    
    <h3>Próximos pasos:</h3>
    <ul>
        <li>Recibirás más detalles del tour 24-48 horas antes de la fecha</li>
        <li>Incluiremos punto de encuentro y horarios exactos</li>
        <li>Para cualquier consulta, contáctanos con tu código de reserva</li>
    </ul>
    
    <p>¡Esperamos verte pronto en esta increíble aventura!</p>
    
    <p>Saludos cordiales,<br>Equipo de " . EMPRESA_NOMBRE . "</p>
    ";
    
    enviarEmail($reserva['cliente_email'], $asunto, $mensaje);
}
?>
