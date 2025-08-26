-- =====================================================
-- AGREGAR TABLA PARTICIPANTES_RESERVA Y OPTIMIZAR CLIENTES
-- =====================================================

USE jaguar_expeditions;

-- =====================================================
-- 1. CREAR TABLA PARTICIPANTES_RESERVA
-- =====================================================
CREATE TABLE participantes_reserva (
    participante_id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    celular VARCHAR(20),
    celular_contacto VARCHAR(20),
    documento VARCHAR(20) NOT NULL,
    tipo_documento ENUM('DNI', 'Pasaporte', 'CE') DEFAULT 'DNI',
    edad INT,
    pais VARCHAR(100) DEFAULT 'Perú',
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    
    FOREIGN KEY (reserva_id) REFERENCES reservas(reserva_id) ON DELETE CASCADE,
    
    INDEX idx_reserva (reserva_id),
    INDEX idx_documento (documento),
    INDEX idx_email (email)
);

-- =====================================================
-- 2. ELIMINAR CAMPO TELEFONO DE TABLA CLIENTES
-- =====================================================
ALTER TABLE clientes DROP COLUMN telefono;

-- =====================================================
-- 3. INSERTAR DATOS DE EJEMPLO EN PARTICIPANTES
-- =====================================================

-- Primero insertar algunas reservas de ejemplo para poder agregar participantes
INSERT INTO reservas (cliente_id, tour_id, codigo_reserva, fecha_tour, num_personas, precio_por_persona, subtotal, impuestos, total, tipo_pago)
VALUES 
(1, 1, 'JE2025001', '2025-09-15', 2, 299.00, 598.00, 107.64, 705.64, 'Completo'),
(2, 1, 'JE2025002', '2025-09-20', 3, 299.00, 897.00, 161.46, 1058.46, 'Cuotas');

-- Agregar participantes para la primera reserva (2 personas)
INSERT INTO participantes_reserva (reserva_id, nombre, apellido, email, celular, documento, edad, pais)
VALUES 
(1, 'Juan Carlos', 'López García', 'juan.lopez@email.com', '+51987654321', '12345678', 32, 'Perú'),
(1, 'Ana María', 'López Sánchez', 'ana.lopez@email.com', '+51987654322', '12345679', 28, 'Perú');

-- Agregar participantes para la segunda reserva (3 personas)
INSERT INTO participantes_reserva (reserva_id, nombre, apellido, email, celular, documento, edad, pais)
VALUES 
(2, 'María Elena', 'González Ruiz', 'maria.gonzalez@email.com', '+51976543210', '87654321', 28, 'Perú'),
(2, 'Carlos Eduardo', 'González Ruiz', 'carlos.gonzalez@email.com', '+51976543211', '87654322', 30, 'Perú'),
(2, 'Sofía Isabel', 'González Ruiz', 'sofia.gonzalez@email.com', '+51976543212', '87654323', 25, 'Perú');

-- Agregar pagos correspondientes
INSERT INTO pagos (reserva_id, monto_total, metodo_pago, estado_pago, fecha_pago)
VALUES (1, 705.64, 'Tarjeta', 'Completado', NOW());

INSERT INTO pagos (reserva_id, monto_total, metodo_pago, estado_pago)
VALUES (2, 1058.46, 'Tarjeta', 'Pendiente');

-- Agregar cuotas para el pago en cuotas
INSERT INTO cuotas (pago_id, numero_cuota, monto_cuota, fecha_vencimiento)
VALUES 
(2, 1, 529.23, DATE_ADD(NOW(), INTERVAL 7 DAY)),
(2, 2, 529.23, DATE_ADD(NOW(), INTERVAL 30 DAY));

-- =====================================================
-- 4. CREAR VISTAS OPTIMIZADAS
-- =====================================================

-- Vista completa de reservas con cliente y participantes
DROP VIEW IF EXISTS vista_reservas_completas;
CREATE VIEW vista_reservas_completas AS
SELECT 
    r.reserva_id,
    r.codigo_reserva,
    CONCAT(c.nombre, ' ', c.apellido) as cliente_responsable,
    c.email as cliente_email,
    c.celular as cliente_celular,
    t.nombre as tour_nombre,
    r.fecha_tour,
    r.num_personas,
    r.total,
    r.estado_reserva,
    r.tipo_pago,
    p.estado_pago,
    p.metodo_pago,
    r.fecha_creacion,
    -- Contar participantes registrados
    (SELECT COUNT(*) FROM participantes_reserva pr WHERE pr.reserva_id = r.reserva_id) as participantes_registrados
FROM reservas r
JOIN clientes c ON r.cliente_id = c.cliente_id
JOIN tours t ON r.tour_id = t.id
LEFT JOIN pagos p ON r.reserva_id = p.reserva_id;

-- Vista de participantes por reserva
CREATE VIEW vista_participantes_por_reserva AS
SELECT 
    pr.participante_id,
    r.codigo_reserva,
    CONCAT(c.nombre, ' ', c.apellido) as cliente_responsable,
    CONCAT(pr.nombre, ' ', pr.apellido) as participante_nombre,
    pr.email as participante_email,
    pr.celular as participante_celular,
    pr.documento as participante_documento,
    pr.edad as participante_edad,
    pr.pais as participante_pais,
    t.nombre as tour_nombre,
    r.fecha_tour,
    r.estado_reserva
FROM participantes_reserva pr
JOIN reservas r ON pr.reserva_id = r.reserva_id
JOIN clientes c ON r.cliente_id = c.cliente_id
JOIN tours t ON r.tour_id = t.id;

-- Vista de cuotas pendientes mejorada
DROP VIEW IF EXISTS vista_cuotas_pendientes;
CREATE VIEW vista_cuotas_pendientes AS
SELECT 
    cu.cuota_id,
    r.codigo_reserva,
    CONCAT(cl.nombre, ' ', cl.apellido) as cliente_responsable,
    cl.email as cliente_email,
    cl.celular as cliente_celular,
    cu.numero_cuota,
    cu.monto_cuota,
    cu.fecha_vencimiento,
    cu.estado_cuota,
    DATEDIFF(cu.fecha_vencimiento, NOW()) as dias_vencimiento,
    -- Información del tour
    t.nombre as tour_nombre,
    r.fecha_tour,
    r.num_personas
FROM cuotas cu
JOIN pagos p ON cu.pago_id = p.pago_id
JOIN reservas r ON p.reserva_id = r.reserva_id
JOIN clientes cl ON r.cliente_id = cl.cliente_id
JOIN tours t ON r.tour_id = t.id
WHERE cu.estado_cuota = 'Pendiente';

-- =====================================================
-- 5. TRIGGER PARA VALIDAR NÚMERO DE PARTICIPANTES
-- =====================================================

DELIMITER //
CREATE TRIGGER validar_participantes_reserva
BEFORE INSERT ON participantes_reserva
FOR EACH ROW
BEGIN
    DECLARE num_personas_reserva INT;
    DECLARE participantes_actuales INT;
    
    -- Obtener número de personas de la reserva
    SELECT num_personas INTO num_personas_reserva 
    FROM reservas 
    WHERE reserva_id = NEW.reserva_id;
    
    -- Contar participantes actuales
    SELECT COUNT(*) INTO participantes_actuales 
    FROM participantes_reserva 
    WHERE reserva_id = NEW.reserva_id;
    
    -- Validar que no se exceda el número de personas
    IF participantes_actuales >= num_personas_reserva THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'No se pueden agregar más participantes. Se ha alcanzado el límite de personas para esta reserva.';
    END IF;
END//
DELIMITER ;

SELECT 'Tabla participantes_reserva creada exitosamente y estructura optimizada!' as mensaje;
