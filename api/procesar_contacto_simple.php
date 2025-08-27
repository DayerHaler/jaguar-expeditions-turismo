<?php
/**
 * PROCESADOR DE FORMULARIO DE CONTACTO - VERSIÓN SIMPLIFICADA
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Función para limpiar datos
function limpiarDatos($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Función para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
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
    echo json_encode([
        'success' => false,
        'message' => 'Errores de validación',
        'errors' => $errores
    ]);
    exit;
}

try {
    // Conexión a la base de datos
    $db = new PDO(
        "mysql:host=localhost;dbname=jaguar_expeditions;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Mapear tour_interes de string a ID si es necesario
    $tourInteresId = null;
    if (!empty($tourInteres)) {
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
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    if ($resultado) {
        $contactoId = $db->lastInsertId();
        
        // Preparar datos para el email
        $datosEmail = [
            'id' => $contactoId,
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'pais' => $pais,
            'fecha_viaje' => $fechaViaje,
            'personas' => $personas,
            'tour_interes' => $tourInteresId,
            'tour_interes_texto' => $tourInteres,
            'mensaje' => $mensaje,
            'newsletter' => $newsletter,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];
        
        // Enviar emails usando el servicio de email
        try {
            require_once __DIR__ . '/EmailService.php';
            $emailService = new EmailService();
            
            // Enviar notificación al administrador
            $emailAdmin = $emailService->enviarNotificacionContacto($datosEmail);
            
            // Enviar confirmación al cliente
            $emailCliente = $emailService->enviarConfirmacionCliente($datosEmail);
            
            // Log del resultado de emails
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] Contacto ID: {$contactoId} - Email admin: " . ($emailAdmin ? 'OK' : 'FALLO') . " - Email cliente: " . ($emailCliente ? 'OK' : 'FALLO') . "\n";
            @file_put_contents(__DIR__ . '/logs/email_status.log', $logMessage, FILE_APPEND | LOCK_EX);
            
            $mensajeRespuesta = 'Mensaje enviado correctamente. Te contactaremos pronto.';
            if (!$emailAdmin || !$emailCliente) {
                $mensajeRespuesta .= ' (Nota: Algunos emails pueden haber fallado, pero tu mensaje fue guardado)';
            }
            
        } catch (Exception $emailError) {
            // Log del error específico
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] Error en envío de emails para contacto {$contactoId}: " . $emailError->getMessage() . "\n";
            @file_put_contents(__DIR__ . '/logs/email_errors.log', $logMessage, FILE_APPEND | LOCK_EX);
            
            // Si falla el email, aún así devolver éxito porque el contacto se guardó
            $mensajeRespuesta = 'Mensaje guardado correctamente. Te contactaremos pronto.';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $mensajeRespuesta,
            'data' => [
                'contacto_id' => $contactoId
            ]
        ]);
        
    } else {
        throw new Exception('Error al guardar el mensaje');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hubo un error al procesar tu mensaje. Por favor, inténtalo de nuevo.'
    ]);
}
?>
