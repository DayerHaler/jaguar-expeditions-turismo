<?php
require_once '../config/config.php';

echo "<h1>Estado del Sistema de Contactos</h1>";

try {
    $db = getDB();
    
    // Verificar conexión
    echo "<h2>✅ Conexión a la base de datos: OK</h2>";
    
    // Verificar estructura de tabla contactos
    echo "<h2>Estructura de la tabla contactos:</h2>";
    $stmt = $db->query("DESCRIBE contactos");
    $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($campos as $campo) {
        echo "<tr>";
        echo "<td>{$campo['Field']}</td>";
        echo "<td>{$campo['Type']}</td>";
        echo "<td>{$campo['Null']}</td>";
        echo "<td>{$campo['Key']}</td>";
        echo "<td>{$campo['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Contar contactos
    $stmt = $db->query("SELECT COUNT(*) as total FROM contactos");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Total de contactos en la base de datos: {$total['total']}</h2>";
    
    // Mostrar últimos 3 contactos
    if ($total['total'] > 0) {
        echo "<h2>Últimos contactos registrados:</h2>";
        $stmt = $db->query("SELECT id, nombre, email, mensaje, fecha_envio FROM contactos ORDER BY fecha_envio DESC LIMIT 3");
        $contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Mensaje</th><th>Fecha</th></tr>";
        foreach ($contactos as $contacto) {
            echo "<tr>";
            echo "<td>{$contacto['id']}</td>";
            echo "<td>{$contacto['nombre']}</td>";
            echo "<td>{$contacto['email']}</td>";
            echo "<td>" . substr($contacto['mensaje'], 0, 50) . "...</td>";
            echo "<td>{$contacto['fecha_envio']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar tours
    echo "<h2>Tours disponibles:</h2>";
    $stmt = $db->query("SELECT id, nombre FROM tours WHERE estado = 'Activo' LIMIT 5");
    $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($tours) {
        echo "<ul>";
        foreach ($tours as $tour) {
            echo "<li>ID: {$tour['id']} - {$tour['nombre']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No hay tours activos disponibles.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<h2>Sistema de contacto implementado:</h2>
<ul>
    <li>✅ Tabla 'contactos' configurada</li>
    <li>✅ API 'procesar_contacto.php' actualizado</li>
    <li>✅ JavaScript del formulario actualizado</li>
    <li>✅ Validaciones implementadas</li>
    <li>✅ Campo newsletter incluido</li>
    <li>✅ Mapeo de tours implementado</li>
</ul>

<h3>Para probar el sistema:</h3>
<p><a href="contacto.html">📧 Ir al formulario de contacto</a></p>
<p><a href="test_contacto.html">🧪 Ir a la página de prueba</a></p>
