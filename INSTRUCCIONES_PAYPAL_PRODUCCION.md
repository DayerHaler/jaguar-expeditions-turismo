# 🔴 CONFIGURACIÓN PayPal PARA PAGOS REALES

## ⚠️ IMPORTANTE: Pasos obligatorios antes de activar pagos reales

### 1. 🏢 Verificar cuenta PayPal Business
- Necesitas una cuenta PayPal Business verificada
- Verificar identidad y cuenta bancaria
- Tener permisos para recibir pagos

### 2. 🔑 Obtener credenciales de PRODUCCIÓN

1. Ve a https://developer.paypal.com/developer/applications/
2. Cambia de "Sandbox" a "Live" (arriba a la derecha)
3. Crea una nueva aplicación para PRODUCCIÓN
4. Anota las credenciales:

```
LIVE CLIENT ID: [tu-client-id-de-produccion]
LIVE CLIENT SECRET: [tu-client-secret-de-produccion]
```

### 3. ⚙️ Configurar en tu sistema

**Archivo: `api/PayPalConfig.php`**
```php
// Cambiar estas líneas:
public static $PRODUCTION_MODE = true; // ✅ Cambiar a true
public static $LIVE_CLIENT_ID = 'TU_CLIENT_ID_REAL_AQUI';
public static $LIVE_CLIENT_SECRET = 'TU_CLIENT_SECRET_REAL_AQUI';
```

**Archivo: `reservar.html` (línea ~13)**
```javascript
const PAYPAL_CLIENT_ID = 'TU_CLIENT_ID_REAL_AQUI'; // ✅ Cambiar
const PAYPAL_ENVIRONMENT = 'production'; // ✅ Cambiar a production
```

### 4. 🌐 Configurar URLs reales

**En `PayPalConfig.php` actualizar:**
```php
public static $SUCCESS_URL = 'https://tu-dominio.com/pago_exitoso.php';
public static $CANCEL_URL = 'https://tu-dominio.com/pago_cancelado.php';
public static $WEBHOOK_URL = 'https://tu-dominio.com/api/paypal_webhook.php';
```

### 5. 🔗 Configurar Webhooks (Recomendado)

1. En PayPal Developer → Live → Tu App → Webhooks
2. Agregar endpoint: `https://tu-dominio.com/api/paypal_webhook.php`
3. Seleccionar eventos:
   - Payment capture completed
   - Payment capture denied
   - Checkout order approved

### 6. 🧪 PRUEBAS OBLIGATORIAS

**ANTES de publicar, probar:**

1. **Monto pequeño**: Hacer un pago de $1 USD
2. **Verificar**: Que llegue el dinero a tu cuenta PayPal
3. **Comprobar**: Que se genere el comprobante correctamente
4. **Validar**: Que los emails de confirmación funcionen

### 7. 🚀 ACTIVAR EN PRODUCCIÓN

```php
// En PayPalConfig.php - SOLO cuando todo esté probado
public static $PRODUCTION_MODE = true;
```

```javascript
// En reservar.html - SOLO cuando todo esté probado
const PAYPAL_ENVIRONMENT = 'production';
```

### 8. 📊 MONITOREO

- Revisar logs en `api/logs/paypal_verifications.log`
- Monitorear transacciones en PayPal Dashboard
- Verificar que lleguen los fondos

---

## ⚠️ CHECKLIST FINAL

- [ ] Cuenta PayPal Business verificada
- [ ] Credenciales de producción obtenidas
- [ ] URLs actualizadas con dominio real
- [ ] Configuración cambiada a producción
- [ ] Webhooks configurados
- [ ] Prueba de $1 USD realizada exitosamente
- [ ] Sistema de logs funcionando
- [ ] Emails de confirmación probados

## 🆘 SOPORTE

Si tienes problemas:
1. Revisar logs de error
2. Verificar credenciales PayPal
3. Comprobar que la cuenta esté verificada
4. Contactar soporte PayPal si es necesario

---

**¡IMPORTANTE!** Una vez en producción, todos los pagos serán REALES y se cobrarán dinero real a los clientes.
