<?php
echo "<h1>📊 Panel de Contactos Recibidos</h1>";

try {
    // Conexión a la base de datos
    $db = new PDO(
        "mysql:host=localhost;dbname=jaguar_expeditions;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Obtener los últimos 10 contactos
    $stmt = $db->query("
        SELECT 
            id, nombre, email, telefono, origen_pais, 
            fecha_preferida, num_personas, tour_interes,
            mensaje, newsletter, fecha_envio
        FROM contactos 
        ORDER BY fecha_envio DESC 
        LIMIT 10
    ");
    $contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener total de contactos
    $stmtTotal = $db->query("SELECT COUNT(*) as total FROM contactos");
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>📈 Estadísticas</h2>";
    echo "<p><strong>Total de contactos recibidos:</strong> {$total['total']}</p>";
    echo "<p><strong>Últimos contactos:</strong> Mostrando los 10 más recientes</p>";
    echo "</div>";
    
    if (empty($contactos)) {
        echo "<p>No hay contactos registrados aún.</p>";
    } else {
        echo "<table border='1' style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<thead style='background: #f8f9fa;'>";
        echo "<tr>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Fecha</th>";
        echo "<th style='padding: 10px;'>Nombre</th>";
        echo "<th style='padding: 10px;'>Email</th>";
        echo "<th style='padding: 10px;'>País</th>";
        echo "<th style='padding: 10px;'>Tour</th>";
        echo "<th style='padding: 10px;'>Mensaje</th>";
        echo "<th style='padding: 10px;'>Newsletter</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        $tours = [
            1 => 'Río Amazonas',
            2 => 'Safari Nocturno',
            3 => 'Comunidades Nativas',
            4 => 'Aventura Extrema',
            5 => 'Tour Personalizado'
        ];
        
        foreach ($contactos as $contacto) {
            $tourNombre = $tours[$contacto['tour_interes']] ?? 'No especificado';
            $newsletter = $contacto['newsletter'] ? '✅ Sí' : '❌ No';
            $mensajeCorto = substr($contacto['mensaje'], 0, 50) . '...';
            
            echo "<tr>";
            echo "<td style='padding: 8px; text-align: center;'>{$contacto['id']}</td>";
            echo "<td style='padding: 8px;'>" . date('d/m/Y H:i', strtotime($contacto['fecha_envio'])) . "</td>";
            echo "<td style='padding: 8px;'>{$contacto['nombre']}</td>";
            echo "<td style='padding: 8px;'><a href='mailto:{$contacto['email']}'>{$contacto['email']}</a></td>";
            echo "<td style='padding: 8px;'>{$contacto['origen_pais']}</td>";
            echo "<td style='padding: 8px;'>{$tourNombre}</td>";
            echo "<td style='padding: 8px; font-size: 12px;'>{$mensajeCorto}</td>";
            echo "<td style='padding: 8px; text-align: center;'>{$newsletter}</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    }
    
    // Estadísticas adicionales
    echo "<h2>📊 Estadísticas Detalladas</h2>";
    
    // Contactos por tour
    $stmtTours = $db->query("
        SELECT tour_interes, COUNT(*) as cantidad 
        FROM contactos 
        WHERE tour_interes IS NOT NULL 
        GROUP BY tour_interes 
        ORDER BY cantidad DESC
    ");
    $tourStats = $stmtTours->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>🎯 Tours más solicitados:</h3>";
    echo "<ul>";
    foreach ($tourStats as $stat) {
        $tourNombre = $tours[$stat['tour_interes']] ?? 'Tour #' . $stat['tour_interes'];
        echo "<li><strong>{$tourNombre}:</strong> {$stat['cantidad']} solicitudes</li>";
    } 
    echo "</ul>";
    
    // Contactos con newsletter
    $stmtNewsletter = $db->query("SELECT COUNT(*) as total FROM contactos WHERE newsletter = 1");
    $newsletter = $stmtNewsletter->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📧 Suscripciones al newsletter:</h3>";
    echo "<p><strong>{$newsletter['total']}</strong> personas han solicitado recibir ofertas especiales</p>";
    
    // Contactos recientes (últimas 24 horas)
    $stmtRecientes = $db->query("SELECT COUNT(*) as total FROM contactos WHERE fecha_envio >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $recientes = $stmtRecientes->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>⏰ Actividad reciente:</h3>";
    echo "<p><strong>{$recientes['total']}</strong> contactos recibidos en las últimas 24 horas</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "</div>";
}

echo "<h3>🔗 Enlaces útiles:</h3>";
echo "<ul>";
echo "<li><a href='contacto.html'>📝 Formulario de contacto</a></li>";
echo "<li><a href='api/test_emails.php'>📧 Probar envío de emails</a></li>";
echo "<li><a href='verificar_contactos.php'>🔍 Verificar sistema</a></li>";
echo "</ul>";
?>
