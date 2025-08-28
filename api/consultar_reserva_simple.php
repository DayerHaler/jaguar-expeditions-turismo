<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    // Obtener información completa de la reserva
    $query = "
        SELECT 
            r.*,
            CONCAT(c.nombre, ' ', c.apellido) as nombre_completo,
            c.email,
            c.celular,
            c.celular_contacto,
            t.id as tour_id,
            t.nombre as tour_nombre,
            t.descripcion as tour_descripcion,
            t.duracion,
            t.precio as precio_base
        FROM reservas r
        INNER JOIN clientes c ON r.cliente_id = c.cliente_id
        INNER JOIN tours t ON r.tour_id = t.id
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

    // Analizar pagos según el tipo de pago
    $info_pagos = [];
    
    if ($reserva['tipo_pago'] === 'Completo') {
        // Para pagos completos, verificar en la tabla pagos
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as num_pagos,
                IFNULL(SUM(monto_total), 0) as total_pagado,
                MAX(estado_pago) as ultimo_estado
            FROM pagos 
            WHERE reserva_id = ? AND estado_pago = 'Completado'
        ");
        $stmt->execute([$reserva['reserva_id']]);
        $pago_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_pagado = floatval($pago_info['total_pagado']);
        $esta_completamente_pagado = $pago_info['num_pagos'] > 0 && $total_pagado >= floatval($reserva['total']);
        
        $info_pagos = [
            'tipo_pago' => 'Completo',
            'total_pagado' => $total_pagado,
            'saldo_pendiente' => floatval($reserva['total']) - $total_pagado,
            'esta_completamente_pagado' => $esta_completamente_pagado,
            'cuotas_pagadas' => $pago_info['num_pagos'],
            'total_cuotas_esperadas' => 1,
            'puede_pagar_segunda_cuota' => false,
            'monto_segunda_cuota' => 0
        ];
        
    } else if ($reserva['tipo_pago'] === 'Cuotas') {
        // Para pagos en cuotas, verificar en la tabla cuotas
        $stmt = $pdo->prepare("
            SELECT 
                p.pago_id,
                p.estado_pago as estado_pago_general,
                COUNT(c.cuota_id) as total_cuotas,
                SUM(CASE WHEN c.estado_cuota = 'Pagada' THEN c.monto_cuota ELSE 0 END) as total_pagado,
                SUM(CASE WHEN c.estado_cuota = 'Pendiente' THEN c.monto_cuota ELSE 0 END) as total_pendiente,
                COUNT(CASE WHEN c.estado_cuota = 'Pagada' THEN 1 END) as cuotas_pagadas,
                COUNT(CASE WHEN c.estado_cuota = 'Pendiente' THEN 1 END) as cuotas_pendientes
            FROM pagos p
            LEFT JOIN cuotas c ON p.pago_id = c.pago_id
            WHERE p.reserva_id = ?
            GROUP BY p.pago_id, p.estado_pago
        ");
        $stmt->execute([$reserva['reserva_id']]);
        $cuotas_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cuotas_info && $cuotas_info['total_cuotas'] > 0) {
            // Obtener información específica de las cuotas
            $stmt = $pdo->prepare("
                SELECT 
                    c.numero_cuota,
                    c.monto_cuota,
                    c.estado_cuota,
                    c.fecha_vencimiento
                FROM pagos p
                INNER JOIN cuotas c ON p.pago_id = c.pago_id
                WHERE p.reserva_id = ?
                ORDER BY c.numero_cuota
            ");
            $stmt->execute([$reserva['reserva_id']]);
            $detalle_cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $primera_cuota_pagada = false;
            $primera_cuota_pendiente = false;
            $segunda_cuota_pendiente = false;
            $monto_primera_cuota = 0;
            $monto_segunda_cuota = 0;
            
            foreach ($detalle_cuotas as $cuota) {
                if ($cuota['numero_cuota'] == 1) {
                    if ($cuota['estado_cuota'] == 'Pagada') {
                        $primera_cuota_pagada = true;
                    } else if ($cuota['estado_cuota'] == 'Pendiente') {
                        $primera_cuota_pendiente = true;
                        $monto_primera_cuota = floatval($cuota['monto_cuota']);
                    }
                }
                if ($cuota['numero_cuota'] == 2 && $cuota['estado_cuota'] == 'Pendiente') {
                    $segunda_cuota_pendiente = true;
                    $monto_segunda_cuota = floatval($cuota['monto_cuota']);
                }
            }
            
            $info_pagos = [
                'tipo_pago' => 'Cuotas',
                'total_pagado' => floatval($cuotas_info['total_pagado']),
                'saldo_pendiente' => floatval($cuotas_info['total_pendiente']),
                'esta_completamente_pagado' => $cuotas_info['cuotas_pendientes'] == 0,
                'cuotas_pagadas' => intval($cuotas_info['cuotas_pagadas']),
                'total_cuotas_esperadas' => intval($cuotas_info['total_cuotas']),
                'puede_pagar_primera_cuota' => $primera_cuota_pendiente,
                'monto_primera_cuota' => $monto_primera_cuota,
                'puede_pagar_segunda_cuota' => $primera_cuota_pagada && $segunda_cuota_pendiente,
                'monto_segunda_cuota' => $monto_segunda_cuota,
                'detalle_cuotas' => $detalle_cuotas
            ];
        } else {
            // Si es tipo Cuotas pero no hay cuotas registradas
            $info_pagos = [
                'tipo_pago' => 'Cuotas',
                'total_pagado' => 0,
                'saldo_pendiente' => floatval($reserva['total']),
                'esta_completamente_pagado' => false,
                'cuotas_pagadas' => 0,
                'total_cuotas_esperadas' => 2,
                'puede_pagar_segunda_cuota' => false,
                'monto_segunda_cuota' => 0
            ];
        }
    } else {
        // Tipo de pago no reconocido
        $info_pagos = [
            'tipo_pago' => $reserva['tipo_pago'],
            'total_pagado' => 0,
            'saldo_pendiente' => floatval($reserva['total']),
            'esta_completamente_pagado' => false,
            'cuotas_pagadas' => 0,
            'total_cuotas_esperadas' => 1,
            'puede_pagar_segunda_cuota' => false,
            'monto_segunda_cuota' => 0
        ];
    }
    
    // Determinar acciones disponibles
    $acciones = [
        'puede_solicitar_reembolso' => $info_pagos['total_pagado'] > 0 // Si ha pagado algo, puede solicitar reembolso
    ];

    // Formatear fecha
    function formatearFecha($fecha) {
        if (!$fecha) return null;
        return date('Y-m-d', strtotime($fecha));
    }
    
    // Calcular fecha de fin basada en duración
    function calcularFechaFin($fecha_inicio, $duracion) {
        if (!$fecha_inicio || !$duracion) return null;
        
        // Extraer número de días de la duración (ej: "3 dias" -> 3)
        preg_match('/(\d+)/', $duracion, $matches);
        $dias = isset($matches[1]) ? intval($matches[1]) : 1;
        
        // Calcular fecha fin (restar 1 día porque si es "3 días", termina el día 3, no el día 4)
        $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' +' . ($dias - 1) . ' days'));
        return $fecha_fin;
    }

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'reserva' => [
                'reserva_id' => $reserva['reserva_id'],
                'codigo_reserva' => $reserva['codigo_reserva'],
                'estado_reserva' => $reserva['estado_reserva'],
                'fecha_reserva' => formatearFecha($reserva['fecha_creacion']),
                'total' => $reserva['total'],
                'tipo_pago' => $reserva['tipo_pago'],
                'numero_participantes' => $reserva['num_personas'],
                'notas' => $reserva['notas']
            ],
            'cliente' => [
                'nombre_completo' => $reserva['nombre_completo'],
                'email' => $reserva['email'],
                'telefono' => $reserva['celular']
            ],
            'tour' => [
                'tour_id' => $reserva['tour_id'],
                'nombre' => $reserva['tour_nombre'],
                'descripcion' => $reserva['tour_descripcion'],
                'fecha_inicio' => formatearFecha($reserva['fecha_tour']),
                'fecha_fin' => calcularFechaFin($reserva['fecha_tour'], $reserva['duracion']),
                'duracion' => $reserva['duracion'],
                'precio_base' => $reserva['precio_base']
            ],
            'pagos' => $info_pagos,
            'acciones' => $acciones
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>
