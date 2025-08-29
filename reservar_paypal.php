<?php
require_once 'api/PayPalConfig.php';
$paypalConfig = PayPalConfig::getJSConfig();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Reservar Tour - Jaguar Expeditions</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- PayPal SDK con configuración dinámica -->
    <?php if (PayPalConfig::isProduction()): ?>
        <!-- PRODUCCIÓN - PAGOS REALES -->
        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypalConfig['client_id']; ?>&currency=<?php echo $paypalConfig['currency']; ?>&intent=capture"></script>
        <script>
            console.log('🔴 PayPal en MODO PRODUCCIÓN - Pagos reales activados');
        </script>
    <?php else: ?>
        <!-- SANDBOX - SOLO PRUEBAS -->
        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypalConfig['client_id']; ?>&currency=<?php echo $paypalConfig['currency']; ?>&intent=capture"></script>
        <script>
            console.log('🟡 PayPal en modo SANDBOX - Solo pruebas');
        </script>
    <?php endif; ?>
    
    <script>
        // Configuración PayPal disponible en JavaScript
        window.PAYPAL_CONFIG = <?php echo json_encode($paypalConfig); ?>;
    </script>
