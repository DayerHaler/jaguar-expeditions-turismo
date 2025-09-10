<?php
/**
 * VERIFICADOR DE CREDENCIALES PAYPAL
 * Ejecuta este archivo para verificar si tus credenciales son válidas
 */

require_once '../config/config.php';
require_once 'PayPalConfig.php';

echo "<h2>🔍 VERIFICACIÓN DE CREDENCIALES PAYPAL</h2>";

// Obtener credenciales actuales
$clientId = PayPalConfig::getClientId();
$clientSecret = PayPalConfig::getClientSecret();
$baseUrl = PayPalConfig::getBaseUrl();

echo "<p><strong>Modo actual:</strong> PRODUCCIÓN</p>";
echo "<p><strong>Client ID:</strong> " . substr($clientId, 0, 20) . "...</p>";
echo "<p><strong>Base URL:</strong> {$baseUrl}</p>";

// Intentar obtener token de acceso
function getAccessToken($clientId, $clientSecret, $baseUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'response' => json_decode($response, true),
        'httpCode' => $httpCode
    ];
}

echo "<h3>🧪 Probando conexión...</h3>";

$result = getAccessToken($clientId, $clientSecret, $baseUrl);

if ($result['httpCode'] == 200) {
    echo "<p style='color: green;'>✅ <strong>CREDENCIALES VÁLIDAS</strong></p>";
    echo "<p>Token obtenido exitosamente</p>";
    
    // Mostrar información adicional
    if (isset($result['response']['scope'])) {
        echo "<p><strong>Permisos:</strong> " . $result['response']['scope'] . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ <strong>ERROR EN CREDENCIALES</strong></p>";
    echo "<p><strong>Código HTTP:</strong> " . $result['httpCode'] . "</p>";
    echo "<p><strong>Respuesta:</strong></p>";
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
    
    if ($result['httpCode'] == 401) {
        echo "<p style='color: orange;'>⚠️ <strong>PROBLEMA:</strong> Credenciales incorrectas o cuenta no autorizada</p>";
        echo "<p>Posibles causas:</p>";
        echo "<ul>";
        echo "<li>Client ID o Client Secret incorrectos</li>";
        echo "<li>Cuenta PayPal no es de tipo Business</li>";
        echo "<li>Aplicación no está aprobada para producción</li>";
        echo "<li>Credenciales de Sandbox usadas en modo producción</li>";
        echo "</ul>";
    } elseif ($result['httpCode'] == 0) {
        echo "<p style='color: orange;'>⚠️ <strong>PROBLEMA:</strong> No se pudo conectar a PayPal</p>";
        echo "<p>Verificar conexión a internet y que cURL esté habilitado</p>";
    }
}

echo "<hr>";
echo "<h3>📋 PASOS SIGUIENTES:</h3>";
echo "<ol>";
echo "<li>Si las credenciales son válidas, verificar las URLs de retorno</li>";
echo "<li>Si hay error 401, revisar credenciales en el dashboard de PayPal</li>";
echo "<li>Asegurarse de tener cuenta PayPal Business</li>";
echo "<li>Configurar webhooks para confirmación de pagos</li>";
echo "</ol>";
?>
