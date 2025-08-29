<?php
/**
 * Configuración de PayPal para Jaguar Expeditions
 * Configurado para PAGOS REALES (Producción)
 */

class PayPalConfig {
    // ⚠️ IMPORTANTE: Cambiar a true para pagos reales
    public static $PRODUCTION_MODE = true; // false = pruebas, true = pagos reales
    
    // 🔴 CREDENCIALES DE PRODUCCIÓN (PAGOS REALES)
    public static $LIVE_CLIENT_ID = 'AfodmrbEI6CjK7hiGXrhPPIBJ8ldDejX8DTO61hbLGzR_IrRum1aCmsYKEIANCpnLwM9Od7inW_JkhuC';
    public static $LIVE_CLIENT_SECRET = 'EELZ7wYwDGqR9ioCf7NfE_fbjiMy1Mekrh1Y7ptu5FV6VKLOwRjOpTmsnKAmv6Kuju5snvPfA0hHOvrR';
    
    // 🟡 CREDENCIALES DE SANDBOX (SOLO PRUEBAS)
    public static $SANDBOX_CLIENT_ID = 'AdONQaUMXJWQ4hf8YO24u3M-JQOb7fFMZQRROwKAc0uAI6DpKfqJYbhD_cVSrpn6GQItC8_LoQCtGfIE';
    public static $SANDBOX_CLIENT_SECRET = 'EE12HH1vclt244Lqr2EREwFp1XPd3b9YDFBD0Y2Ti5GuuIllel1NkG1DIkYzBV4MmSQO5WhtBUj06ag_';
    
    // URLs según el entorno
    public static $SANDBOX_BASE_URL = 'https://api.sandbox.paypal.com';
    public static $PRODUCTION_BASE_URL = 'https://api.paypal.com';
    
    // Configuración de moneda
    public static $CURRENCY = 'USD';
    
    // URLs de tu sitio (actualizar con tu dominio real)
    public static $SUCCESS_URL = 'https://tu-dominio.com/pago_exitoso.php';
    public static $CANCEL_URL = 'https://tu-dominio.com/pago_cancelado.php';
    public static $WEBHOOK_URL = 'https://tu-dominio.com/api/paypal_webhook.php';
    
    /**
     * Obtener Client ID según el entorno
     */
    public static function getClientId() {
        return self::$PRODUCTION_MODE ? self::$LIVE_CLIENT_ID : self::$SANDBOX_CLIENT_ID;
    }
    
    /**
     * Obtener Client Secret según el entorno
     */
    public static function getClientSecret() {
        return self::$PRODUCTION_MODE ? self::$LIVE_CLIENT_SECRET : self::$SANDBOX_CLIENT_SECRET;
    }
    
    /**
     * Obtener URL base según el entorno
     */
    public static function getBaseUrl() {
        return self::$PRODUCTION_MODE ? self::$PRODUCTION_BASE_URL : self::$SANDBOX_BASE_URL;
    }
    
    /**
     * Verificar si estamos en producción
     */
    public static function isProduction() {
        return self::$PRODUCTION_MODE;
    }
    
    /**
     * Obtener configuración para JavaScript
     */
    public static function getJSConfig() {
        return [
            'client_id' => self::getClientId(),
            'currency' => self::$CURRENCY,
            'environment' => self::$PRODUCTION_MODE ? 'production' : 'sandbox'
        ];
    }
}
?>
