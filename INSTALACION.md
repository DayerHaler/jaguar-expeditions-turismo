# INSTALACIÓN DE DEPENDENCIAS PARA JAGUAR EXPEDITIONS
# ===================================================

# Este archivo contiene los comandos necesarios para instalar las dependencias
# del sistema de pagos y otras librerías requeridas.

# REQUISITOS PREVIOS:
# - PHP 7.4 o superior
# - Composer instalado
# - MySQL 5.7 o superior
# - XAMPP o servidor web equivalente

# ========================================
# COMANDO PARA INSTALAR COMPOSER (si no está instalado)
# ========================================

# En Windows (ejecutar en PowerShell como administrador):
# php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
# php composer-setup.php
# php -r "unlink('composer-setup.php');"
# move composer.phar C:\Windows\System32\composer.bat

# ========================================
# CREAR ARCHIVO COMPOSER.JSON
# ========================================

# Ejecutar este comando en la raíz del proyecto:
# composer init

# O crear manualmente el archivo composer.json con el contenido del próximo bloque

# ========================================
# CONTENIDO DEL ARCHIVO composer.json
# ========================================

{
    "name": "jaguar-expeditions/tourism-system",
    "description": "Sistema de reservas y pagos para Jaguar Expeditions",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "stripe/stripe-php": "^10.0",
        "paypal/rest-api-sdk-php": "^1.14",
        "mercadopago/dx-php": "^2.5",
        "phpmailer/phpmailer": "^6.8",
        "mpdf/mpdf": "^8.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5"
    },
    "autoload": {
        "psr-4": {
            "JaguarExpeditions\\": "src/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "php -r \"if (!file_exists('config/config.php')) { copy('config/config.sample.php', 'config/config.php'); }\""
        ]
    }
}

# ========================================
# COMANDOS DE INSTALACIÓN
# ========================================

# 1. Navegar al directorio del proyecto
cd "C:\xampp\htdocs\Proyecto_turismo_contacto - copia"

# 2. Instalar dependencias con Composer
composer install

# 3. Crear archivo de configuración si no existe
# (Se copiará automáticamente desde config.sample.php)

# ========================================
# CONFIGURACIÓN DE BASE DE DATOS
# ========================================

# 1. Abrir XAMPP Control Panel
# 2. Iniciar Apache y MySQL
# 3. Abrir phpMyAdmin (http://localhost/phpmyadmin)
# 4. Crear nueva base de datos: jaguar_expeditions
# 5. Importar el archivo: database/jaguar_expeditions.sql

# ========================================
# CONFIGURACIÓN DE PASARELAS DE PAGO
# ========================================

# STRIPE:
# 1. Crear cuenta en https://stripe.com
# 2. Obtener claves API (test y live)
# 3. Configurar webhook endpoint: https://tu-dominio.com/api/webhook_stripe.php

# PAYPAL:
# 1. Crear cuenta developer en https://developer.paypal.com
# 2. Crear nueva aplicación
# 3. Obtener Client ID y Client Secret

# MERCADOPAGO:
# 1. Crear cuenta en https://mercadopago.com
# 2. Ir a "Tus integraciones" -> "Credenciales"
# 3. Obtener Public Key y Access Token

# ========================================
# CONFIGURACIÓN DE EMAIL
# ========================================

# Para usar PHPMailer con Gmail:
# 1. Habilitar "Acceso de aplicaciones menos seguras" O usar contraseñas de aplicación
# 2. Configurar SMTP en config/config.php

# ========================================
# ESTRUCTURA DE DIRECTORIOS NECESARIA
# ========================================

mkdir logs
mkdir uploads
mkdir temp
mkdir vendor

# ========================================
# PERMISOS DE ARCHIVOS (Linux/Mac)
# ========================================

# chmod 755 api/
# chmod 755 config/
# chmod 777 logs/
# chmod 777 uploads/
# chmod 777 temp/

# ========================================
# VERIFICACIÓN DE INSTALACIÓN
# ========================================

# Crear archivo test.php para verificar:

<?php
require_once 'config/config.php';

echo "<h2>Verificación de Sistema</h2>";

// Verificar conexión a base de datos
try {
    $db = getDB();
    echo "✓ Conexión a base de datos: OK<br>";
} catch (Exception $e) {
    echo "✗ Error de base de datos: " . $e->getMessage() . "<br>";
}

// Verificar librerías
$librerias = [
    'stripe' => class_exists('Stripe\\Stripe'),
    'phpmailer' => class_exists('PHPMailer\\PHPMailer\\PHPMailer'),
    'mpdf' => class_exists('Mpdf\\Mpdf')
];

foreach ($librerias as $nombre => $existe) {
    echo ($existe ? "✓" : "✗") . " Librería $nombre: " . ($existe ? "OK" : "NO INSTALADA") . "<br>";
}

// Verificar permisos de directorios
$directorios = ['logs', 'uploads', 'temp'];
foreach ($directorios as $dir) {
    $escribible = is_writable($dir);
    echo ($escribible ? "✓" : "✗") . " Directorio $dir: " . ($escribible ? "ESCRIBIBLE" : "SIN PERMISOS") . "<br>";
}

// Verificar configuración PHP
echo "<h3>Configuración PHP</h3>";
echo "Versión PHP: " . PHP_VERSION . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Upload Max Size: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
?>

# ========================================
# COMANDOS ADICIONALES ÚTILES
# ========================================

# Actualizar dependencias:
composer update

# Instalar dependencia específica:
composer require nombre/paquete

# Generar autoload:
composer dump-autoload

# Verificar dependencias:
composer show

# ========================================
# SOLUCIÓN DE PROBLEMAS COMUNES
# ========================================

# Error: "Class 'Stripe\Stripe' not found"
# Solución: Verificar que vendor/autoload.php esté incluido

# Error: "Access denied for user"
# Solución: Verificar credenciales de MySQL en config.php

# Error: "Permission denied"
# Solución: Verificar permisos de directorios logs/, uploads/, temp/

# Error: "Mail() function disabled"
# Solución: Configurar PHPMailer con SMTP

# ========================================
# COMANDOS PARA PRODUCCIÓN
# ========================================

# Instalar solo dependencias de producción:
composer install --no-dev --optimize-autoloader

# Configurar PHP para producción:
# display_errors = Off
# log_errors = On
# error_log = /path/to/php_errors.log

# ========================================
# BACKUP Y MANTENIMIENTO
# ========================================

# Backup de base de datos:
# mysqldump -u usuario -p jaguar_expeditions > backup_$(date +%Y%m%d).sql

# Limpiar logs antiguos:
# find logs/ -name "*.log" -mtime +30 -delete

# Verificar integridad de archivos:
# composer validate
