<?php
// Configuración de base de datos
$host = 'localhost';
$dbname = 'jaguar_expeditions';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit();
}

try {
    echo "=== Verificando tabla transacciones_log ===\n";
    
    $query = "SHOW TABLES LIKE 'transacciones_log'";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ La tabla transacciones_log existe\n";
        
        echo "\n=== Estructura de transacciones_log ===\n";
        $query = "DESCRIBE transacciones_log";
        $stmt = $pdo->query($query);
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($structure as $column) {
            echo "Columna: {$column['Field']} | Tipo: {$column['Type']}\n";
        }
    } else {
        echo "❌ La tabla transacciones_log NO existe\n";
        echo "Necesitamos eliminar esa parte del código\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
