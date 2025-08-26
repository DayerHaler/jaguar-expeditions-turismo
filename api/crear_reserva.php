<?php
/**
 * API PARA CREAR RESERVAS
 * =======================
 * 
 * Endpoint para crear nuevas reservas de tours
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Ocultar errores para evitar que se muestren en la respuesta JSON
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON(false, 'Método no permitido');
}

// Obtener datos
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    respuestaJSON(false, 'Error al decodificar JSON: ' . json_last_error_msg());
}

if (!$data) {
    respuestaJSON(false, 'Datos inválidos o vacíos');
}

// Validar datos obligatorios
$tourId = $data['tour_id'] ?? null;
$fechaTour = $data['fecha_tour'] ?? '';
$emailContacto = $data['email_contacto'] ?? '';
$telefonoContacto = $data['telefono_contacto'] ?? '';
$numClientes = $data['num_clientes'] ?? 0;
$clientes = $data['clientes'] ?? [];
$comentarios = $data['comentarios'] ?? '';
$tipoProceso = $data['tipo_proceso'] ?? 'reserva'; // 'reserva' o 'reserva_y_pago'

if (!$tourId || !$fechaTour || !$emailContacto || !$telefonoContacto || $numClientes < 1 || empty($clientes)) {
    respuestaJSON(false, 'Datos obligatorios faltantes');
}

// Validar que el número de clientes coincida con los datos enviados
if (count($clientes) !== $numClientes) {
    respuestaJSON(false, 'El número de clientes no coincide con los datos enviados');
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Verificar que el tour existe y está activo
    $stmt = $db->prepare("SELECT * FROM tours WHERE id = ? AND estado = 'Activo'");
    $stmt->execute([$tourId]);
    $tour = $stmt->fetch();
    
    if (!$tour) {
        respuestaJSON(false, 'Tour no encontrado o no disponible');
    }
    
    // Verificar disponibilidad para la fecha
    $stmt = $db->prepare("
        SELECT * FROM disponibilidad_tours 
        WHERE tour_id = ? AND fecha = ? AND estado = 'Disponible'
    ");
    $stmt->execute([$tourId, $fechaTour]);
    $disponibilidad = $stmt->fetch();
    
    if (!$disponibilidad) {
        // Si no hay registro de disponibilidad, crear uno con valores por defecto
        $stmt = $db->prepare("
            INSERT INTO disponibilidad_tours (tour_id, fecha, cupos_disponibles, estado)
            VALUES (?, ?, ?, 'Disponible')
        ");
        $stmt->execute([$tourId, $fechaTour, $tour['max_personas']]);
        
        $disponibilidad = [
            'cupos_disponibles' => $tour['max_personas'],
            'cupos_reservados' => 0
        ];
    }
    
    // Verificar que hay cupos disponibles
    $cuposLibres = $disponibilidad['cupos_disponibles'] - $disponibilidad['cupos_reservados'];
    
    if ($cuposLibres < $numClientes) {
        respuestaJSON(false, "Solo quedan {$cuposLibres} cupos disponibles para esta fecha");
    }
    
    // Calcular precios
    $precioTotal = $numClientes * floatval($tour['precio']);
    
    // Aplicar descuento por pronto pago si aplica
    $descuento = 0;
    if ($tipoProceso === 'reserva_y_pago') {
        $descuento = $precioTotal * 0.05; // 5% descuento por pagar inmediatamente
    }
    
    $subtotal = $precioTotal;
    $impuestos = ($subtotal - $descuento) * 0.18; // 18% IGV
    $total = $subtotal - $descuento + $impuestos;
    
    // Generar código único de reserva
    $codigoReserva = generarCodigoReserva();
    
    // Verificar que el código no existe
    $stmt = $db->prepare("SELECT id FROM reservas WHERE codigo_reserva = ?");
    $stmt->execute([$codigoReserva]);
    if ($stmt->fetch()) {
        // Si existe, generar otro
        $codigoReserva = generarCodigoReserva();
    }
    
    // Determinar estado inicial
    $estadoReserva = ($tipoProceso === 'reserva_y_pago') ? 'Confirmada' : 'Pendiente_Pago';
    $estadoPago = 'Pendiente';
    
    // Establecer tiempo límite para pago (24 horas)
    $tiempoLimitePago = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Obtener datos del cliente principal (primer cliente)
    $clientePrincipal = $clientes[0];
    
    // Insertar reserva principal
    $sql = "INSERT INTO reservas (
        codigo_reserva, tour_id, cliente_nombre, cliente_apellido, cliente_email,
        cliente_telefono, fecha_tour, num_clientes, precio_unitario, subtotal, 
        descuento, impuestos, total, estado_reserva, estado_pago, 
        comentarios_especiales, ip_address, user_agent
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $resultado = $stmt->execute([
        $codigoReserva,
        $tourId,
        limpiarDatos($clientePrincipal['nombre']),
        limpiarDatos($clientePrincipal['apellido'] ?? ''), // apellido separado
        limpiarDatos($clientePrincipal['email'] ?? $emailContacto),
        limpiarDatos($telefonoContacto),
        $fechaTour,
        $numClientes,
        floatval($tour['precio']), // precio_unitario
        $subtotal, // subtotal
        $descuento, // descuento
        $impuestos, // impuestos
        $total, // total
        $estadoReserva,
        $estadoPago,
        limpiarDatos($comentarios),
        obtenerIPUsuario(),
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    if (!$resultado) {
        throw new Exception('Error al crear la reserva');
    }
    
    $reservaId = $db->lastInsertId();
    
    // Insertar datos individuales de cada cliente
    $sqlCliente = "INSERT INTO clientes_reserva (
        reserva_id, nombre, apellido, documento, edad, genero, pais, celular, celular_contacto, email, comentarios
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmtCliente = $db->prepare($sqlCliente);
    
    foreach ($clientes as $index => $cliente) {
        // Dividir nombre completo en nombre y apellido
        $nombreCompleto = trim($cliente['nombre']);
        $partesNombre = explode(' ', $nombreCompleto, 2);
        $nombre = $partesNombre[0];
        $apellido = isset($partesNombre[1]) ? $partesNombre[1] : '';
        
        $stmtCliente->execute([
            $reservaId,
            limpiarDatos($nombre),
            limpiarDatos($apellido),
            limpiarDatos($cliente['documento']),
            intval($cliente['edad']),
            limpiarDatos($cliente['genero'] ?? null),
            limpiarDatos($cliente['pais'] ?? null),
            limpiarDatos($cliente['celular'] ?? ''),
            limpiarDatos($cliente['celular_contacto'] ?? ''),
            limpiarDatos($cliente['email'] ?? ($index === 0 ? $emailContacto : '')),
            limpiarDatos($cliente['observaciones'] ?? '')
        ]);
    }
    
    // Actualizar disponibilidad (esto se hace automáticamente con el trigger)
    // Pero lo hacemos manualmente por si acaso
    $stmt = $db->prepare("
        UPDATE disponibilidad_tours 
        SET cupos_reservados = cupos_reservados + ?
        WHERE tour_id = ? AND fecha = ?
    ");
    $stmt->execute([$numClientes, $tourId, $fechaTour]);
    
    $db->commit();
    
    // Enviar email según el tipo de proceso
    if ($tipoProceso === 'reserva') {
        enviarEmailReservaTemporal($cliente, $tour, $codigoReserva, $fechaTour, $total);
    } else {
        // Para reserva_y_pago, se enviará el email después del procesamiento del pago
    }
    
    // Notificar al administrador
    notificarNuevaReserva($reservaId, $codigoReserva, $tour['nombre'], $cliente);
    
    $mensaje = ($tipoProceso === 'reserva') 
        ? 'Reserva creada correctamente. Tienes 24 horas para completar el pago.'
        : 'Reserva creada correctamente. Procede con el pago para confirmar.';
    
    respuestaJSON(true, $mensaje, [
        'reserva_id' => $reservaId,
        'codigo_reserva' => $codigoReserva,
        'tipo_proceso' => $tipoProceso,
        'estado_reserva' => $estadoReserva,
        'subtotal' => $subtotal,
        'descuento' => $descuento,
        'impuestos' => $impuestos,
        'total' => $total,
        'tour' => [
            'id' => $tour['id'],
            'nombre' => $tour['nombre'],
            'fecha' => $fechaTour
        ],
        'tiempo_limite_pago' => ($tipoProceso === 'reserva') ? date('Y-m-d H:i:s', strtotime('+24 hours')) : null
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    logError("Error al crear reserva: " . $e->getMessage());
    respuestaJSON(false, 'Error al procesar la reserva: ' . $e->getMessage());
}

/**
 * Enviar email de confirmación temporal (antes del pago)
 */
function enviarEmailReservaTemporal($cliente, $tour, $codigoReserva, $fecha, $total) {
    $asunto = "Reserva temporal creada - {$codigoReserva}";
    
    $fechaFormateada = date('d/m/Y', strtotime($fecha));
    
    $mensaje = "
    <h2>Reserva temporal creada</h2>
    <p>Estimado/a {$cliente['nombre']},</p>
    
    <p>Hemos recibido tu solicitud de reserva. Para confirmarla, debes completar el pago.</p>
    
    <h3>Detalles de la reserva:</h3>
    <ul>
        <li><strong>Código de reserva:</strong> {$codigoReserva}</li>
        <li><strong>Tour:</strong> {$tour['nombre']}</li>
        <li><strong>Fecha:</strong> {$fechaFormateada}</li>
        <li><strong>Total a pagar:</strong> " . formatearPrecio($total) . "</li>
    </ul>
    
    <p><strong>IMPORTANTE:</strong> Esta reserva será cancelada automáticamente si no se completa el pago en las próximas 24 horas.</p>
    
    <p>Si tienes alguna pregunta, no dudes en contactarnos al " . EMPRESA_TELEFONO . "</p>
    
    <p>Saludos cordiales,<br>Equipo de " . EMPRESA_NOMBRE . "</p>
    ";
    
    enviarEmail($cliente['email'], $asunto, $mensaje);
}

/**
 * Notificar nueva reserva al administrador
 */
function notificarNuevaReserva($reservaId, $codigoReserva, $tourNombre, $cliente) {
    $asunto = "Nueva reserva pendiente - {$codigoReserva}";
    
    $mensaje = "
    <h2>Nueva reserva recibida</h2>
    
    <h3>Información de la reserva:</h3>
    <ul>
        <li><strong>ID:</strong> {$reservaId}</li>
        <li><strong>Código:</strong> {$codigoReserva}</li>
        <li><strong>Tour:</strong> {$tourNombre}</li>
        <li><strong>Cliente:</strong> {$cliente['nombre']} {$cliente['apellido']}</li>
        <li><strong>Email:</strong> {$cliente['email']}</li>
        <li><strong>Teléfono:</strong> {$cliente['telefono']}</li>
        <li><strong>País:</strong> {$cliente['pais']}</li>
    </ul>
    
    <p>La reserva está pendiente de pago.</p>
    
    <p>IP del cliente: " . obtenerIPUsuario() . "</p>
    ";
    
    enviarEmail(ADMIN_EMAIL, $asunto, $mensaje);
}
?>
