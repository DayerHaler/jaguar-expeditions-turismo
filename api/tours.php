<?php
/**
 * API PARA GESTIÓN DE TOURS
 * =========================
 * 
 * Endpoint para obtener, crear, actualizar y eliminar tours
 */

require_once '../config/config.php';

// Configurar headers para CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$metodo = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? $_GET['path'] : '';

try {
    $db = getDB();
    
    switch ($metodo) {
        case 'GET':
            manejarGET($db, $path);
            break;
            
        case 'POST':
            manejarPOST($db);
            break;
            
        case 'PUT':
            manejarPUT($db, $path);
            break;
            
        case 'DELETE':
            manejarDELETE($db, $path);
            break;
            
        default:
            respuestaJSON(false, 'Método no soportado');
    }
    
} catch (Exception $e) {
    logError("Error en API tours: " . $e->getMessage());
    respuestaJSON(false, 'Error interno del servidor');
}

/**
 * Función helper para aplicar traducciones a un tour
 */
function aplicarTraduccion($tour, $idioma = 'es') {
    if ($idioma === 'es') {
        // Si es español, no hay nada que traducir, ya está en el idioma base
        return $tour;
    }
    
    // Aplicar traducciones según el idioma
    if ($idioma === 'en') {
        $tour['nombre'] = $tour['nombre_en'] ?? $tour['nombre'];
        $tour['descripcion'] = $tour['descripcion_en'] ?? $tour['descripcion'];
        $tour['descripcion_corta'] = $tour['descripcion_corta_en'] ?? $tour['descripcion_corta'];
        $tour['incluye'] = !empty($tour['incluye_en']) ? json_decode($tour['incluye_en'], true) : 
                          (!empty($tour['incluye']) ? json_decode($tour['incluye'], true) : []);
        $tour['no_incluye'] = !empty($tour['no_incluye_en']) ? json_decode($tour['no_incluye_en'], true) : 
                             (!empty($tour['no_incluye']) ? json_decode($tour['no_incluye'], true) : []);
        $tour['itinerario'] = !empty($tour['itinerario_en']) ? json_decode($tour['itinerario_en'], true) : 
                             (!empty($tour['itinerario']) ? json_decode($tour['itinerario'], true) : []);
    } elseif ($idioma === 'de') {
        $tour['nombre'] = $tour['nombre_de'] ?? $tour['nombre'];
        $tour['descripcion'] = $tour['descripcion_de'] ?? $tour['descripcion'];
        $tour['descripcion_corta'] = $tour['descripcion_corta_de'] ?? $tour['descripcion_corta'];
        $tour['incluye'] = !empty($tour['incluye_de']) ? json_decode($tour['incluye_de'], true) : 
                          (!empty($tour['incluye']) ? json_decode($tour['incluye'], true) : []);
        $tour['no_incluye'] = !empty($tour['no_incluye_de']) ? json_decode($tour['no_incluye_de'], true) : 
                             (!empty($tour['no_incluye']) ? json_decode($tour['no_incluye'], true) : []);
        $tour['itinerario'] = !empty($tour['itinerario_de']) ? json_decode($tour['itinerario_de'], true) : 
                             (!empty($tour['itinerario']) ? json_decode($tour['itinerario'], true) : []);
    }
    
    // Limpiar campos de traducción para no enviarlos al frontend
    unset($tour['nombre_en'], $tour['nombre_de']);
    unset($tour['descripcion_en'], $tour['descripcion_de']);
    unset($tour['descripcion_corta_en'], $tour['descripcion_corta_de']);
    unset($tour['incluye_en'], $tour['incluye_de']);
    unset($tour['no_incluye_en'], $tour['no_incluye_de']);
    unset($tour['itinerario_en'], $tour['itinerario_de']);
    
    return $tour;
}

/**
 * Manejar peticiones GET
 */
function manejarGET($db, $path) {
    if (empty($path)) {
        // Obtener todos los tours
        obtenerTodosLosTours($db);
    } elseif (is_numeric($path)) {
        // Obtener un tour específico
        obtenerTourPorId($db, $path);
    } elseif ($path === 'activos') {
        // Obtener solo tours activos
        obtenerToursActivos($db);
    } elseif ($path === 'destacados') {
        // Obtener tours destacados
        obtenerToursDestacados($db);
    } elseif ($path === 'categorias') {
        // Obtener tours por categoría
        obtenerToursPorCategoria($db);
    } else {
        respuestaJSON(false, 'Endpoint no encontrado');
    }
}

/**
 * Obtener todos los tours
 */
function obtenerTodosLosTours($db) {
    $categoria = $_GET['categoria'] ?? '';
    $precio_min = $_GET['precio_min'] ?? '';
    $precio_max = $_GET['precio_max'] ?? '';
    $dificultad = $_GET['dificultad'] ?? '';
    $busqueda = $_GET['busqueda'] ?? '';
    
    $sql = "SELECT * FROM tours WHERE 1=1";
    $params = [];
    
    if (!empty($categoria)) {
        $sql .= " AND categoria = ?";
        $params[] = $categoria;
    }
    
    if (!empty($precio_min)) {
        $sql .= " AND precio >= ?";
        $params[] = $precio_min;
    }
    
    if (!empty($precio_max)) {
        $sql .= " AND precio <= ?";
        $params[] = $precio_max;
    }
    
    if (!empty($dificultad)) {
        $sql .= " AND dificultad = ?";
        $params[] = $dificultad;
    }
    
    if (!empty($busqueda)) {
        $sql .= " AND (nombre LIKE ? OR descripcion LIKE ? OR descripcion_corta LIKE ?)";
        $busquedaParam = "%{$busqueda}%";
        $params[] = $busquedaParam;
        $params[] = $busquedaParam;
        $params[] = $busquedaParam;
    }
    
    $sql .= " ORDER BY destacado DESC, fecha_creacion DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tours = $stmt->fetchAll();
    
    // Procesar datos adicionales
    foreach ($tours as &$tour) {
        $tour['precio_formateado'] = formatearPrecio($tour['precio']);
        $tour['incluye'] = !empty($tour['incluye']) ? json_decode($tour['incluye'], true) : [];
        $tour['no_incluye'] = !empty($tour['no_incluye']) ? json_decode($tour['no_incluye'], true) : [];
        $tour['itinerario'] = !empty($tour['itinerario']) ? json_decode($tour['itinerario'], true) : [];
        $tour['imagenes_galeria'] = !empty($tour['imagenes_galeria']) ? json_decode($tour['imagenes_galeria'], true) : [];
    }
    
    respuestaJSON(true, 'Tours obtenidos correctamente', $tours);
}

/**
 * Obtener tours activos
 */
function obtenerToursActivos($db) {
    $idioma = $_GET['lang'] ?? $_GET['idioma'] ?? 'es';
    
    $sql = "SELECT * FROM tours WHERE estado = 'Activo' ORDER BY destacado DESC, precio ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $tours = $stmt->fetchAll();
    
    foreach ($tours as &$tour) {
        // Aplicar traducción según el idioma solicitado
        $tour = aplicarTraduccion($tour, $idioma);
        
        $tour['precio_formateado'] = formatearPrecio($tour['precio']);
        
        // Asegurar que incluye y no_incluye sean arrays
        if (!is_array($tour['incluye'])) {
            $tour['incluye'] = !empty($tour['incluye']) ? json_decode($tour['incluye'], true) : [];
        }
        if (!is_array($tour['no_incluye'])) {
            $tour['no_incluye'] = !empty($tour['no_incluye']) ? json_decode($tour['no_incluye'], true) : [];
        }
    }
    
    respuestaJSON(true, 'Tours activos obtenidos', $tours);
}

/**
 * Obtener tours destacados
 */
function obtenerToursDestacados($db) {
    $idioma = $_GET['lang'] ?? $_GET['idioma'] ?? 'es';
    
    $sql = "SELECT * FROM tours WHERE estado = 'Activo' AND destacado = 1 ORDER BY precio ASC LIMIT 6";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $tours = $stmt->fetchAll();
    
    foreach ($tours as &$tour) {
        // Aplicar traducción según el idioma solicitado
        $tour = aplicarTraduccion($tour, $idioma);
        
        $tour['precio_formateado'] = formatearPrecio($tour['precio']);
    }
    
    respuestaJSON(true, 'Tours destacados obtenidos', $tours);
}

/**
 * Obtener tour por ID
 */
function obtenerTourPorId($db, $id) {
    $idioma = $_GET['lang'] ?? $_GET['idioma'] ?? 'es';
    
    $sql = "SELECT * FROM tours WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $tour = $stmt->fetch();
    
    if (!$tour) {
        respuestaJSON(false, 'Tour no encontrado');
    }
    
    // Aplicar traducción según el idioma solicitado
    $tour = aplicarTraduccion($tour, $idioma);
    
    // Procesar campos JSON
    $tour['precio_formateado'] = formatearPrecio($tour['precio']);
    
    // Asegurar que los campos JSON sean arrays
    if (!is_array($tour['incluye'])) {
        $tour['incluye'] = !empty($tour['incluye']) ? json_decode($tour['incluye'], true) : [];
    }
    if (!is_array($tour['no_incluye'])) {
        $tour['no_incluye'] = !empty($tour['no_incluye']) ? json_decode($tour['no_incluye'], true) : [];
    }
    if (!is_array($tour['itinerario'])) {
        $tour['itinerario'] = !empty($tour['itinerario']) ? json_decode($tour['itinerario'], true) : [];
    }
    
    $tour['imagenes_galeria'] = !empty($tour['imagenes_galeria']) ? json_decode($tour['imagenes_galeria'], true) : [];
    
    // Obtener disponibilidad próxima
    $sqlDisponibilidad = "SELECT fecha, cupos_disponibles, cupos_reservados, precio_especial 
                         FROM disponibilidad_tours 
                         WHERE tour_id = ? AND fecha >= CURDATE() AND estado = 'Disponible'
                         ORDER BY fecha ASC LIMIT 10";
    $stmtDisp = $db->prepare($sqlDisponibilidad);
    $stmtDisp->execute([$id]);
    $tour['disponibilidad'] = $stmtDisp->fetchAll();
    
    respuestaJSON(true, 'Tour obtenido correctamente', $tour);
}

/**
 * Obtener tours por categoría
 */
function obtenerToursPorCategoria($db) {
    $sql = "SELECT categoria, COUNT(*) as total, AVG(precio) as precio_promedio 
            FROM tours 
            WHERE estado = 'Activo' 
            GROUP BY categoria 
            ORDER BY categoria";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $categorias = $stmt->fetchAll();
    
    $resultado = [];
    foreach ($categorias as $cat) {
        $categoria = $cat['categoria'];
        
        // Obtener tours de esta categoría
        $sqlTours = "SELECT * FROM tours WHERE categoria = ? AND estado = 'Activo' ORDER BY destacado DESC, precio ASC";
        $stmtTours = $db->prepare($sqlTours);
        $stmtTours->execute([$categoria]);
        $tours = $stmtTours->fetchAll();
        
        foreach ($tours as &$tour) {
            $tour['precio_formateado'] = formatearPrecio($tour['precio']);
        }
        
        $resultado[] = [
            'categoria' => $categoria,
            'total_tours' => $cat['total'],
            'precio_promedio' => formatearPrecio($cat['precio_promedio']),
            'tours' => $tours
        ];
    }
    
    respuestaJSON(true, 'Tours por categoría obtenidos', $resultado);
}

/**
 * Manejar peticiones POST (crear tour)
 */
function manejarPOST($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        respuestaJSON(false, 'Datos inválidos');
    }
    
    // Validaciones básicas
    $nombre = $data['nombre'] ?? '';
    $descripcion = $data['descripcion'] ?? '';
    $duracion = $data['duracion'] ?? '';
    $precio = $data['precio'] ?? 0;
    
    if (empty($nombre) || empty($descripcion) || empty($duracion) || $precio <= 0) {
        respuestaJSON(false, 'Datos obligatorios faltantes');
    }
    
    try {
        $sql = "INSERT INTO tours (
            nombre, descripcion, descripcion_corta, duracion, precio, 
            precio_descuento, imagen_principal, imagenes_galeria, 
            incluye, no_incluye, itinerario, dificultad, max_personas, 
            min_personas, categoria, estado, destacado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $resultado = $stmt->execute([
            $nombre,
            $descripcion,
            $data['descripcion_corta'] ?? '',
            $duracion,
            $precio,
            $data['precio_descuento'] ?? null,
            $data['imagen_principal'] ?? '',
            json_encode($data['imagenes_galeria'] ?? []),
            json_encode($data['incluye'] ?? []),
            json_encode($data['no_incluye'] ?? []),
            json_encode($data['itinerario'] ?? []),
            $data['dificultad'] ?? 'Fácil',
            $data['max_personas'] ?? 12,
            $data['min_personas'] ?? 2,
            $data['categoria'] ?? 'Naturaleza',
            $data['estado'] ?? 'Activo',
            $data['destacado'] ?? 0
        ]);
        
        if ($resultado) {
            $tourId = $db->lastInsertId();
            respuestaJSON(true, 'Tour creado correctamente', ['id' => $tourId]);
        } else {
            respuestaJSON(false, 'Error al crear el tour');
        }
        
    } catch (Exception $e) {
        logError("Error al crear tour: " . $e->getMessage());
        respuestaJSON(false, 'Error al crear el tour');
    }
}

/**
 * Manejar peticiones PUT (actualizar tour)
 */
function manejarPUT($db, $id) {
    if (!is_numeric($id)) {
        respuestaJSON(false, 'ID de tour inválido');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        respuestaJSON(false, 'Datos inválidos');
    }
    
    try {
        // Verificar que el tour existe
        $stmt = $db->prepare("SELECT id FROM tours WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            respuestaJSON(false, 'Tour no encontrado');
        }
        
        $sql = "UPDATE tours SET 
                nombre = ?, descripcion = ?, descripcion_corta = ?, 
                duracion = ?, precio = ?, precio_descuento = ?, 
                imagen_principal = ?, imagenes_galeria = ?, incluye = ?, 
                no_incluye = ?, itinerario = ?, dificultad = ?, 
                max_personas = ?, min_personas = ?, categoria = ?, 
                estado = ?, destacado = ?, fecha_actualizacion = NOW()
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $resultado = $stmt->execute([
            $data['nombre'],
            $data['descripcion'],
            $data['descripcion_corta'] ?? '',
            $data['duracion'],
            $data['precio'],
            $data['precio_descuento'] ?? null,
            $data['imagen_principal'] ?? '',
            json_encode($data['imagenes_galeria'] ?? []),
            json_encode($data['incluye'] ?? []),
            json_encode($data['no_incluye'] ?? []),
            json_encode($data['itinerario'] ?? []),
            $data['dificultad'] ?? 'Fácil',
            $data['max_personas'] ?? 12,
            $data['min_personas'] ?? 2,
            $data['categoria'] ?? 'Naturaleza',
            $data['estado'] ?? 'Activo',
            $data['destacado'] ?? 0,
            $id
        ]);
        
        if ($resultado) {
            respuestaJSON(true, 'Tour actualizado correctamente');
        } else {
            respuestaJSON(false, 'Error al actualizar el tour');
        }
        
    } catch (Exception $e) {
        logError("Error al actualizar tour: " . $e->getMessage());
        respuestaJSON(false, 'Error al actualizar el tour');
    }
}

/**
 * Manejar peticiones DELETE (eliminar tour)
 */
function manejarDELETE($db, $id) {
    if (!is_numeric($id)) {
        respuestaJSON(false, 'ID de tour inválido');
    }
    
    try {
        // Verificar que no hay reservas activas
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM reservas WHERE tour_id = ? AND estado_reserva NOT IN ('Cancelada', 'Completada')");
        $stmt->execute([$id]);
        $reservas = $stmt->fetch();
        
        if ($reservas['total'] > 0) {
            respuestaJSON(false, 'No se puede eliminar el tour porque tiene reservas activas');
        }
        
        // Eliminar tour (esto eliminará en cascada las disponibilidades)
        $stmt = $db->prepare("DELETE FROM tours WHERE id = ?");
        $resultado = $stmt->execute([$id]);
        
        if ($resultado) {
            respuestaJSON(true, 'Tour eliminado correctamente');
        } else {
            respuestaJSON(false, 'Error al eliminar el tour');
        }
        
    } catch (Exception $e) {
        logError("Error al eliminar tour: " . $e->getMessage());
        respuestaJSON(false, 'Error al eliminar el tour');
    }
}
?>
