-- =====================================================
-- SCRIPT SEGURO PARA REESTRUCTURAR BASE DE DATOS
-- =====================================================

USE jaguar_expeditions;

-- Deshabilitar verificación de foreign keys temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar las tablas existentes en cualquier orden
DROP TABLE IF EXISTS cuotas;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS reserva_cuotas;
DROP TABLE IF EXISTS reservas_pago;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS clientes_reserva;
DROP TABLE IF EXISTS clientes;

-- Rehabilitar verificación de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

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
    referencia_externa VARCHAR(100),
    datos_pago JSON,
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
    numero_cuota INT NOT NULL,
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

-- Insertar cliente de ejemplo
INSERT INTO clientes (nombre, apellido, email, telefono, celular, documento, edad, genero, pais) 
VALUES 
('Juan Carlos', 'López García', 'juan.lopez@email.com', '+51987654321', '+51987654321', '12345678', 32, 'Masculino', 'Perú'),
('María Elena', 'González Ruiz', 'maria.gonzalez@email.com', '+51976543210', '+51976543210', '87654321', 28, 'Femenino', 'Perú');

SELECT 'Nueva estructura de base de datos creada exitosamente!' as mensaje;
