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

$reserva_id = trim($input['reserva_id'] ?? '');
$codigo_reserva = trim($input['codigo_reserva'] ?? '');
$motivo = trim($input['motivo'] ?? '');

// Validar datos requeridos
if (empty($reserva_id) || empty($codigo_reserva)) {
    echo json_encode(['success' => false, 'message' => 'Datos de reserva requeridos']);
    exit();
}

if (empty($motivo)) {
    echo json_encode(['success' => false, 'message' => 'Debe especificar el motivo del reembolso']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Verificar que la reserva existe y es elegible para reembolso
    $query = "
        SELECT r.*, t.fecha_inicio,
               IFNULL(SUM(p.monto_total), 0) as monto_pagado,
               DATEDIFF(t.fecha_inicio, NOW()) as dias_hasta_tour
        FROM reservas r
        INNER JOIN tours t ON r.tour_id = t.tour_id
        LEFT JOIN pagos p ON r.reserva_id = p.reserva_id AND p.estado_pago = 'Completado'
        WHERE r.reserva_id = ? AND r.codigo_reserva = ?
        GROUP BY r.reserva_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$reserva_id, $codigo_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit();
    }
    
    if ($reserva['monto_pagado'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'No hay pagos realizados para reembolsar']);
        exit();
    }
    
    // Verificar si ya existe una solicitud de reembolso
    $check_reembolso = "SELECT * FROM reembolsos WHERE reserva_id = ?";
    $stmt = $pdo->prepare($check_reembolso);
    $stmt->execute([$reserva_id]);
    $reembolso_existente = $stmt->fetch();
    
    if ($reembolso_existente) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ya existe una solicitud de reembolso para esta reserva'
        ]);
        exit();
    }
    
    // Calcular porcentaje de reembolso según política
    $dias_hasta_tour = $reserva['dias_hasta_tour'];
    $porcentaje_reembolso = 0;
    $politica_aplicada = '';
    
    if ($dias_hasta_tour > 30) {
        $porcentaje_reembolso = 90;
        $politica_aplicada = 'Más de 30 días de anticipación';
    } elseif ($dias_hasta_tour > 15) {
        $porcentaje_reembolso = 70;
        $politica_aplicada = '15-30 días de anticipación';
    } elseif ($dias_hasta_tour > 7) {
        $porcentaje_reembolso = 50;
        $politica_aplicada = '7-15 días de anticipación';
    } elseif ($dias_hasta_tour > 0) {
        $porcentaje_reembolso = 20;
        $politica_aplicada = 'Menos de 7 días de anticipación';
    } else {
        $porcentaje_reembolso = 0;
        $politica_aplicada = 'Tour ya iniciado - Sin reembolso';
    }
    
    $monto_reembolso = ($reserva['monto_pagado'] * $porcentaje_reembolso) / 100;
    
    // Crear tabla de reembolsos si no existe
    $create_table = "
        CREATE TABLE IF NOT EXISTS reembolsos (
            reembolso_id INT PRIMARY KEY AUTO_INCREMENT,
            reserva_id INT NOT NULL,
            motivo TEXT NOT NULL,
            monto_original DECIMAL(10,2) NOT NULL,
            porcentaje_reembolso DECIMAL(5,2) NOT NULL,
            monto_reembolso DECIMAL(10,2) NOT NULL,
            politica_aplicada VARCHAR(100) NOT NULL,
            estado VARCHAR(50) DEFAULT 'Pendiente',
            fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_procesado TIMESTAMP NULL,
            notas_admin TEXT,
            FOREIGN KEY (reserva_id) REFERENCES reservas(reserva_id)
        )
    ";
    
    $pdo->exec($create_table);
    
    // Insertar solicitud de reembolso
    $insert_reembolso = "
        INSERT INTO reembolsos (
            reserva_id, 
            motivo, 
            monto_original, 
            porcentaje_reembolso, 
            monto_reembolso, 
            politica_aplicada,
            estado
        ) VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')
    ";
    
    $stmt = $pdo->prepare($insert_reembolso);
    $stmt->execute([
        $reserva_id,
        $motivo,
        $reserva['monto_pagado'],
        $porcentaje_reembolso,
        $monto_reembolso,
        $politica_aplicada
    ]);
    
    $reembolso_id = $pdo->lastInsertId();
    
    // Actualizar estado de la reserva si corresponde
    if ($monto_reembolso > 0) {
        $update_reserva = "UPDATE reservas SET estado_reserva = 'Reembolso Solicitado' WHERE reserva_id = ?";
        $stmt = $pdo->prepare($update_reserva);
        $stmt->execute([$reserva_id]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Solicitud de reembolso creada exitosamente',
        'data' => [
            'reembolso_id' => $reembolso_id,
            'monto_original' => $reserva['monto_pagado'],
            'porcentaje_reembolso' => $porcentaje_reembolso,
            'monto_reembolso' => $monto_reembolso,
            'politica_aplicada' => $politica_aplicada,
            'dias_hasta_tour' => $dias_hasta_tour,
            'estado' => 'Pendiente'
        ]
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?> 
            r.reserva_id, r.codigo_reserva, r.fecha_tour, r.total, r.estado_reserva,
            c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.email as cliente_email,
            t.nombre as tour_nombre,
            SUM(CASE WHEN p.estado_pago = 'Completado' THEN p.monto_total ELSE 0 END) as total_pagado
        FROM reservas r
        INNER JOIN clientes c ON r.cliente_id = c.cliente_id
        INNER JOIN tours t ON r.tour_id = t.id
        LEFT JOIN pagos p ON r.reserva_id = p.reserva_id
        WHERE r.codigo_reserva = ?
        GROUP BY r.reserva_id
    ");
    
    $stmt->execute([$codigo_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit();
    }
    
    if ($reserva['estado_reserva'] === 'Cancelada') {
        echo json_encode(['success' => false, 'message' => 'La reserva ya está cancelada']);
        exit();
    }
    
    if ($reserva['total_pagado'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'No hay pagos completados para reembolsar']);
        exit();
    }
    
    // Calcular porcentaje de reembolso según política
    $fecha_tour = new DateTime($reserva['fecha_tour']);
    $hoy = new DateTime();
    $diferencia = $hoy->diff($fecha_tour);
    $dias_restantes = $diferencia->days;
    
    $porcentaje_reembolso = 100; // Por defecto 100%
    if ($fecha_tour < $hoy) {
        // Tour ya pasó
        $porcentaje_reembolso = 0;
    } elseif ($dias_restantes < 1) {
        // Menos de 24 horas
        $porcentaje_reembolso = 50;
    } elseif ($dias_restantes < 2) {
        // Entre 24-48 horas
        $porcentaje_reembolso = 75;
    }
    
    if ($porcentaje_reembolso <= 0) {
        echo json_encode(['success' => false, 'message' => 'No es posible procesar reembolsos después de la fecha del tour']);
        exit();
    }
    
    $monto_reembolso = ($reserva['total_pagado'] * $porcentaje_reembolso) / 100;
    
    // Crear registro de reembolso
    $stmt_reembolso = $pdo->prepare("
        INSERT INTO reembolsos (
            reserva_id, 
            codigo_reserva, 
            monto_original, 
            porcentaje_reembolso, 
            monto_reembolso, 
            motivo_reembolso, 
            estado_reembolso,
            fecha_solicitud
        ) VALUES (?, ?, ?, ?, ?, ?, 'Pendiente', NOW())
    ");
    
    $stmt_reembolso->execute([
        $reserva['reserva_id'],
        $codigo_reserva,
        $reserva['total_pagado'],
        $porcentaje_reembolso,
        $monto_reembolso,
        $motivo ?: 'Solicitud de reembolso del cliente'
    ]);
    
    // Actualizar estado de la reserva
    $stmt_update = $pdo->prepare("
        UPDATE reservas 
        SET estado_reserva = 'Cancelada', 
            notas = CONCAT(IFNULL(notas, ''), '\n[', NOW(), '] Solicitud de reembolso procesada. Motivo: ', ?)
        WHERE reserva_id = ?
    ");
    
    $stmt_update->execute([
        $motivo ?: 'Solicitud de reembolso del cliente',
        $reserva['reserva_id']
    ]);
    
    // Actualizar estado de pagos
    $stmt_pagos = $pdo->prepare("
        UPDATE pagos 
        SET estado_pago = 'Reembolsado', 
            fecha_actualizacion = NOW()
        WHERE reserva_id = ? AND estado_pago = 'Completado'
    ");
    
    $stmt_pagos->execute([$reserva['reserva_id']]);
    
    $pdo->commit();
    
    // Aquí podrías agregar lógica para enviar email de confirmación
    // enviarEmailReembolso($reserva, $monto_reembolso, $porcentaje_reembolso, $motivo);
    
    echo json_encode([
        'success' => true,
        'message' => 'Solicitud de reembolso procesada exitosamente',
        'data' => [
            'codigo_reserva' => $codigo_reserva,
            'monto_original' => floatval($reserva['total_pagado']),
            'porcentaje_reembolso' => $porcentaje_reembolso,
            'monto_reembolso' => $monto_reembolso,
            'dias_restantes' => $dias_restantes,
            'fecha_estimada_reembolso' => date('Y-m-d', strtotime('+7 days'))
        ]
    ]);
    
} catch (PDOException $e) {
    $pdo->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar la solicitud de reembolso: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'Error inesperado: ' . $e->getMessage()
    ]);
}

// Función para enviar email de confirmación (implementar según necesidades)
function enviarEmailReembolso($reserva, $monto_reembolso, $porcentaje_reembolso, $motivo) {
    // Implementar envío de email
    // Usar PHPMailer o servicio de email preferido
}
?>
