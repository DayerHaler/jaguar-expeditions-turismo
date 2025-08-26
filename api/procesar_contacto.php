<?php
/**
 * PROCESADOR DE FORMULARIO DE CONTACTO
 * ===================================
 * 
 * Maneja el formulario "Envíanos un mensaje" y guarda los datos en la base de datos
 */

require_once '../config/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON(false, 'Método no permitido');
}

// Obtener y limpiar datos del formulario
$nombre = limpiarDatos($_POST['nombre'] ?? '');
$email = limpiarDatos($_POST['email'] ?? '');
$telefono = limpiarDatos($_POST['telefono'] ?? '');
$asunto = limpiarDatos($_POST['asunto'] ?? '');
$mensaje = limpiarDatos($_POST['mensaje'] ?? '');
$tourInteres = !empty($_POST['tour_interes']) ? (int)$_POST['tour_interes'] : null;
$fechaPreferida = !empty($_POST['fecha_preferida']) ? $_POST['fecha_preferida'] : null;
$numPersonas = !empty($_POST['num_personas']) ? (int)$_POST['num_personas'] : null;
$origenPais = limpiarDatos($_POST['origen_pais'] ?? '');

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
    if ($tourInteres) {
        $stmt = $db->prepare("SELECT id, nombre FROM tours WHERE id = ? AND estado = 'Activo'");
        $stmt->execute([$tourInteres]);
        $tour = $stmt->fetch();
        
        if (!$tour) {
            respuestaJSON(false, 'El tour seleccionado no existe o no está disponible');
        }
    }
    
    // Insertar contacto en la base de datos
    $sql = "INSERT INTO contactos (
        nombre, email, telefono, asunto, mensaje, 
        tour_interes, fecha_preferida, num_personas, origen_pais,
        ip_address, user_agent, fecha_envio
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    $resultado = $stmt->execute([
        $nombre,
        $email,
        $telefono,
        $asunto,
        $mensaje,
        $tourInteres,
        $fechaPreferida,
        $numPersonas,
        $origenPais,
        obtenerIPUsuario(),
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    if ($resultado) {
        $contactoId = $db->lastInsertId();
        
        // Enviar email de notificación al administrador
        $asuntoEmail = "Nuevo mensaje de contacto - " . EMPRESA_NOMBRE;
        $mensajeEmail = "
        <h2>Nuevo mensaje recibido</h2>
        <p><strong>ID de contacto:</strong> {$contactoId}</p>
        <p><strong>Nombre:</strong> {$nombre}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Teléfono:</strong> {$telefono}</p>
        <p><strong>Asunto:</strong> {$asunto}</p>
        <p><strong>País de origen:</strong> {$origenPais}</p>";
        
        if ($tourInteres && isset($tour)) {
            $mensajeEmail .= "<p><strong>Tour de interés:</strong> {$tour['nombre']}</p>";
        }
        
        if ($fechaPreferida) {
            $mensajeEmail .= "<p><strong>Fecha preferida:</strong> {$fechaPreferida}</p>";
        }
        
        if ($numPersonas) {
            $mensajeEmail .= "<p><strong>Número de personas:</strong> {$numPersonas}</p>";
        }
        
        $mensajeEmail .= "
        <p><strong>Mensaje:</strong></p>
        <p>{$mensaje}</p>
        <hr>
        <p><small>IP: " . obtenerIPUsuario() . " | Fecha: " . date('Y-m-d H:i:s') . "</small></p>";
        
        enviarEmail(ADMIN_EMAIL, $asuntoEmail, $mensajeEmail);
        
        // Enviar email de confirmación al cliente
        $asuntoCliente = "Hemos recibido tu mensaje - " . EMPRESA_NOMBRE;
        $mensajeCliente = "
        <h2>¡Gracias por contactarnos, {$nombre}!</h2>
        <p>Hemos recibido tu mensaje y nos pondremos en contacto contigo en las próximas 24 horas.</p>
        
        <h3>Resumen de tu consulta:</h3>
        <p><strong>Asunto:</strong> {$asunto}</p>";
        
        if ($tourInteres && isset($tour)) {
            $mensajeCliente .= "<p><strong>Tour de interés:</strong> {$tour['nombre']}</p>";
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
