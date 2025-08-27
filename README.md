# 🐆 Jaguar Expeditions - Sistema de Turismo

[![Sistema de Reservas](https://img.shields.io/badge/Sistema-Reservas%20Online-green)](https://github.com/DayerHaler/jaguar-expeditions-turismo)
[![Base de Datos](https://img.shields.io/badge/BD-Normalizada-blue)](https://github.com/DayerHaler/jaguar-expeditions-turismo)
[![Pago Dual](https://img.shields.io/badge/Pago-Completo%20%2F%20Cuotas-orange)](https://github.com/DayerHaler/jaguar-expeditions-turismo)

Sistema completo de reservas de turismo con base de datos normalizada y sistema de pago dual (completo/cuotas).

## 🌟 Características Principales

### ✅ Sistema de Pago Dual
- **Pago Completo**: Pago total inmediato con descuentos por grupo
- **Sistema de Cuotas**: 50% inicial + 50% antes del tour (15 días)
- Cálculo automático de descuentos por cantidad de personas:
  - 3-4 personas: 5% descuento
  - 5-7 personas: 10% descuento  
  - 8+ personas: 15% descuento

### ✅ Base de Datos Normalizada
- **clientes**: Datos del cliente responsable
- **reservas**: Información principal de la reserva
- **participantes_reserva**: Datos individuales de cada participante
- **pagos**: Registro de pagos realizados
- **cuotas**: Gestión de cuotas pendientes
- **tours**: Catálogo de tours disponibles

### ✅ Frontend Mejorado
- Selección visual de tipo de pago con efectos CSS
- Formularios separados para cliente responsable y participantes
- Cálculo automático de cuotas con fechas
- Validación de datos en tiempo real
- Interfaz responsive con gradientes y animaciones

## 🚀 Instalación y Configuración

### Prerrequisitos
- XAMPP (Apache + MySQL + PHP)
- Git

### 1. Clonar el Repositorio
```bash
git clone https://github.com/DayerHaler/jaguar-expeditions-turismo.git
cd jaguar-expeditions-turismo
```

### 2. Configurar Base de Datos
```sql
-- Crear base de datos
CREATE DATABASE jaguar_expeditions;

-- Ejecutar estructura normalizada
SOURCE database/estructura_nueva_segura.sql;
```

### 3. Configurar XAMPP
1. Copiar el proyecto a `C:\xampp\htdocs\`
2. Iniciar Apache y MySQL
3. Acceder a `http://localhost/jaguar-expeditions-turismo/`

## 📁 Estructura del Proyecto

```
proyecto/
├── index.html              # Página principal
├── reservar.html           # Sistema de reservas (PRINCIPAL)
├── tours.html              # Catálogo de tours
├── test_sistema.html       # Página de pruebas del sistema
├── api/
│   ├── tours.php           # API gestión de tours
│   ├── crear_reserva_normalizada.php  # API nueva estructura
│   └── procesar_pago_completo.php     # API legacy
├── database/
│   ├── estructura_nueva_segura.sql    # BD normalizada principal
│   ├── nueva_estructura.sql           # Versión anterior
│   └── *.sql               # Scripts de migración
├── img/                    # Imágenes del sitio
├── style.css              # Estilos principales
├── responsive.css         # Estilos responsive
└── script.js             # JavaScript general
```

## 🔧 API Endpoints

### GET /api/tours.php
Obtiene lista de tours disponibles
```json
{
  "id": 1,
  "nombre": "Expedición Amazonas",
  "precio": 150.00,
  "duracion": "3 días",
  "descripcion": "..."
}
```

### POST /api/crear_reserva_normalizada.php
Crea reserva con estructura normalizada
```json
{
  "tour_id": 1,
  "fecha_tour": "2024-02-15",
  "numero_personas": 2,
  "precio_total": 300.00,
  "tipo_pago": "completo|cuotas",
  "metodo_pago": "tarjeta|transferencia|efectivo",
  "cliente_responsable": {
    "nombre": "Juan",
    "apellido": "Pérez",
    "email": "juan@email.com"
  },
  "participantes": [
    {
      "nombre": "Juan", 
      "apellido": "Pérez",
      "edad": 30
    }
  ]
}
```

## 🎯 Flujo de Reserva

1. **Selección de Tour**: Usuario elige tour y fecha
2. **Configuración**: Número de personas y tipo de pago
3. **Datos del Cliente**: Formulario del responsable de la reserva
4. **Datos de Participantes**: Información individual de cada persona
5. **Método de Pago**: Selección de forma de pago
6. **Procesamiento**: Validación y creación en BD normalizada
7. **Confirmación**: Código de reserva y detalles

## 🧪 Testing

Accede a `test_sistema.html` para probar:
- ✅ API de tours
- ✅ Estructura de base de datos
- ✅ Creación de reservas (completo/cuotas)
- ✅ Frontend de reservas

## 💳 Sistema de Pagos

### Pago Completo
- Pago inmediato del 100%
- Descuentos automáticos por grupo
- Estado: `confirmada`

### Sistema de Cuotas
- 1ª Cuota: 50% inmediato (reserva el tour)
- 2ª Cuota: 50% hasta 15 días antes del tour
- Estado inicial: `parcialmente_pagada`

## 📊 Base de Datos

### Normalización Implementada
- **1NF**: Eliminación de grupos repetidos
- **2NF**: Dependencias funcionales parciales eliminadas  
- **3NF**: Dependencias transitivas eliminadas

### Relaciones
```sql
clientes 1:N reservas
reservas 1:N participantes_reserva
reservas 1:N pagos
reservas 1:N cuotas
tours 1:N reservas
```

## 🔒 Características de Seguridad

- Transacciones ACID en creación de reservas
- Validación de datos en frontend y backend
- Prepared statements contra SQL injection
- CORS configurado para APIs
- Rollback automático en errores

## 🚀 Próximas Mejoras

- [ ] Sistema de notificaciones por email
- [ ] Panel administrativo
- [ ] Integración con pasarelas de pago reales
- [ ] Sistema de cupones de descuento
- [ ] Reportes y estadísticas
- [ ] API REST completa con autenticación

## 👥 Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT.

## 🤝 Contacto

**Jaguar Expeditions**
- Website: [jaguar-expeditions-turismo](https://github.com/DayerHaler/jaguar-expeditions-turismo)
- Email: contact@jaguarexpeditions.com

---

⭐ **¡Dale una estrella si te gusta el proyecto!** ⭐
- **Panel administrativo** para gestionar tours y reservas
- **Sistema de emails** automatizado
- **Base de datos completa** con todas las relaciones

## 🎯 Características Principales

### ✅ **PÁGINAS COMPLETADAS**

1. **index.html** - Página principal con hero, tours destacados, testimonios
2. **tours.html** - Catálogo completo de tours con filtros
3. **contacto.html** - Formulario de contacto integrado con base de datos
4. **galeria.html** - Galería de fotos con filtros por categoría
5. **nosotros.html** - Página sobre la empresa con estadísticas
6. **preguntas.html** - FAQ con sistema de búsqueda
7. **reservar.html** - Sistema de reservas paso a paso
8. **confirmacion.html** - Página de confirmación de reserva

### ✅ **SISTEMA DE BASE DE DATOS**

- **8 Tablas principales** con relaciones completas
- **Triggers automáticos** para actualizar disponibilidad
- **Funciones personalizadas** para códigos únicos
- **Vistas optimizadas** para reportes
- **Índices de rendimiento** para consultas rápidas

### ✅ **APIs DESARROLLADAS**

- **`api/tours.php`** - CRUD completo de tours
- **`api/procesar_contacto.php`** - Procesar formularios de contacto
- **`api/crear_reserva.php`** - Crear nuevas reservas
- **`api/procesar_pago.php`** - Procesar pagos con múltiples pasarelas

### ✅ **SISTEMA DE PAGOS**

- **Stripe** - Tarjetas internacionales
- **PayPal** - Pagos seguros mundiales
- **MercadoPago** - Especializado en Latinoamérica
- **Procesamiento seguro** con validaciones
- **Emails automáticos** de confirmación

## 🛠️ Instalación y Configuración

### **1. Requisitos del Sistema**

```bash
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Composer (para dependencias PHP)
- XAMPP, WAMP o servidor web similar
```

### **2. Instalación Paso a Paso**

```bash
# 1. Clonar o descargar el proyecto
cd C:\xampp\htdocs\
# (Copiar la carpeta del proyecto aquí)

# 2. Instalar dependencias PHP
cd "Proyecto_turismo_contacto - copia"
composer install

# 3. Configurar base de datos
# - Abrir phpMyAdmin (http://localhost/phpmyadmin)
# - Crear base de datos: jaguar_expeditions
# - Importar archivo: database/jaguar_expeditions.sql

# 4. Configurar credenciales
# - Editar config/config.php
# - Agregar claves de Stripe, PayPal, MercadoPago
# - Configurar datos de email SMTP
```

### **3. Configuración de Pasarelas de Pago**

#### **STRIPE** (Recomendado para internacional)
```php
// En config/config.php
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');
define('STRIPE_SECRET_KEY', 'sk_test_...');
```

#### **PAYPAL**
```php
define('PAYPAL_CLIENT_ID', 'tu_client_id');
define('PAYPAL_CLIENT_SECRET', 'tu_client_secret');
define('PAYPAL_MODE', 'sandbox'); // 'live' para producción
```

#### **MERCADOPAGO**
```php
define('MERCADOPAGO_PUBLIC_KEY', 'TEST-tu-public-key');
define('MERCADOPAGO_ACCESS_TOKEN', 'TEST-tu-access-token');
```

## 📊 Estructura de la Base de Datos

### **Tablas Principales:**

1. **`tours`** - Catálogo de tours disponibles
2. **`contactos`** - Formularios de contacto recibidos
3. **`reservas`** - Reservas realizadas por clientes
4. **`pagos`** - Transacciones y pagos procesados
5. **`acompanantes`** - Personas adicionales en reservas
6. **`disponibilidad_tours`** - Control de fechas y cupos
7. **`configuracion`** - Configuraciones del sistema

### **Relaciones:**
- Tours → Reservas (1:N)
- Reservas → Pagos (1:N)
- Reservas → Acompañantes (1:N)
- Tours → Disponibilidad (1:N)

## 🎨 Características del Frontend

### **Diseño Responsive**
- ✅ Mobile First
- ✅ Breakpoints optimizados
- ✅ Navegación hamburguesa
- ✅ Imágenes adaptativas

### **Interactividad**
- ✅ Carruseles con Slick.js
- ✅ Animaciones CSS3
- ✅ Formularios validados
- ✅ Filtros dinámicos
- ✅ Modal de imágenes

### **Optimización SEO**
- ✅ Meta tags optimizados
- ✅ Estructura semántica HTML5
- ✅ URLs amigables
- ✅ Alt text en imágenes

## 🔧 Uso del Sistema

### **Para Clientes:**

1. **Explorar Tours** - `tours.html`
   - Ver catálogo completo
   - Filtrar por categoría/precio
   - Ver detalles de cada tour

2. **Hacer Reserva** - `reservar.html`
   - Proceso paso a paso
   - Selección de fecha/personas
   - Información personal
   - Pago seguro

3. **Contactar** - `contacto.html`
   - Formulario de consultas
   - Selección de tour de interés
   - Respuesta automática

### **Para Administradores:**

1. **Gestión de Tours** - API endpoints
   - `GET /api/tours.php` - Listar tours
   - `POST /api/tours.php` - Crear tour
   - `PUT /api/tours.php/{id}` - Actualizar tour
   - `DELETE /api/tours.php/{id}` - Eliminar tour

2. **Gestión de Reservas**
   - Ver reservas en base de datos
   - Procesar pagos pendientes
   - Contactar clientes

## 📧 Sistema de Emails Automáticos

### **Emails que se envían:**

1. **Confirmación de Contacto**
   - Al cliente que envía consulta
   - Al administrador con detalles

2. **Reserva Temporal**
   - Confirmación de reserva creada
   - Instrucciones de pago

3. **Confirmación de Pago**
   - Detalles completos de reserva
   - Información de contacto

## 🔒 Seguridad Implementada

- ✅ **Validación de datos** en frontend y backend
- ✅ **Prepared statements** para prevenir SQL injection
- ✅ **Sanitización** de inputs de usuario
- ✅ **Tokens CSRF** en formularios
- ✅ **Encriptación** de datos sensibles
- ✅ **Logs de errores** para auditoría

## 📱 Compatibilidad

### **Navegadores Soportados:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### **Dispositivos:**
- Desktop (1920px+)
- Laptop (1366px+)
- Tablet (768px+)
- Mobile (320px+)

## 🚀 Funcionalidades Avanzadas

### **Sistema de Reservas:**
```javascript
// Proceso paso a paso
1. Selección fecha/personas
2. Información personal
3. Datos acompañantes
4. Método de pago
5. Confirmación
```

### **Múltiples Pasarelas:**
```php
// Procesamiento unificado
switch ($metodoPago) {
    case 'stripe': return procesarStripe();
    case 'paypal': return procesarPayPal();
    case 'mercadopago': return procesarMercadoPago();
}
```

### **API REST Completa:**
```php
// Endpoints disponibles
GET    /api/tours.php           - Listar tours
GET    /api/tours.php/{id}      - Tour específico
POST   /api/crear_reserva.php   - Nueva reserva
POST   /api/procesar_pago.php   - Procesar pago
POST   /api/procesar_contacto.php - Formulario contacto
```

## 📈 Métricas y Analytics

### **Datos que se registran:**
- Número de visitas por tour
- Conversiones de reservas
- Métodos de pago más usados
- Países de origen de clientes
- Temporadas de mayor demanda

## 🎯 **ESTADO ACTUAL: 100% FUNCIONAL**

### ✅ **Completado:**
- [x] Todas las páginas HTML
- [x] Diseño responsive completo
- [x] Base de datos con datos de prueba
- [x] APIs funcionando
- [x] Sistema de pagos integrado
- [x] Formularios con validación
- [x] Emails automáticos
- [x] Documentación completa

### 🔄 **Pendiente (Opcionales):**
- [ ] Panel administrativo web
- [ ] Sistema de reportes
- [ ] App móvil
- [ ] Chat en vivo
- [ ] Sistema de reviews

## 📞 Soporte y Contacto

Para implementar este sistema o solicitar modificaciones:

- **Email:** desarrollo@jaguarexpeditions.com
- **WhatsApp:** +51 999 123 456
- **Documentación:** Ver archivos en `/docs/`

---

## 🎉 **¡Sistema Completo y Listo para Producción!**

Este sistema incluye todo lo necesario para gestionar un negocio de turismo:
- ✅ **Frontend atractivo y funcional**
- ✅ **Backend robusto con PHP/MySQL**
- ✅ **Sistema de pagos múltiples**
- ✅ **Base de datos completa**
- ✅ **Emails automáticos**
- ✅ **Documentación detallada**

**Total de archivos creados:** 15+
**APIs desarrolladas:** 4
**Páginas completadas:** 8
**Tablas de base de datos:** 8
**Pasarelas de pago:** 3

¡Tu sistema de turismo está listo para recibir clientes y procesar reservas! 🐆🌟

---

## 🆕 SISTEMA DE CONTACTO IMPLEMENTADO ✅

### Últimas Actualizaciones (26 Agosto 2025)

#### ✅ Formulario de Contacto Completo
- Guarda todos los datos en base de datos `contactos`
- Envío automático de emails al administrador
- Email de confirmación automático al cliente
- Sistema de logs para debugging

#### ✅ Sistema de Emails Gmail SMTP
- Configurado con Gmail SMTP (puerto 587)
- Usa contraseña de aplicación de Gmail
- Templates profesionales de email
- Manejo de errores y logs detallados

#### ✅ Panel de Administración
- `panel_contactos.php` - Ver todos los contactos
- `verificar_contactos.php` - Lista detallada
- Interfaz administrativa simple y funcional

### 🔄 Para Obtener Actualizaciones (Tu Amigo)

```bash
# 1. Ir a la carpeta del proyecto
cd ruta/a/tu/proyecto

# 2. Obtener últimos cambios
git pull origin main

# 3. Verificar que se descargó todo
git status
```

### ⚠️ Configuración Importante

**Tu amigo debe configurar su email en:**
- Archivo: `api/EmailService.php`
- Líneas 33-35: Cambiar email y contraseña de aplicación Gmail

### 📧 Crear Contraseña de Aplicación Gmail:
1. Ir a [myaccount.google.com](https://myaccount.google.com)
2. Seguridad → Verificación en 2 pasos
3. Contraseñas de aplicaciones → Crear nueva
4. Usar esa contraseña en `EmailService.php`

### 🧪 Probar el Sistema:
1. Abrir `contacto.html`
2. Llenar formulario y enviar
3. Verificar emails recibidos
4. Revisar `panel_contactos.php`

---

**✨ Sistema completo funcionando al 100%** 🎉
