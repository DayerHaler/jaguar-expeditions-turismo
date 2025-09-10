<?php
/**
 * CONFIGURACIÓN DE WEBHOOKS PAYPAL
 * Configuración para recibir notificaciones automáticas de PayPal
 */

class WebhookConfig {
    
    // URL donde PayPal enviará las notificaciones
    public static function getWebhookUrl() {
        // CAMBIAR ESTA URL POR TU DOMINIO REAL
        return "https://tudominio.com/api/paypal_webhook.php";
    }
    
    // Eventos que queremos recibir de PayPal
    public static function getEventTypes() {
        return [
            'PAYMENT.CAPTURE.COMPLETED',     // Pago capturado exitosamente
            'CHECKOUT.ORDER.APPROVED',       // Orden aprobada
            'PAYMENT.CAPTURE.DENIED',        // Pago denegado
            'PAYMENT.CAPTURE.REFUNDED',      // Pago reembolsado
            'BILLING.SUBSCRIPTION.ACTIVATED',// Suscripción activada
            'BILLING.SUBSCRIPTION.CANCELLED',// Suscripción cancelada
        ];
    }
    
    // ID del webhook (se genera al crear el webhook)
    public static function getWebhookId() {
        return 'WH-xxxxxxxxxx'; // Se actualizará después de crear el webhook
    }
    
    // Verificar si un evento debe ser procesado
    public static function shouldProcessEvent($eventType) {
        return in_array($eventType, self::getEventTypes());
    }
}

/**
 * FUNCIÓN PARA CREAR WEBHOOK EN PAYPAL
 */
function crearWebhookPayPal() {
    require_once 'PayPalConfig.php';
    
    $clientId = PayPalConfig::getClientId();
    $clientSecret = PayPalConfig::getClientSecret();
    $baseUrl = PayPalConfig::getBaseUrl();
    $webhookUrl = WebhookConfig::getWebhookUrl();
    $eventTypes = WebhookConfig::getEventTypes();
    
    // Preparar eventos para la API
    $events = [];
    foreach ($eventTypes as $eventType) {
        $events[] = ['name' => $eventType];
    }
    
    // Datos del webhook
    $webhookData = [
        'url' => $webhookUrl,
        'event_types' => $events
    ];
    
    // Obtener token de acceso
    $tokenData = [
        'grant_type' => 'client_credentials'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US',
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
    ]);
    
    $tokenResponse = curl_exec($ch);
    $tokenInfo = curl_getinfo($ch);
    curl_close($ch);
    
    if ($tokenInfo['http_code'] !== 200) {
        return ['error' => 'No se pudo obtener token de acceso', 'response' => $tokenResponse];
    }
    
    $tokenData = json_decode($tokenResponse, true);
    $accessToken = $tokenData['access_token'];
    
    // Crear webhook
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/notifications/webhooks');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'PayPal-Request-Id: ' . uniqid()
    ]);
    
    $webhookResponse = curl_exec($ch);
    $webhookInfo = curl_getinfo($ch);
    curl_close($ch);
    
    if ($webhookInfo['http_code'] === 201) {
        $webhook = json_decode($webhookResponse, true);
        return [
            'success' => true,
            'webhook_id' => $webhook['id'],
            'webhook_url' => $webhook['url'],
            'events' => $webhook['event_types']
        ];
    } else {
        return [
            'error' => 'No se pudo crear el webhook',
            'http_code' => $webhookInfo['http_code'],
            'response' => $webhookResponse
        ];
    }
}

/**
 * FUNCIÓN PARA LISTAR WEBHOOKS EXISTENTES
 */
function listarWebhooksPayPal() {
    require_once 'PayPalConfig.php';
    
    $clientId = PayPalConfig::getClientId();
    $clientSecret = PayPalConfig::getClientSecret();
    $baseUrl = PayPalConfig::getBaseUrl();
    
    // Obtener token de acceso
    $tokenData = [
        'grant_type' => 'client_credentials'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US',
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
    ]);
    
    $tokenResponse = curl_exec($ch);
    $tokenInfo = curl_getinfo($ch);
    curl_close($ch);
    
    if ($tokenInfo['http_code'] !== 200) {
        return ['error' => 'No se pudo obtener token de acceso'];
    }
    
    $tokenData = json_decode($tokenResponse, true);
    $accessToken = $tokenData['access_token'];
    
    // Listar webhooks
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/notifications/webhooks');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] === 200) {
        return json_decode($response, true);
    } else {
        return ['error' => 'No se pudieron listar los webhooks', 'response' => $response];
    }
}
?>
