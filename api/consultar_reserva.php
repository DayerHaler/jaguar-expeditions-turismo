<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuración de base de datos
$host = 'localhost';
$dbname = 'jaguar_expeditions';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit();
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos de entrada
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$codigo_reserva = trim($input['codigo_reserva'] ?? '');
$email = trim($input['email'] ?? '');

// Validar datos requeridos
if (empty($codigo_reserva)) {
    echo json_encode(['success' => false, 'message' => 'El código de reserva es requerido']);
    exit();
}

try {
    // Consulta simplificada para obtener información básica de la reserva
    $query = "
        SELECT 
            r.reserva_id,
            r.codigo_reserva,
            r.fecha_tour,
            r.num_personas,
            r.precio_por_persona,
            r.subtotal,
            r.descuento,
            r.impuestos,
            r.total,
            r.estado_reserva,
            r.tipo_pago,
            r.notas,
            r.fecha_creacion,
            r.fecha_actualizacion,
            
            -- Información del cliente
            c.nombre as cliente_nombre,
            c.apellido as cliente_apellido,
            c.email as cliente_email,
            c.celular as cliente_celular,
            c.documento as cliente_documento,
            c.tipo_documento as cliente_tipo_documento,
            c.edad as cliente_edad,
            c.pais as cliente_pais
            
        FROM reservas r
        INNER JOIN clientes c ON r.cliente_id = c.cliente_id
        WHERE r.codigo_reserva = ?
    ";
    
    $params = [$codigo_reserva];
    
    // Si se proporciona email, agregar validación adicional
    if (!empty($email)) {
        $query .= " AND c.email = ?";
        $params[] = $email;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        echo json_encode([
            'success' => false, 
            'message' => 'No se encontró ninguna reserva con el código proporcionado' . 
                        (!empty($email) ? ' y email especificado' : '')
        ]);
        exit();
    }
    
    // Obtener participantes de la reserva
    $stmt_participantes = $pdo->prepare("
        SELECT 
            nombre, apellido, email, celular, documento, tipo_documento, 
            edad, pais, direccion, descripcion
        FROM participantes_reserva 
        WHERE reserva_id = ? AND estado = 'Activo'
        ORDER BY participante_id
    ");
    $stmt_participantes->execute([$reserva['reserva_id']]);
    $participantes = $stmt_participantes->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar información de pagos
    $pagos = [];
    $total_pagado = 0;
    $tiene_pago_completado = false;
    $tiene_pago_pendiente = false;
    
    if (!empty($reserva['pagos_info'])) {
        $pagos_raw = explode('||', $reserva['pagos_info']);
        foreach ($pagos_raw as $pago_raw) {
            $pago_data = explode(':', $pago_raw);
            if (count($pago_data) >= 4) {
                $pago = [
                    'pago_id' => $pago_data[0],
                    'monto' => floatval($pago_data[1]),
                    'metodo_pago' => $pago_data[2],
                    'estado' => $pago_data[3],
                    'codigo_transaccion' => $pago_data[4] ?? '',
                    'fecha_pago' => $pago_data[5] ?? ''
                ];
                
                $pagos[] = $pago;
                
                if ($pago['estado'] === 'Completado') {
                    $total_pagado += $pago['monto'];
                    $tiene_pago_completado = true;
                } elseif ($pago['estado'] === 'Pendiente') {
                    $tiene_pago_pendiente = true;
                }
            }
        }
    }
    
    // Determinar estado de pago y acciones disponibles
    $total_reserva = floatval($reserva['total']);
    $monto_restante = $total_reserva - $total_pagado;
    $pago_completo = ($monto_restante <= 0.01); // Considerar pequeñas diferencias de redondeo
    
    // Determinar acciones disponibles
    $acciones_disponibles = [];
    
    // Determinar si puede pagar segunda cuota
    $puede_pagar_segunda_cuota = false;
    $es_pago_por_cuotas = ($reserva['tipo_pago'] === 'Cuotas');
    
    if ($es_pago_por_cuotas && !$pago_completo && $tiene_pago_completado) {
        $puede_pagar_segunda_cuota = true;
        $acciones_disponibles[] = 'pagar_segunda_cuota';
    }
    
    // Determinar si puede pagar primera cuota o pago completo
    if (!$tiene_pago_completado && !$pago_completo) {
        if ($es_pago_por_cuotas) {
            $acciones_disponibles[] = 'pagar_primera_cuota';
        } else {
            $acciones_disponibles[] = 'pagar_completo';
        }
    }
    
    // Determinar si puede solicitar reembolso
    if ($tiene_pago_completado && $reserva['estado_reserva'] !== 'Cancelada') {
        $acciones_disponibles[] = 'solicitar_reembolso';
    }
    
    // Determinar si puede cancelar
    if ($reserva['estado_reserva'] === 'Pendiente' || $reserva['estado_reserva'] === 'Confirmada') {
        $fecha_tour = new DateTime($reserva['fecha_tour']);
        $hoy = new DateTime();
        $diferencia = $hoy->diff($fecha_tour);
        
        if ($fecha_tour > $hoy) {
            $acciones_disponibles[] = 'cancelar_reserva';
        }
    }
    
    // Calcular tiempo límite para pago (24 horas desde creación si está pendiente)
    $tiempo_limite = null;
    if ($reserva['estado_reserva'] === 'Pendiente' && !$pago_completo) {
        $fecha_creacion = new DateTime($reserva['fecha_creacion']);
        $fecha_limite = $fecha_creacion->add(new DateInterval('P1D')); // Agregar 1 día
        $tiempo_limite = $fecha_limite->format('Y-m-d H:i:s');
    }
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'reserva' => [
            'reserva_id' => $reserva['reserva_id'],
            'codigo_reserva' => $reserva['codigo_reserva'],
            'estado' => strtolower($reserva['estado_reserva']),
            'estado_display' => $reserva['estado_reserva'],
            
            // Información del tour
            'tour_nombre' => $reserva['tour_nombre'],
            'tour_descripcion' => $reserva['tour_descripcion'],
            'tour_duracion' => $reserva['tour_duracion'],
            'tour_ubicacion' => $reserva['tour_ubicacion'],
            'fecha_tour' => $reserva['fecha_tour'],
            
            // Información de participantes
            'numero_personas' => intval($reserva['num_personas']),
            'precio_por_persona' => floatval($reserva['precio_por_persona']),
            'subtotal' => floatval($reserva['subtotal']),
            'descuento' => floatval($reserva['descuento']),
            'impuestos' => floatval($reserva['impuestos']),
            'monto_total' => $total_reserva,
            
            // Información del cliente principal
            'cliente_nombre' => $reserva['cliente_nombre'],
            'cliente_apellido' => $reserva['cliente_apellido'],
            'cliente_email' => $reserva['cliente_email'],
            'cliente_celular' => $reserva['cliente_celular'],
            'cliente_documento' => $reserva['cliente_documento'],
            'cliente_tipo_documento' => $reserva['cliente_tipo_documento'],
            
            // Información de pago
            'tipo_pago' => $reserva['tipo_pago'],
            'total_pagado' => $total_pagado,
            'monto_restante' => $monto_restante,
            'pago_completo' => $pago_completo,
            'es_pago_por_cuotas' => $es_pago_por_cuotas,
            'puede_pagar_segunda_cuota' => $puede_pagar_segunda_cuota,
            
            // Participantes adicionales
            'participantes' => $participantes,
            'tiene_participantes' => (count($participantes) > 0),
            
            // Historial de pagos
            'pagos' => $pagos,
            'historial_pagos' => $pagos,
            
            // Acciones y tiempo límite
            'acciones_disponibles' => $acciones_disponibles,
            'tiempo_limite' => $tiempo_limite,
            
            // Metadatos
            'fecha_creacion' => $reserva['fecha_creacion'],
            'fecha_actualizacion' => $reserva['fecha_actualizacion'],
            'notas' => $reserva['notas']
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al consultar la reserva: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error inesperado: ' . $e->getMessage()
    ]);
}
?>
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
