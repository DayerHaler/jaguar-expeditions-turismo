<?php
/**
 * PROCESADOR DE FORMULARIO DE CONTACTO - CON SEGURIDAD AVANZADA CONTRA INYECCIÓN SQL Y ATAQUES DE DÍA CERO
 * Jaguar Expeditions - Version 2.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// Headers de seguridad adicionales
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once 'JaguarSecurityPHP.php';

// Inicializar sistema de seguridad
try {
    $db = new PDO(
        "mysql:host=localhost;dbname=jaguar_expeditions;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    $security = new JaguarSecurityPHP($db);
} catch (Exception $e) {
    // Si falla la DB, usar seguridad básica
    $security = new JaguarSecurityPHP();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $security->logSecurityEvent('invalid_method', ['method' => $_SERVER['REQUEST_METHOD']]);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar si la IP está bloqueada
if ($security->isIPBlocked()) {
    echo json_encode([
        'success' => false, 
        'message' => 'Acceso temporalmente bloqueado por actividad sospechosa'
    ]);
    exit;
}

// Obtener datos del formulario
$datosRaw = [
    'nombre' => $_POST['nombre'] ?? '',
    'email' => $_POST['email'] ?? '',
    'telefono' => $_POST['telefono'] ?? '',
    'pais' => $_POST['pais'] ?? '',
    'fechaViaje' => $_POST['fechaViaje'] ?? '',
    'personas' => $_POST['personas'] ?? '',
    'tourInteres' => $_POST['tourInteres'] ?? '',
    'mensaje' => $_POST['mensaje'] ?? '',
    'newsletter' => isset($_POST['newsletter']) ? 1 : 0,
    'honeypot' => $_POST['honeypot'] ?? '',
    'form_start_time' => $_POST['form_start_time'] ?? null,
    'jaguar_csrf_token' => $_POST['jaguar_csrf_token'] ?? ''
];

// Validar formulario completo con sistema de seguridad avanzado
$validation = $security->validateForm($datosRaw, 'contact');

if (!$validation['valid']) {
    // Log de violación de seguridad
    $security->logSecurityEvent('contact_form_blocked', [
        'errors' => $validation['errors'],
        'threats' => $validation['threats'],
        'form_data_summary' => [
            'has_nombre' => !empty($datosRaw['nombre']),
            'has_email' => !empty($datosRaw['email']),
            'has_mensaje' => !empty($datosRaw['mensaje'])
        ]
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Formulario bloqueado por razones de seguridad',
        'errors' => $validation['errors']
    ]);
    exit;
}

// Usar datos validados y sanitizados
$datos = $validation['data'];

// Log de formulario válido
$security->logSecurityEvent('contact_form_validated', [
    'form_fields' => array_keys($datos),
    'has_tour_interest' => !empty($datos['tourInteres'])
]);

// Las validaciones específicas ya fueron hechas por el sistema de seguridad
// Los datos están completamente sanitizados y validados

try {
    // La conexión a DB ya está establecida en la inicialización de seguridad
    // Mapear tour_interes de string a ID si es necesario
    $tourInteresId = null;
    if (!empty($datos['tourInteres'])) {
        switch($datos['tourInteres']) {
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
        $datos['nombre'],
        $datos['email'],
        $datos['telefono'],
        $datos['mensaje'],
        $tourInteresId,
        $datos['fechaViaje'],
        $datos['personas'],
        $datos['pais'],
        $datos['newsletter'],
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    if ($resultado) {
        $contactoId = $db->lastInsertId();
        
        // Preparar datos para el email
        $datosEmail = [
            'id' => $contactoId,
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'pais' => $datos['pais'],
            'fecha_viaje' => $datos['fechaViaje'],
            'personas' => $datos['personas'],
            'tour_interes' => $tourInteresId,
            'tour_interes_texto' => $datos['tourInteres'],
            'mensaje' => $datos['mensaje'],
            'newsletter' => $datos['newsletter'],
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
        
        // Log de contacto exitoso
        $security->logSecurityEvent('contact_form_processed_successfully', [
            'contacto_id' => $contactoId,
            'tour_interes' => $datos['tourInteres'],
            'email_sent' => isset($emailAdmin) ? $emailAdmin : false
        ]);
        
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
    // Log de error de seguridad
    $security->logSecurityEvent('contact_form_processing_error', [
        'error_message' => $e->getMessage(),
        'form_data_summary' => [
            'has_nombre' => !empty($datosRaw['nombre']),
            'has_email' => !empty($datosRaw['email']),
            'has_mensaje' => !empty($datosRaw['mensaje'])
        ]
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Hubo un error al procesar tu mensaje. Por favor, inténtalo de nuevo.',
        'error_code' => 'PROCESSING_ERROR'
    ]);
}

// Asegurar que la respuesta se envíe inmediatamente
if (ob_get_level()) {
    ob_end_flush();
}
flush();
?>
