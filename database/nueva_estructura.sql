-- =====================================================
-- NUEVA ESTRUCTURA OPTIMIZADA DE BASE DE DATOS
-- Sistema de Reservas Jaguar Expeditions
-- =====================================================

USE jaguar_expeditions;

-- Eliminar las tablas existentes en orden correcto
DROP TABLE IF EXISTS cuotas;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS reserva_cuotas;
DROP TABLE IF EXISTS reservas_pago;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS clientes_reserva;
DROP TABLE IF EXISTS clientes;

-- =====================================================
-- 1. TABLA CLIENTES
-- =====================================================
CREATE TABLE clientes (
    cliente_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    celular VARCHAR(20),
    celular_contacto VARCHAR(20),
    documento VARCHAR(20) NOT NULL,
    tipo_documento ENUM('DNI', 'Pasaporte', 'CE') DEFAULT 'DNI',
    edad INT,
    genero ENUM('Masculino', 'Femenino', 'Otro') DEFAULT 'Masculino',
    pais VARCHAR(100) DEFAULT 'Perú',
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    INDEX idx_email (email),
    INDEX idx_documento (documento)
);

-- =====================================================
-- 2. TABLA RESERVAS
-- =====================================================
CREATE TABLE reservas (
    reserva_id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tour_id INT NOT NULL,
    codigo_reserva VARCHAR(20) UNIQUE NOT NULL,
    fecha_tour DATE NOT NULL,
    num_personas INT NOT NULL DEFAULT 1,
    precio_por_persona DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0.00,
    impuestos DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado_reserva ENUM('Pendiente', 'Confirmada', 'Cancelada', 'Completada') DEFAULT 'Pendiente',
    tipo_pago ENUM('Completo', 'Cuotas') NOT NULL,
    notas TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cliente_id) REFERENCES clientes(cliente_id) ON DELETE CASCADE,
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
    
    INDEX idx_codigo_reserva (codigo_reserva),
    INDEX idx_fecha_tour (fecha_tour),
    INDEX idx_estado (estado_reserva),
    INDEX idx_cliente (cliente_id),
    INDEX idx_tour (tour_id)
);

-- =====================================================
-- 3. TABLA PAGOS
-- =====================================================
CREATE TABLE pagos (
    pago_id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    codigo_transaccion VARCHAR(50) UNIQUE,
    monto_total DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('Tarjeta', 'PayPal', 'Yape', 'Plin', 'Transferencia', 'Efectivo') NOT NULL,
    estado_pago ENUM('Pendiente', 'Procesando', 'Completado', 'Fallido', 'Cancelado', 'Reembolsado') DEFAULT 'Pendiente',
    referencia_externa VARCHAR(100), -- ID de transacción de la pasarela
    datos_pago JSON, -- Detalles adicionales del pago (últimos 4 dígitos tarjeta, etc.)
    fecha_pago TIMESTAMP NULL,
    fecha_vencimiento TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reserva_id) REFERENCES reservas(reserva_id) ON DELETE CASCADE,
    
    INDEX idx_codigo_transaccion (codigo_transaccion),
    INDEX idx_estado_pago (estado_pago),
    INDEX idx_metodo_pago (metodo_pago),
    INDEX idx_reserva (reserva_id)
);

-- =====================================================
-- 4. TABLA CUOTAS
-- =====================================================
CREATE TABLE cuotas (
    cuota_id INT AUTO_INCREMENT PRIMARY KEY,
    pago_id INT NOT NULL,
    numero_cuota INT NOT NULL, -- 1 para primera cuota, 2 para segunda cuota
    monto_cuota DECIMAL(10,2) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado_cuota ENUM('Pendiente', 'Pagada', 'Vencida', 'Cancelada') DEFAULT 'Pendiente',
    codigo_transaccion_cuota VARCHAR(50),
    referencia_externa_cuota VARCHAR(100),
    fecha_pago TIMESTAMP NULL,
    datos_pago_cuota JSON,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pago_id) REFERENCES pagos(pago_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_cuota_pago (pago_id, numero_cuota),
    INDEX idx_estado_cuota (estado_cuota),
    INDEX idx_fecha_vencimiento (fecha_vencimiento),
    INDEX idx_numero_cuota (numero_cuota)
);

-- =====================================================
-- FUNCIONES Y TRIGGERS
-- =====================================================

-- Función para generar código único de reserva
DELIMITER //
CREATE FUNCTION generar_codigo_reserva() 
RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE codigo VARCHAR(20);
    DECLARE contador INT DEFAULT 0;
    
    REPEAT
        SET codigo = CONCAT('JE', YEAR(NOW()), LPAD(FLOOR(RAND() * 999999), 6, '0'));
        SELECT COUNT(*) INTO contador FROM reservas WHERE codigo_reserva = codigo;
    UNTIL contador = 0 END REPEAT;
    
    RETURN codigo;
END//
DELIMITER ;

-- Función para generar código de transacción
DELIMITER //
CREATE FUNCTION generar_codigo_transaccion() 
RETURNS VARCHAR(50)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE codigo VARCHAR(50);
    DECLARE contador INT DEFAULT 0;
    
    REPEAT
        SET codigo = CONCAT('TXN', YEAR(NOW()), MONTH(NOW()), DAY(NOW()), '_', LPAD(FLOOR(RAND() * 999999), 6, '0'));
        SELECT COUNT(*) INTO contador FROM pagos WHERE codigo_transaccion = codigo;
    UNTIL contador = 0 END REPEAT;
    
    RETURN codigo;
END//
DELIMITER ;

-- Trigger para asignar código de reserva automáticamente
DELIMITER //
CREATE TRIGGER before_insert_reserva
BEFORE INSERT ON reservas
FOR EACH ROW
BEGIN
    IF NEW.codigo_reserva IS NULL OR NEW.codigo_reserva = '' THEN
        SET NEW.codigo_reserva = generar_codigo_reserva();
    END IF;
END//
DELIMITER ;

-- Trigger para asignar código de transacción automáticamente
DELIMITER //
CREATE TRIGGER before_insert_pago
BEFORE INSERT ON pagos
FOR EACH ROW
BEGIN
    IF NEW.codigo_transaccion IS NULL OR NEW.codigo_transaccion = '' THEN
        SET NEW.codigo_transaccion = generar_codigo_transaccion();
    END IF;
END//
DELIMITER ;

-- Trigger para actualizar estado de pago cuando todas las cuotas están pagadas
DELIMITER //
CREATE TRIGGER after_update_cuota
AFTER UPDATE ON cuotas
FOR EACH ROW
BEGIN
    DECLARE cuotas_pendientes INT;
    
    IF NEW.estado_cuota = 'Pagada' THEN
        -- Contar cuotas pendientes para este pago
        SELECT COUNT(*) INTO cuotas_pendientes 
        FROM cuotas 
        WHERE pago_id = NEW.pago_id AND estado_cuota != 'Pagada';
        
        -- Si no hay cuotas pendientes, marcar pago como completado
        IF cuotas_pendientes = 0 THEN
            UPDATE pagos 
            SET estado_pago = 'Completado', fecha_pago = NOW() 
            WHERE pago_id = NEW.pago_id;
            
            -- Actualizar estado de reserva
            UPDATE reservas 
            SET estado_reserva = 'Confirmada' 
            WHERE reserva_id = (SELECT reserva_id FROM pagos WHERE pago_id = NEW.pago_id);
        END IF;
    END IF;
END//
DELIMITER ;

-- =====================================================
-- DATOS DE EJEMPLO
-- =====================================================

-- Insertar cliente de ejemplo
INSERT INTO clientes (nombre, apellido, email, telefono, celular, documento, edad, genero, pais) 
VALUES 
('Juan Carlos', 'López García', 'juan.lopez@email.com', '+51987654321', '+51987654321', '12345678', 32, 'Masculino', 'Perú'),
('María Elena', 'González Ruiz', 'maria.gonzalez@email.com', '+51976543210', '+51976543210', '87654321', 28, 'Femenino', 'Perú');

-- Insertar reserva de ejemplo (pago completo)
INSERT INTO reservas (cliente_id, tour_id, fecha_tour, num_personas, precio_por_persona, subtotal, impuestos, total, tipo_pago)
VALUES (1, 1, '2025-09-15', 2, 299.00, 598.00, 107.64, 705.64, 'Completo');

-- Insertar pago completo
INSERT INTO pagos (reserva_id, monto_total, metodo_pago, estado_pago, fecha_pago)
VALUES (1, 705.64, 'Tarjeta', 'Completado', NOW());

-- Insertar reserva de ejemplo (pago en cuotas)
INSERT INTO reservas (cliente_id, tour_id, fecha_tour, num_personas, precio_por_persona, subtotal, impuestos, total, tipo_pago)
VALUES (2, 1, '2025-09-20', 3, 299.00, 897.00, 161.46, 1058.46, 'Cuotas');

-- Insertar pago en cuotas
INSERT INTO pagos (reserva_id, monto_total, metodo_pago, estado_pago)
VALUES (2, 1058.46, 'Tarjeta', 'Pendiente');

-- Insertar cuotas
INSERT INTO cuotas (pago_id, numero_cuota, monto_cuota, fecha_vencimiento)
VALUES 
(2, 1, 529.23, DATE_ADD(NOW(), INTERVAL 7 DAY)),
(2, 2, 529.23, DATE_ADD(NOW(), INTERVAL 30 DAY));

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de reservas completas
CREATE VIEW vista_reservas_completas AS
SELECT 
    r.reserva_id,
    r.codigo_reserva,
    CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
    c.email as cliente_email,
    c.telefono as cliente_telefono,
    t.nombre as tour_nombre,
    r.fecha_tour,
    r.num_personas,
    r.total,
    r.estado_reserva,
    r.tipo_pago,
    p.estado_pago,
    p.metodo_pago,
    r.fecha_creacion
FROM reservas r
JOIN clientes c ON r.cliente_id = c.cliente_id
JOIN tours t ON r.tour_id = t.id
LEFT JOIN pagos p ON r.reserva_id = p.reserva_id;

-- Vista de cuotas pendientes
CREATE VIEW vista_cuotas_pendientes AS
SELECT 
    cu.cuota_id,
    r.codigo_reserva,
    CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
    cl.email,
    cu.numero_cuota,
    cu.monto_cuota,
    cu.fecha_vencimiento,
    cu.estado_cuota,
    DATEDIFF(cu.fecha_vencimiento, NOW()) as dias_vencimiento
FROM cuotas cu
JOIN pagos p ON cu.pago_id = p.pago_id
JOIN reservas r ON p.reserva_id = r.reserva_id
JOIN clientes cl ON r.cliente_id = cl.cliente_id
WHERE cu.estado_cuota = 'Pendiente';

-- =====================================================
-- ÍNDICES ADICIONALES PARA RENDIMIENTO
-- =====================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_reserva_estado_fecha ON reservas(estado_reserva, fecha_tour);
CREATE INDEX idx_pago_estado_metodo ON pagos(estado_pago, metodo_pago);
CREATE INDEX idx_cuota_vencimiento_estado ON cuotas(fecha_vencimiento, estado_cuota);

SELECT 'Base de datos reestructurada exitosamente!' as mensaje;
