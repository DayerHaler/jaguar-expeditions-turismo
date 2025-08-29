<?php
/**
 * API para verificar y procesar pagos de PayPal
 * Jaguar Expeditions
 */

header('Content-Type: application/json');
require_once 'PayPalConfig.php';

// Función para verificar pago con PayPal
function verificarPagoPayPal($transactionId, $amount) {
    $config = PayPalConfig::getJSConfig();
    $baseUrl = PayPalConfig::getBaseUrl();
    $clientId = PayPalConfig::getClientId();
    $clientSecret = PayPalConfig::getClientSecret();
    
    try {
        // 1. Obtener token de acceso
        $tokenUrl = $baseUrl . '/v1/oauth2/token';
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $tokenUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
            ],
        ]);
        
        $tokenResponse = curl_exec($curl);
        $tokenData = json_decode($tokenResponse, true);
        
        if (!isset($tokenData['access_token'])) {
            throw new Exception('Error obteniendo token de PayPal');
        }
        
        $accessToken = $tokenData['access_token'];
        curl_close($curl);
        
        // 2. Verificar la transacción
        $orderUrl = $baseUrl . '/v2/checkout/orders/' . $transactionId;
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $orderUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);
        
        $orderResponse = curl_exec($curl);
        $orderData = json_decode($orderResponse, true);
        curl_close($curl);
        
        // 3. Validar la transacción
        if (isset($orderData['status']) && $orderData['status'] === 'COMPLETED') {
            $paidAmount = floatval($orderData['purchase_units'][0]['amount']['value']);
            $expectedAmount = floatval($amount);
            
            // Verificar que el monto coincida (con margen de error de $0.01)
            if (abs($paidAmount - $expectedAmount) <= 0.01) {
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'amount' => $paidAmount,
                    'currency' => $orderData['purchase_units'][0]['amount']['currency_code'],
                    'payer_email' => $orderData['payer']['email_address'] ?? '',
                    'payer_name' => $orderData['payer']['name']['given_name'] . ' ' . $orderData['payer']['name']['surname'],
                    'status' => $orderData['status'],
                    'create_time' => $orderData['create_time'],
                    'verified' => true
                ];
            } else {
                throw new Exception("Monto no coincide. Esperado: $expectedAmount, Pagado: $paidAmount");
            }
        } else {
            throw new Exception('Transacción no completada o no encontrada');
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'transaction_id' => $transactionId
        ];
    }
}

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['transaction_id']) || !isset($input['amount'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Faltan parámetros: transaction_id y amount son requeridos'
        ]);
        exit;
    }
    
    $resultado = verificarPagoPayPal($input['transaction_id'], $input['amount']);
    
    // Log de la verificación
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'transaction_id' => $input['transaction_id'],
        'amount' => $input['amount'],
        'result' => $resultado,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    @file_put_contents(__DIR__ . '/logs/paypal_verifications.log', 
                       json_encode($logEntry) . "\n", 
                       FILE_APPEND | LOCK_EX);
    
    echo json_encode($resultado);
    
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST.'
    ]);
}
?>
