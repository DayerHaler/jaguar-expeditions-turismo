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

echo "=== VERIFICACIÓN DEL SISTEMA DE CUOTAS ===\n\n";

echo "1. Estructura de la tabla CUOTAS:\n";
$query = "DESCRIBE cuotas";
$stmt = $pdo->query($query);
$structure = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($structure as $column) {
    echo "   - {$column['Field']} ({$column['Type']}) | Null: {$column['Null']} | Key: {$column['Key']}\n";
}

echo "\n2. Verificar si existe el campo codigo_transaccion en cuotas:\n";
$query = "SHOW COLUMNS FROM cuotas LIKE 'codigo_transaccion'";
$stmt = $pdo->query($query);
$result = $stmt->fetch();

if ($result) {
    echo "   ✅ Campo codigo_transaccion existe\n";
} else {
    echo "   ❌ Campo codigo_transaccion NO existe - necesario agregarlo\n";
    
    echo "\n   🔧 Agregando campo codigo_transaccion...\n";
    try {
        $query = "ALTER TABLE cuotas ADD COLUMN codigo_transaccion VARCHAR(100) NULL AFTER fecha_pago";
        $pdo->exec($query);
        echo "   ✅ Campo codigo_transaccion agregado exitosamente\n";
    } catch (PDOException $e) {
        echo "   ❌ Error agregando campo: " . $e->getMessage() . "\n";
    }
}

echo "\n3. Verificar relaciones entre tablas:\n";
$query = "
    SELECT 
        CONSTRAINT_NAME, 
        TABLE_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = '$dbname' 
    AND TABLE_NAME IN ('cuotas', 'pagos', 'reservas')
";

$stmt = $pdo->query($query);
$constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($constraints as $constraint) {
    echo "   - {$constraint['TABLE_NAME']}.{$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
}

echo "\n4. Datos actuales en las tablas:\n";

echo "\n   RESERVAS con tipo_pago = 'Cuotas':\n";
$query = "SELECT reserva_id, codigo_reserva, tipo_pago, estado_reserva, total FROM reservas WHERE tipo_pago = 'Cuotas'";
$stmt = $pdo->query($query);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($reservas) {
    foreach ($reservas as $reserva) {
        echo "   - {$reserva['codigo_reserva']} | Estado: {$reserva['estado_reserva']} | Total: ${$reserva['total']}\n";
    }
} else {
    echo "   - No hay reservas con pago en cuotas\n";
}

echo "\n   PAGOS asociados a reservas de cuotas:\n";
$query = "
    SELECT p.pago_id, p.reserva_id, r.codigo_reserva, p.estado_pago, p.monto_total
    FROM pagos p
    INNER JOIN reservas r ON p.reserva_id = r.reserva_id
    WHERE r.tipo_pago = 'Cuotas'
";
$stmt = $pdo->query($query);
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($pagos) {
    foreach ($pagos as $pago) {
        echo "   - Pago ID: {$pago['pago_id']} | Reserva: {$pago['codigo_reserva']} | Estado: {$pago['estado_pago']} | Monto: ${$pago['monto_total']}\n";
    }
} else {
    echo "   - No hay pagos asociados a reservas de cuotas\n";
}

echo "\n   CUOTAS existentes:\n";
$query = "
    SELECT c.cuota_id, c.pago_id, c.numero_cuota, c.monto_cuota, c.estado_cuota, r.codigo_reserva
    FROM cuotas c
    INNER JOIN pagos p ON c.pago_id = p.pago_id
    INNER JOIN reservas r ON p.reserva_id = r.reserva_id
";
$stmt = $pdo->query($query);
$cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($cuotas) {
    foreach ($cuotas as $cuota) {
        echo "   - Cuota {$cuota['numero_cuota']} | Reserva: {$cuota['codigo_reserva']} | Estado: {$cuota['estado_cuota']} | Monto: ${$cuota['monto_cuota']}\n";
    }
} else {
    echo "   - No hay cuotas registradas\n";
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
?>
