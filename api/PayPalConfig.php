<?php
/**
 * Configuración de PayPal para Jaguar Expeditions
 * SOLO MODO PRODUCCIÓN - PAGOS REALES
 */

class PayPalConfig {
    // 🔴 CREDENCIALES DE PRODUCCIÓN (PAGOS REALES)
    public static $CLIENT_ID = 'AfodmrbEI6CjK7hiGXrhPPIBJ8ldDejX8DTO61hbLGzR_IrRum1aCmsYKEIANCpnLwM9Od7inW_JkhuC';
    public static $CLIENT_SECRET = 'EELZ7wYwDGqR9ioCf7NfE_fbjiMy1Mekrh1Y7ptu5FV6VKLOwRjOpTmsnKAmv6Kuju5snvPfA0hHOvrR';
    
    // URL base de producción
    public static $BASE_URL = 'https://api.paypal.com';
    
    // Configuración de moneda
    public static $CURRENCY = 'USD';
    
    // ⚠️ IMPORTANTE: CAMBIAR ESTAS URLs POR TU DOMINIO REAL
    // PayPal NO acepta localhost en producción
    public static $SUCCESS_URL = 'https://TU-DOMINIO-REAL.com/confirmacion.html?status=success';
    public static $CANCEL_URL = 'https://TU-DOMINIO-REAL.com/confirmacion.html?status=cancel';
    public static $WEBHOOK_URL = 'https://TU-DOMINIO-REAL.com/api/paypal_webhook.php';
    
    /**
     * Obtener Client ID
     */
    public static function getClientId() {
        return self::$CLIENT_ID;
    }
    
    /**
     * Obtener Client Secret
     */
    public static function getClientSecret() {
        return self::$CLIENT_SECRET;
    }
    
    /**
     * Obtener URL base
     */
    public static function getBaseUrl() {
        return self::$BASE_URL;
    }
    
    /**
     * Obtener configuración para JavaScript
     */
    public static function getJSConfig() {
        return [
            'clientId' => self::$CLIENT_ID,
            'currency' => self::$CURRENCY,
            'intent' => 'capture'
        ];
    }
}
?>
