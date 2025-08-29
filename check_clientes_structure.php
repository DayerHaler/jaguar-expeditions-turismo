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
    echo "=== Estructura de la tabla clientes ===\n";
    
    $query = "DESCRIBE clientes";
    $stmt = $pdo->query($query);
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($structure as $column) {
        echo "Columna: {$column['Field']} | Tipo: {$column['Type']} | Null: {$column['Null']} | Key: {$column['Key']}\n";
    }
    
    echo "\n=== Datos de ejemplo de clientes ===\n";
    $query = "SELECT * FROM clientes LIMIT 3";
    $stmt = $pdo->query($query);
    $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($examples) {
        print_r($examples);
    } else {
        echo "No hay datos en la tabla clientes\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
