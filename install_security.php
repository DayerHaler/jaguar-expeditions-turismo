<?php
/**
 * INSTALADOR AUTOMÁTICO DEL SISTEMA DE SEGURIDAD
 * Jaguar Expeditions - Version 2.0
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador de Seguridad - Jaguar Expeditions</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c5530; text-align: center; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #2c5530; background: #f9f9f9; }
        .success { color: #2c5530; }
        .error { color: #cc0000; }
        .warning { color: #ff6600; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .button { background: #2c5530; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .button:hover { background: #1e3d22; }
        .log { background: #000; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; height: 200px; overflow-y: auto; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Instalador de Seguridad - Jaguar Expeditions</h1>
        
        <?php
        if (isset($_POST['install'])) {
            echo "<h2>📋 Proceso de Instalación</h2>";
            
            // Paso 1: Verificar conexión
            echo "<div class='step'>";
            echo "<h3>Paso 1: Verificando conexión a base de datos...</h3>";
            
            try {
                $db = new PDO(
                    "mysql:host=localhost;dbname=jaguar_expeditions;charset=utf8mb4",
                    "root",
                    "",
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                echo "<p class='success'>✅ Conexión exitosa a jaguar_expeditions</p>";
                
                // Paso 2: Leer y ejecutar SQL
                echo "</div><div class='step'>";
                echo "<h3>Paso 2: Ejecutando tablas de seguridad...</h3>";
                
                $sqlFile = 'database/security_tables_advanced.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    echo "<p class='success'>✅ Archivo SQL leído correctamente</p>";
                    
                    // Dividir en statements individuales
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    $executed = 0;
                    $errors = 0;
                    
                    echo "<div class='log'>";
                    echo "Ejecutando " . count($statements) . " statements...\n\n";
                    
                    foreach ($statements as $statement) {
                        if (empty($statement) || strpos($statement, '--') === 0) continue;
                        
                        try {
                            $db->exec($statement);
                            $executed++;
                            
                            // Mostrar solo statements importantes
                            if (strpos($statement, 'CREATE TABLE') !== false) {
                                preg_match('/CREATE TABLE.*?(\w+)\s*\(/', $statement, $matches);
                                $tableName = $matches[1] ?? 'unknown';
                                echo "✅ Tabla creada: $tableName\n";
                            } elseif (strpos($statement, 'INSERT INTO') !== false) {
                                preg_match('/INSERT INTO\s+(\w+)/', $statement, $matches);
                                $tableName = $matches[1] ?? 'unknown';
                                echo "📝 Datos insertados en: $tableName\n";
                            } elseif (strpos($statement, 'CREATE PROCEDURE') !== false) {
                                echo "🔧 Procedimiento almacenado creado\n";
                            } elseif (strpos($statement, 'CREATE FUNCTION') !== false) {
                                echo "⚡ Función creada\n";
                            } elseif (strpos($statement, 'CREATE EVENT') !== false) {
                                echo "⏰ Evento programado creado\n";
                            } elseif (strpos($statement, 'CREATE TRIGGER') !== false) {
                                echo "🎯 Trigger creado\n";
                            } elseif (strpos($statement, 'CREATE VIEW') !== false) {
                                echo "👁️ Vista creada\n";
                            }
                            
                        } catch (Exception $e) {
                            $errors++;
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                echo "❌ Error: " . $e->getMessage() . "\n";
                            }
                        }
                    }
                    
                    echo "\n🎯 Resumen: $executed statements ejecutados, $errors errores\n";
                    echo "</div>";
                    
                    if ($errors < 10) { // Permitir algunos errores menores
                        echo "<p class='success'>✅ Instalación completada con éxito</p>";
                        
                        // Paso 3: Verificar instalación
                        echo "</div><div class='step'>";
                        echo "<h3>Paso 3: Verificando instalación...</h3>";
                        
                        $tablas = ['security_logs', 'blocked_ips', 'rate_limiting', 'contact_attempts', 'security_config'];
                        $tablasOK = 0;
                        
                        foreach ($tablas as $tabla) {
                            try {
                                $stmt = $db->query("SHOW TABLES LIKE '$tabla'");
                                if ($stmt->rowCount() > 0) {
                                    echo "<p class='success'>✅ Tabla $tabla - OK</p>";
                                    $tablasOK++;
                                } else {
                                    echo "<p class='error'>❌ Tabla $tabla - NO ENCONTRADA</p>";
                                }
                            } catch (Exception $e) {
                                echo "<p class='error'>❌ Error verificando $tabla: " . $e->getMessage() . "</p>";
                            }
                        }
                        
                        if ($tablasOK === count($tablas)) {
                            echo "<h3 class='success'>🎉 ¡INSTALACIÓN EXITOSA!</h3>";
                            echo "<p>El sistema de seguridad ha sido instalado correctamente.</p>";
                            
                            // Mostrar configuración inicial
                            try {
                                $stmt = $db->query("SELECT config_key, config_value FROM security_config LIMIT 5");
                                $config = $stmt->fetchAll();
                                
                                echo "<h4>⚙️ Configuración inicial:</h4>";
                                echo "<div class='code'>";
                                foreach ($config as $item) {
                                    echo "{$item['config_key']}: {$item['config_value']}<br>";
                                }
                                echo "</div>";
                                
                            } catch (Exception $e) {
                                echo "<p class='warning'>⚠️ No se pudo leer la configuración inicial</p>";
                            }
                            
                            echo "<h4>🚀 Próximos pasos:</h4>";
                            echo "<ol>";
                            echo "<li>Probar el sistema: <a href='test_system_check.php' target='_blank'>test_system_check.php</a></li>";
                            echo "<li>Probar seguridad: <a href='test_security_advanced.html' target='_blank'>test_security_advanced.html</a></li>";
                            echo "<li>Usar formulario: <a href='contacto.html' target='_blank'>contacto.html</a></li>";
                            echo "</ol>";
                            
                        } else {
                            echo "<h3 class='error'>❌ INSTALACIÓN INCOMPLETA</h3>";
                            echo "<p>Algunas tablas no se crearon correctamente. Revisa los errores arriba.</p>";
                        }
                        
                    } else {
                        echo "<p class='error'>❌ Demasiados errores durante la instalación</p>";
                    }
                    
                } else {
                    echo "<p class='error'>❌ No se encontró el archivo database/security_tables_advanced.sql</p>";
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
                echo "<p>Verifica que:</p>";
                echo "<ul>";
                echo "<li>XAMPP esté ejecutándose</li>";
                echo "<li>MySQL esté activo</li>";
                echo "<li>La base de datos 'jaguar_expeditions' exista</li>";
                echo "</ul>";
            }
            echo "</div>";
            
        } else {
            // Mostrar formulario de instalación
            ?>
            
            <h2>🎯 Instalación del Sistema de Seguridad</h2>
            
            <div class="step">
                <h3>📋 Requisitos previos:</h3>
                <ul>
                    <li>✅ XAMPP ejecutándose</li>
                    <li>✅ MySQL activo</li>
                    <li>✅ Base de datos 'jaguar_expeditions' creada</li>
                    <li>✅ Archivo database/security_tables_advanced.sql presente</li>
                </ul>
            </div>
            
            <div class="step">
                <h3>🛡️ Características a instalar:</h3>
                <ul>
                    <li>📊 Tablas de logs de seguridad</li>
                    <li>🚫 Sistema de bloqueo de IPs</li>
                    <li>⏱️ Rate limiting por IP</li>
                    <li>🔒 Detección de intentos maliciosos</li>
                    <li>⚙️ Configuración dinámica</li>
                    <li>🧹 Limpieza automática de logs</li>
                    <li>🎯 Triggers de auto-protección</li>
                </ul>
            </div>
            
            <div class="step">
                <h3>⚠️ Importante:</h3>
                <p>Este instalador ejecutará el archivo <strong>database/security_tables_advanced.sql</strong> en tu base de datos. Asegúrate de tener una copia de seguridad si es necesario.</p>
            </div>
            
            <form method="post" style="text-align: center; margin: 30px 0;">
                <button type="submit" name="install" class="button">
                    🚀 Instalar Sistema de Seguridad
                </button>
            </form>
            
            <?php
        }
        ?>
    </div>
</body>
</html>
