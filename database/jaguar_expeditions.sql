-- ========================================
-- BASE DE DATOS JAGUAR EXPEDITIONS
-- ========================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS jaguar_expeditions;
USE jaguar_expeditions;

-- ========================================
-- TABLA DE TOURS
-- ========================================
CREATE TABLE tours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    descripcion_corta VARCHAR(500),
    duracion VARCHAR(50) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    precio_descuento DECIMAL(10,2) NULL,
    imagen_principal VARCHAR(255),
    imagenes_galeria JSON,
    incluye JSON,
    no_incluye JSON,
    itinerario JSON,
    dificultad ENUM('Fácil', 'Moderado', 'Difícil') DEFAULT 'Fácil',
    max_personas INT DEFAULT 12,
    min_personas INT DEFAULT 2,
    categoria ENUM('Aventura', 'Cultural', 'Naturaleza', 'Gastronomía', 'Relajación') DEFAULT 'Naturaleza',
    estado ENUM('Activo', 'Inactivo', 'Agotado') DEFAULT 'Activo',
    destacado BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- TABLA DE CONTACTOS (Formulario "Envíanos un mensaje")
-- ========================================
CREATE TABLE contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    asunto VARCHAR(255),
    mensaje TEXT NOT NULL,
    tour_interes INT NULL,
    fecha_preferida DATE NULL,
    num_personas INT NULL,
    origen_pais VARCHAR(100),
    estado ENUM('Nuevo', 'En_proceso', 'Respondido', 'Cerrado') DEFAULT 'Nuevo',
    prioridad ENUM('Baja', 'Media', 'Alta') DEFAULT 'Media',
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta TIMESTAMP NULL,
    notas_internas TEXT,
    FOREIGN KEY (tour_interes) REFERENCES tours(id) ON DELETE SET NULL
);

-- ========================================
-- TABLA DE RESERVAS/BOOKINGS
-- ========================================
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_reserva VARCHAR(20) UNIQUE NOT NULL,
    tour_id INT NOT NULL,
    
    -- Datos del cliente principal
    cliente_nombre VARCHAR(100) NOT NULL,
    cliente_apellido VARCHAR(100) NOT NULL,
    cliente_email VARCHAR(255) NOT NULL,
    cliente_telefono VARCHAR(20),
    cliente_pais VARCHAR(100),
    cliente_ciudad VARCHAR(100),
    cliente_documento VARCHAR(50),
    cliente_fecha_nacimiento DATE,
    
    -- Datos de la reserva
    fecha_tour DATE NOT NULL,
    num_adultos INT NOT NULL DEFAULT 1,
    num_ninos INT DEFAULT 0,
    num_bebes INT DEFAULT 0,
    total_personas INT NOT NULL,
    
    -- Precios
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'USD',
    
    -- Estado de la reserva
    estado_reserva ENUM('Pendiente', 'Confirmada', 'Pagada', 'Cancelada', 'Completada') DEFAULT 'Pendiente',
    estado_pago ENUM('Pendiente', 'Procesando', 'Pagado', 'Fallido', 'Reembolsado') DEFAULT 'Pendiente',
    
    -- Información adicional
    comentarios_especiales TEXT,
    restricciones_alimentarias TEXT,
    condiciones_medicas TEXT,
    nivel_experiencia ENUM('Principiante', 'Intermedio', 'Avanzado') DEFAULT 'Principiante',
    
    -- Datos del sistema
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE RESTRICT,
    INDEX idx_codigo_reserva (codigo_reserva),
    INDEX idx_cliente_email (cliente_email),
    INDEX idx_fecha_tour (fecha_tour),
    INDEX idx_estado_reserva (estado_reserva)
);

-- ========================================
-- TABLA DE PAGOS
-- ========================================
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    codigo_transaccion VARCHAR(100) UNIQUE NOT NULL,
    
    -- Información del pago
    metodo_pago ENUM('Stripe', 'PayPal', 'MercadoPago', 'Transferencia', 'Efectivo') NOT NULL,
    tipo_pago ENUM('Completo', 'Anticipo', 'Saldo') DEFAULT 'Completo',
    monto DECIMAL(10,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'USD',
    
    -- IDs de las pasarelas
    stripe_payment_intent_id VARCHAR(255),
    paypal_payment_id VARCHAR(255),
    mercadopago_payment_id VARCHAR(255),
    
    -- Estado del pago
    estado ENUM('Pendiente', 'Procesando', 'Exitoso', 'Fallido', 'Cancelado', 'Reembolsado') DEFAULT 'Pendiente',
    
    -- Detalles adicionales
    descripcion TEXT,
    datos_pago JSON, -- Para guardar datos específicos de cada pasarela
    respuesta_gateway JSON, -- Respuesta completa de la pasarela
    
    -- Fechas importantes
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_procesado TIMESTAMP NULL,
    fecha_vencimiento TIMESTAMP NULL,
    
    -- Información de reembolsos
    monto_reembolsado DECIMAL(10,2) DEFAULT 0,
    fecha_reembolso TIMESTAMP NULL,
    motivo_reembolso TEXT,
    
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    INDEX idx_codigo_transaccion (codigo_transaccion),
    INDEX idx_estado (estado),
    INDEX idx_metodo_pago (metodo_pago)
);

-- ========================================
-- TABLA DE ACOMPAÑANTES
-- ========================================
CREATE TABLE acompanantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    telefono VARCHAR(20),
    documento VARCHAR(50),
    fecha_nacimiento DATE,
    tipo ENUM('Adulto', 'Niño', 'Bebé') NOT NULL,
    relacion VARCHAR(50), -- Esposo/a, Hijo/a, Amigo/a, etc.
    restricciones_alimentarias TEXT,
    condiciones_medicas TEXT,
    
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE
);

-- ========================================
-- TABLA DE DISPONIBILIDAD DE TOURS
-- ========================================
CREATE TABLE disponibilidad_tours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    fecha DATE NOT NULL,
    cupos_disponibles INT NOT NULL,
    cupos_reservados INT DEFAULT 0,
    precio_especial DECIMAL(10,2) NULL,
    estado ENUM('Disponible', 'Agotado', 'Suspendido') DEFAULT 'Disponible',
    notas TEXT,
    
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tour_fecha (tour_id, fecha),
    INDEX idx_fecha (fecha)
);

-- ========================================
-- TABLA DE CONFIGURACIÓN DEL SISTEMA
-- ========================================
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descripcion TEXT,
    tipo ENUM('texto', 'numero', 'booleano', 'json') DEFAULT 'texto',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- INSERTAR DATOS INICIALES
-- ========================================

-- Tours iniciales
INSERT INTO tours (nombre, descripcion_corta, duracion, precio, imagen_principal, categoria, destacado) VALUES
('Expedición Río Amazonas', '3 días navegando por el río más largo del mundo', '3 días', 299.00, 'tour1.jpg', 'Aventura', TRUE),
('Safari Nocturno', 'Descubre la vida nocturna de la selva amazónica', '1 día', 89.00, 'tour2.jpg', 'Naturaleza', TRUE),
('Comunidades Nativas', 'Conoce la cultura ancestral de la Amazonía', '2 días', 199.00, 'tour3.jpeg', 'Cultural', TRUE),
('Aventura Extrema', 'Canopy, trekking y deportes de aventura', '4 días', 449.00, 'tour4.jpeg', 'Aventura', TRUE),
('Observación de Delfines', 'Nada con los delfines rosados del Amazonas', '1 día', 129.00, 'delfines.jpeg', 'Naturaleza', FALSE),
('Tour Gastronómico', 'Sabores únicos de la selva peruana', '1 día', 79.00, 'gastronomia.jpg', 'Gastronomía', FALSE);

-- Configuraciones iniciales
INSERT INTO configuracion (clave, valor, descripcion, tipo) VALUES
('stripe_public_key', '', 'Clave pública de Stripe', 'texto'),
('stripe_secret_key', '', 'Clave secreta de Stripe', 'texto'),
('paypal_client_id', '', 'Client ID de PayPal', 'texto'),
('paypal_client_secret', '', 'Client Secret de PayPal', 'texto'),
('mercadopago_public_key', '', 'Clave pública de MercadoPago', 'texto'),
('mercadopago_access_token', '', 'Access Token de MercadoPago', 'texto'),
('empresa_email', 'info@jaguarexpeditions.com', 'Email principal de la empresa', 'texto'),
('empresa_telefono', '+51 999 123 456', 'Teléfono de la empresa', 'texto'),
('moneda_principal', 'USD', 'Moneda principal para los precios', 'texto'),
('iva_porcentaje', '18', 'Porcentaje de IGV/IVA', 'numero'),
('dias_cancelacion', '7', 'Días mínimos para cancelación gratuita', 'numero');

-- ========================================
-- VISTAS ÚTILES
-- ========================================

-- Vista de reservas con información del tour
CREATE VIEW vista_reservas_completa AS
SELECT 
    r.*,
    t.nombre as tour_nombre,
    t.duracion as tour_duracion,
    t.categoria as tour_categoria,
    (SELECT COUNT(*) FROM acompanantes WHERE reserva_id = r.id) as num_acompanantes,
    (SELECT SUM(monto) FROM pagos WHERE reserva_id = r.id AND estado = 'Exitoso') as total_pagado
FROM reservas r
LEFT JOIN tours t ON r.tour_id = t.id;

-- Vista de estadísticas de contactos
CREATE VIEW estadisticas_contactos AS
SELECT 
    DATE(fecha_envio) as fecha,
    COUNT(*) as total_contactos,
    COUNT(CASE WHEN estado = 'Nuevo' THEN 1 END) as nuevos,
    COUNT(CASE WHEN estado = 'Respondido' THEN 1 END) as respondidos,
    COUNT(CASE WHEN tour_interes IS NOT NULL THEN 1 END) as con_interes_tour
FROM contactos
GROUP BY DATE(fecha_envio)
ORDER BY fecha DESC;

-- ========================================
-- PROCEDIMIENTOS ALMACENADOS
-- ========================================

DELIMITER //

-- Generar código de reserva único
CREATE FUNCTION generar_codigo_reserva() RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE codigo VARCHAR(20);
    DECLARE existe INT DEFAULT 1;
    
    WHILE existe > 0 DO
        SET codigo = CONCAT('JE', YEAR(NOW()), LPAD(FLOOR(RAND() * 999999), 6, '0'));
        SELECT COUNT(*) INTO existe FROM reservas WHERE codigo_reserva = codigo;
    END WHILE;
    
    RETURN codigo;
END //

DELIMITER ;

-- ========================================
-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- ========================================

-- Índices para búsquedas frecuentes
CREATE INDEX idx_tours_categoria_estado ON tours(categoria, estado);
CREATE INDEX idx_tours_precio ON tours(precio);
CREATE INDEX idx_contactos_fecha_estado ON contactos(fecha_envio, estado);
CREATE INDEX idx_reservas_fecha_creacion ON reservas(fecha_creacion);
CREATE INDEX idx_pagos_fecha_metodo ON pagos(fecha_pago, metodo_pago);

-- ========================================
-- TRIGGERS PARA AUDITORÍA
-- ========================================

DELIMITER //

-- Trigger para actualizar cupos al crear reserva
CREATE TRIGGER actualizar_cupos_reserva
AFTER INSERT ON reservas
FOR EACH ROW
BEGIN
    UPDATE disponibilidad_tours 
    SET cupos_reservados = cupos_reservados + NEW.total_personas
    WHERE tour_id = NEW.tour_id AND fecha = NEW.fecha_tour;
END //

-- Trigger para restaurar cupos al cancelar reserva
CREATE TRIGGER restaurar_cupos_cancelacion
AFTER UPDATE ON reservas
FOR EACH ROW
BEGIN
    IF OLD.estado_reserva != 'Cancelada' AND NEW.estado_reserva = 'Cancelada' THEN
        UPDATE disponibilidad_tours 
        SET cupos_reservados = cupos_reservados - NEW.total_personas
        WHERE tour_id = NEW.tour_id AND fecha = NEW.fecha_tour;
    END IF;
END //

DELIMITER ;

-- ========================================
-- COMENTARIOS FINALES
-- ========================================

/*
ESTRUCTURA COMPLETA CREADA:

TABLAS PRINCIPALES:
✅ tours - Catálogo de tours disponibles
✅ contactos - Formularios de contacto recibidos
✅ reservas - Reservas de tours realizadas
✅ pagos - Transacciones y pagos procesados
✅ acompañantes - Personas adicionales en las reservas
✅ disponibilidad_tours - Control de fechas y cupos
✅ configuracion - Configuraciones del sistema

CARACTERÍSTICAS:
✅ Relaciones entre tablas con llaves foráneas
✅ Índices para optimizar consultas
✅ Vistas para reportes rápidos
✅ Triggers para automatización
✅ Función para códigos únicos
✅ Soporte para múltiples pasarelas de pago
✅ Control de disponibilidad y cupos
✅ Historial completo de transacciones

PRÓXIMO PASO:
- Importar este archivo SQL en tu base de datos
- Configurar las credenciales de las pasarelas de pago
- Implementar los archivos PHP para procesar pagos
*/
