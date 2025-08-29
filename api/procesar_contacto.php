<?php
/**
 * PROCESADOR DE FORMULARIO DE CONTACTO
 * ===================================
 * 
 * Maneja el formulario "Envíanos un mensaje" y guarda los datos en la base de datos
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

require_once '../config/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON(false, 'Método no permitido');
}

// Obtener y limpiar datos del formulario
$nombre = limpiarDatos($_POST['nombre'] ?? '');
$email = limpiarDatos($_POST['email'] ?? '');
$telefono = limpiarDatos($_POST['telefono'] ?? '');
$pais = limpiarDatos($_POST['pais'] ?? '');
$fechaViaje = !empty($_POST['fechaViaje']) ? $_POST['fechaViaje'] : null;
$personas = limpiarDatos($_POST['personas'] ?? '');
$tourInteres = limpiarDatos($_POST['tourInteres'] ?? '');
$mensaje = limpiarDatos($_POST['mensaje'] ?? '');
$newsletter = isset($_POST['newsletter']) ? 1 : 0;

// Mapear tour_interes de string a ID si es necesario
$tourInteresId = null;
if (!empty($tourInteres)) {
    // Si es un string, convertir a valor apropiado para la base de datos
    switch($tourInteres) {
        case 'rio-amazonas':
            $tourInteresId = 1;
            break;
        case 'safari-nocturno':
            $tourInteresId = 2;
            break;
        case 'comunidades-nativas':
            $tourInteresId = 3;
            break;
        case 'aventura-extrema':
            $tourInteresId = 4;
            break;
        case 'tour-personalizado':
            $tourInteresId = 5;
            break;
        default:
            $tourInteresId = null;
    }
}

// Validaciones básicas
$errores = [];

if (empty($nombre)) {
    $errores[] = 'El nombre es obligatorio';
}

if (empty($email) || !validarEmail($email)) {
    $errores[] = 'Por favor ingrese un email válido';
}

if (empty($mensaje)) {
    $errores[] = 'El mensaje es obligatorio';
}

if (!empty($errores)) {
    respuestaJSON(false, 'Errores de validación', $errores);
}

try {
    $db = getDB();
    
    // Verificar si el tour existe (si se especificó)
    if ($tourInteresId) {
        $stmt = $db->prepare("SELECT id, nombre FROM tours WHERE id = ? AND estado = 'Activo'");
        $stmt->execute([$tourInteresId]);
        $tour = $stmt->fetch();
        
        if (!$tour) {
            respuestaJSON(false, 'El tour seleccionado no existe o no está disponible');
        }
    }
    
    // Insertar contacto en la base de datos
    $sql = "INSERT INTO contactos (
        nombre, email, telefono, mensaje, 
        tour_interes, fecha_preferida, num_personas, origen_pais, newsletter,
        ip_address, user_agent, fecha_envio
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    $resultado = $stmt->execute([
        $nombre,
        $email,
        $telefono,
        $mensaje,
        $tourInteresId,
        $fechaViaje,
        $personas,
        $pais,
        $newsletter,
        obtenerIPUsuario(),
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    if ($resultado) {
        $contactoId = $db->lastInsertId();
        
        // Enviar email de notificación al administrador
        $asuntoEmail = "🔔 Nuevo mensaje de contacto - " . EMPRESA_NOMBRE;
        $mensajeEmail = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 10px;'>
                    🌿 Nuevo mensaje recibido - Jaguar Expeditions
                </h2>
                
                <div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>📧 ID de contacto:</strong> {$contactoId}</p>
                    <p><strong>⏰ Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>
                </div>
                
                <h3 style='color: #155724;'>👤 Información del cliente:</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Nombre:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$nombre}</td></tr>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><a href='mailto:{$email}'>{$email}</a></td></tr>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Teléfono:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$telefono}</td></tr>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>País:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$pais}</td></tr>";
        
        if ($tourInteresId && isset($tour)) {
            $mensajeEmail .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>🎯 Tour de interés:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><span style='background: #fff3cd; padding: 3px 8px; border-radius: 3px;'>{$tour['nombre']}</span></td></tr>";
        } elseif (!empty($tourInteres)) {
            $mensajeEmail .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>🎯 Tour de interés:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><span style='background: #fff3cd; padding: 3px 8px; border-radius: 3px;'>{$tourInteres}</span></td></tr>";
        }
        
        if ($fechaViaje) {
            $mensajeEmail .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>📅 Fecha preferida:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$fechaViaje}</td></tr>";
        }
        
        if ($personas) {
            $mensajeEmail .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>👥 Personas:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$personas}</td></tr>";
        }
        
        if ($newsletter) {
            $mensajeEmail .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>📧 Newsletter:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><span style='color: #28a745;'>✅ Sí quiere recibir ofertas</span></td></tr>";
        }
        
        $mensajeEmail .= "</table>
                
                <h3 style='color: #155724; margin-top: 25px;'>💬 Mensaje del cliente:</h3>
                <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; font-style: italic;'>
                    {$mensaje}
                </div>
                
                <div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;'>
                    <h4 style='margin: 0; color: #1976d2;'>🚀 Próximos pasos:</h4>
                    <ul style='margin: 10px 0;'>
                        <li>Responder al cliente en las próximas 2-4 horas</li>
                        <li>Enviar información detallada del tour solicitado</li>
                        <li>Coordinar fecha y detalles específicos</li>
                    </ul>
                </div>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                <p style='font-size: 12px; color: #666; text-align: center;'>
                    <strong>IP:</strong> " . obtenerIPUsuario() . " | 
                    <strong>Enviado desde:</strong> " . SITE_URL . " | 
                    <strong>Sistema:</strong> Jaguar Expeditions
                </p>
            </div>
        </div>";
        
        // Enviar email de notificación al administrador (en modo desarrollo se simula)
        enviarEmail(ADMIN_EMAIL, $asuntoEmail, $mensajeEmail);
        
        // Enviar email de confirmación al cliente
        $asuntoCliente = "Hemos recibido tu mensaje - " . EMPRESA_NOMBRE;
        $mensajeCliente = "
        <h2>¡Gracias por contactarnos, {$nombre}!</h2>
        <p>Hemos recibido tu mensaje y nos pondremos en contacto contigo en las próximas 24 horas.</p>
        
        <h3>Resumen de tu consulta:</h3>";
        
        if ($tourInteresId && isset($tour)) {
            $mensajeCliente .= "<p><strong>Tour de interés:</strong> {$tour['nombre']}</p>";
        } elseif (!empty($tourInteres)) {
            $mensajeCliente .= "<p><strong>Tour de interés:</strong> {$tourInteres}</p>";
        }
        
        $mensajeCliente .= "
        <p><strong>Tu mensaje:</strong></p>
        <p>{$mensaje}</p>
        
        <hr>
        <p>Si tienes alguna pregunta urgente, no dudes en llamarnos al " . EMPRESA_TELEFONO . "</p>
        <p>Saludos cordiales,<br>Equipo de " . EMPRESA_NOMBRE . "</p>";
        
        enviarEmail($email, $asuntoCliente, $mensajeCliente);
        
        respuestaJSON(true, 'Mensaje enviado correctamente. Te contactaremos pronto.', [
            'contacto_id' => $contactoId
        ]);
        
    } else {
        throw new Exception('Error al guardar el mensaje');
    }
    
} catch (Exception $e) {
    logError("Error en formulario de contacto: " . $e->getMessage());
    respuestaJSON(false, 'Hubo un error al procesar tu mensaje. Por favor, inténtalo de nuevo.');
}
?>
