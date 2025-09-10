<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=jaguar_expeditions;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== ESTRUCTURA DE LA TABLA participantes_reserva ===\n";
    $result = $pdo->query('DESCRIBE participantes_reserva');

    foreach ($result as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
