<?php
$pdo = new PDO('mysql:host=localhost;dbname=jaguar_expeditions;charset=utf8', 'root', '');
$result = $pdo->query('DESCRIBE pagos');
echo "Estructura de la tabla pagos:\n";
while ($row = $result->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
