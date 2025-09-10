<?php
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    echo "=== BASES DE DATOS DISPONIBLES ===\n";
    $result = $pdo->query('SHOW DATABASES');
    while($row = $result->fetch()) {
        echo $row[0] . "\n";
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
