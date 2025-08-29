<?php
// Configuración de base de datos
$host = 'localhost';
$dbname = 'jaguar_expeditions';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ESTRUCTURA DE LA TABLA CUOTAS ===\n";
    $stmt = $pdo->query("DESCRIBE cuotas");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Key'] . " - " . $row['Default'] . "\n";
    }
    
    echo "\n=== DATOS DE EJEMPLO EN CUOTAS ===\n";
    $stmt = $pdo->query("SELECT * FROM cuotas LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }
    
    echo "\n=== BUSCAR RESERVAS CON CUOTAS ===\n";
    $stmt = $pdo->query("
        SELECT r.codigo_reserva, r.tipo_pago, r.total,
               p.pago_id, p.estado_pago,
               c.numero_cuota, c.monto_cuota, c.estado_cuota
        FROM reservas r
        INNER JOIN pagos p ON r.reserva_id = p.reserva_id
        INNER JOIN cuotas c ON p.pago_id = c.pago_id
        LIMIT 10
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
