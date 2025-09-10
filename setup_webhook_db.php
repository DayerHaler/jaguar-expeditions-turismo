<?php
require_once 'config/config.php';

try {
    $db = getDB();
    echo "Conectado a la base de datos jaguar_expeditions\n";
    
    // Verificar si las columnas ya existen
    $result = $db->query("DESCRIBE reservas");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('webhook_verified', $columns)) {
        $db->exec("ALTER TABLE reservas ADD webhook_verified TINYINT(1) DEFAULT 0");
        echo "✓ Columna webhook_verified agregada\n";
    } else {
        echo "• Columna webhook_verified ya existe\n";
    }
    
    if (!in_array('webhook_data', $columns)) {
        $db->exec("ALTER TABLE reservas ADD webhook_data TEXT");
        echo "✓ Columna webhook_data agregada\n";
    } else {
        echo "• Columna webhook_data ya existe\n";
    }
    
    if (!in_array('fecha_webhook', $columns)) {
        $db->exec("ALTER TABLE reservas ADD fecha_webhook TIMESTAMP NULL");
        echo "✓ Columna fecha_webhook agregada\n";
    } else {
        echo "• Columna fecha_webhook ya existe\n";
    }
    
    // Verificar si la tabla webhook_logs existe
    $tables = $db->query("SHOW TABLES LIKE 'webhook_logs'")->fetchAll();
    
    if (empty($tables)) {
        $db->exec("
            CREATE TABLE webhook_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(100),
                paypal_id VARCHAR(100),
                reserva_id INT,
                webhook_data TEXT,
                processed TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_paypal_id (paypal_id),
                INDEX idx_event_type (event_type)
            )
        ");
        echo "✓ Tabla webhook_logs creada\n";
    } else {
        echo "• Tabla webhook_logs ya existe\n";
    }
    
    echo "\n🎉 Base de datos actualizada correctamente para webhooks!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
