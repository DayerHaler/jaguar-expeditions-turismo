-- Script para completar la reestructuración de las tablas
-- Agregar campos faltantes a las tablas renombradas

USE jaguar_expeditions;

-- =============================================
-- COMPLETAR ESTRUCTURA DE reserva_cuotas
-- =============================================

-- Primero eliminar la foreign key anterior si existe
ALTER TABLE reserva_cuotas DROP FOREIGN KEY IF EXISTS reserva_cuotas_ibfk_1;

-- Renombrar el campo reserva_id a tour_id
ALTER TABLE reserva_cuotas CHANGE reserva_id tour_id INT(11) NOT NULL;

-- Agregar campos necesarios para el sistema de cuotas
ALTER TABLE reserva_cuotas 
ADD COLUMN codigo_reserva VARCHAR(20) UNIQUE AFTER tour_id,
ADD COLUMN fecha_tour DATE NOT NULL AFTER codigo_reserva,
ADD COLUMN num_personas INT(11) NOT NULL DEFAULT 1 AFTER fecha_tour,
ADD COLUMN precio_unitario DECIMAL(10,2) NOT NULL AFTER num_personas,
ADD COLUMN subtotal DECIMAL(10,2) NOT NULL AFTER precio_unitario,
ADD COLUMN descuento DECIMAL(10,2) DEFAULT 0.00 AFTER subtotal,
ADD COLUMN impuestos DECIMAL(10,2) DEFAULT 0.00 AFTER descuento,
ADD COLUMN total DECIMAL(10,2) NOT NULL AFTER impuestos,
ADD COLUMN moneda VARCHAR(3) DEFAULT 'USD' AFTER total,
ADD COLUMN anticipo DECIMAL(10,2) NOT NULL COMMENT 'Monto del anticipo (50%)' AFTER moneda,
ADD COLUMN saldo_pendiente DECIMAL(10,2) NOT NULL COMMENT 'Saldo pendiente por pagar' AFTER anticipo,
ADD COLUMN estado_reserva ENUM('Pendiente_Anticipo','Anticipo_Pagado','Pendiente_Saldo','Pagada_Completa','Cancelada','Completada') DEFAULT 'Pendiente_Anticipo' AFTER saldo_pendiente,
ADD COLUMN estado_pago ENUM('Pendiente','Anticipo_Pagado','Completo','Fallido','Reembolsado') DEFAULT 'Pendiente' AFTER estado_reserva,
ADD COLUMN fecha_limite_saldo DATE COMMENT 'Fecha límite para pagar el saldo' AFTER estado_pago,
ADD COLUMN ip_address VARCHAR(45) AFTER fecha_limite_saldo,
ADD COLUMN user_agent TEXT AFTER ip_address,
ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER user_agent,
ADD COLUMN fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER fecha_creacion;

-- Agregar índices para reserva_cuotas
ALTER TABLE reserva_cuotas
ADD INDEX idx_tour_id (tour_id),
ADD INDEX idx_codigo_reserva (codigo_reserva),
ADD INDEX idx_fecha_tour (fecha_tour),
ADD INDEX idx_estado_reserva (estado_reserva),
ADD INDEX idx_estado_pago (estado_pago),
ADD INDEX idx_fecha_creacion (fecha_creacion);

-- Agregar foreign key para tour_id
ALTER TABLE reserva_cuotas
ADD CONSTRAINT fk_reserva_cuotas_tour_id 
FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- =============================================
-- MODIFICAR TABLA pagos PARA SOPORTAR AMBOS TIPOS
-- =============================================

-- Modificar la tabla pagos para que pueda referenciar ambos tipos de reservas
ALTER TABLE pagos 
MODIFY COLUMN reserva_id INT(11) NULL COMMENT 'ID de reserva (puede ser NULL si se usa otro campo)',
ADD COLUMN reserva_cuotas_id INT(11) NULL COMMENT 'ID de reserva con cuotas' AFTER reserva_id,
ADD COLUMN reservas_pago_id INT(11) NULL COMMENT 'ID de reserva con pago completo' AFTER reserva_cuotas_id,
ADD COLUMN numero_cuota TINYINT NULL COMMENT 'Número de cuota: 1=Anticipo, 2=Saldo final' AFTER tipo_pago;

-- Agregar índices para las nuevas relaciones
ALTER TABLE pagos
ADD INDEX idx_reserva_cuotas_id (reserva_cuotas_id),
ADD INDEX idx_reservas_pago_id (reservas_pago_id),
ADD INDEX idx_numero_cuota (numero_cuota);

-- Agregar foreign keys para las nuevas relaciones
ALTER TABLE pagos
ADD CONSTRAINT fk_pagos_reserva_cuotas 
FOREIGN KEY (reserva_cuotas_id) REFERENCES reserva_cuotas(id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT fk_pagos_reservas_pago 
FOREIGN KEY (reservas_pago_id) REFERENCES reservas_pago(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- =============================================
-- CREAR TRIGGER PARA CALCULAR CUOTAS AUTOMÁTICAMENTE
-- =============================================

DROP TRIGGER IF EXISTS tr_calcular_cuotas_before_insert;
DROP TRIGGER IF EXISTS tr_calcular_cuotas_before_update;

DELIMITER //

CREATE TRIGGER tr_calcular_cuotas_before_insert
BEFORE INSERT ON reserva_cuotas
FOR EACH ROW
BEGIN
    -- Calcular el anticipo (50% del total)
    SET NEW.anticipo = NEW.total * 0.50;
    
    -- Calcular el saldo pendiente (50% restante)
    SET NEW.saldo_pendiente = NEW.total - NEW.anticipo;
    
    -- Establecer fecha límite para pagar el saldo (7 días antes del tour)
    SET NEW.fecha_limite_saldo = DATE_SUB(NEW.fecha_tour, INTERVAL 7 DAY);
    
    -- Generar código de reserva único si no se proporciona
    IF NEW.codigo_reserva IS NULL OR NEW.codigo_reserva = '' THEN
        SET NEW.codigo_reserva = CONCAT('RC-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(LAST_INSERT_ID(), 4, '0'));
    END IF;
END//

CREATE TRIGGER tr_calcular_cuotas_before_update
BEFORE UPDATE ON reserva_cuotas
FOR EACH ROW
BEGIN
    -- Recalcular cuotas si el total cambió
    IF NEW.total != OLD.total THEN
        SET NEW.anticipo = NEW.total * 0.50;
        SET NEW.saldo_pendiente = NEW.total - NEW.anticipo;
    END IF;
    
    -- Actualizar fecha de modificación
    SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
END//

DELIMITER ;

-- =============================================
-- CREAR VISTAS PARA CONSULTAS SIMPLIFICADAS
-- =============================================

-- Eliminar vistas existentes si existen
DROP VIEW IF EXISTS vista_todas_reservas;
DROP VIEW IF EXISTS vista_pagos_completa;

-- Vista para todas las reservas (cuotas + pago completo)
CREATE VIEW vista_todas_reservas AS
SELECT 
    'CUOTAS' as tipo_reserva,
    rc.id,
    rc.codigo_reserva,
    rc.tour_id,
    t.nombre as tour_nombre,
    rc.nombre as cliente_nombre,
    rc.apellido as cliente_apellido,
    rc.email as cliente_email,
    rc.celular as cliente_telefono,
    rc.fecha_tour,
    rc.num_personas,
    rc.total,
    rc.anticipo,
    rc.saldo_pendiente,
    rc.estado_reserva,
    rc.estado_pago,
    rc.fecha_limite_saldo,
    rc.fecha_creacion
FROM reserva_cuotas rc
JOIN tours t ON rc.tour_id = t.id

UNION ALL

SELECT 
    'PAGO_COMPLETO' as tipo_reserva,
    rp.id,
    rp.codigo_reserva,
    rp.tour_id,
    t.nombre as tour_nombre,
    rp.cliente_nombre,
    rp.cliente_apellido,
    rp.cliente_email,
    rp.cliente_telefono,
    rp.fecha_tour,
    rp.num_clientes as num_personas,
    rp.total,
    rp.total as anticipo,
    0.00 as saldo_pendiente,
    rp.estado_reserva,
    rp.estado_pago,
    NULL as fecha_limite_saldo,
    rp.fecha_creacion
FROM reservas_pago rp
JOIN tours t ON rp.tour_id = t.id
ORDER BY fecha_creacion DESC;

-- Vista para pagos con detalles de reserva
CREATE VIEW vista_pagos_completa AS
SELECT 
    p.id as pago_id,
    p.codigo_transaccion,
    p.metodo_pago,
    p.tipo_pago,
    p.numero_cuota,
    p.monto,
    p.estado as estado_pago,
    p.fecha_pago,
    
    -- Datos de reserva con cuotas
    CASE 
        WHEN p.reserva_cuotas_id IS NOT NULL THEN 'CUOTAS'
        WHEN p.reservas_pago_id IS NOT NULL THEN 'PAGO_COMPLETO'
        ELSE 'LEGACY'
    END as tipo_reserva,
    
    COALESCE(rc.codigo_reserva, rp.codigo_reserva, 'N/A') as codigo_reserva,
    COALESCE(rc.nombre, rp.cliente_nombre, 'N/A') as cliente_nombre,
    COALESCE(rc.apellido, rp.cliente_apellido, 'N/A') as cliente_apellido,
    COALESCE(rc.email, rp.cliente_email, 'N/A') as cliente_email,
    COALESCE(rc.fecha_tour, rp.fecha_tour) as fecha_tour,
    COALESCE(t1.nombre, t2.nombre, 'N/A') as tour_nombre

FROM pagos p
LEFT JOIN reserva_cuotas rc ON p.reserva_cuotas_id = rc.id
LEFT JOIN reservas_pago rp ON p.reservas_pago_id = rp.id
LEFT JOIN tours t1 ON rc.tour_id = t1.id
LEFT JOIN tours t2 ON rp.tour_id = t2.id
ORDER BY p.fecha_pago DESC;

-- Mensaje de finalización
SELECT 'Reestructuración de base de datos completada exitosamente!' as status;
SELECT 'Tablas configuradas:' as info;
SELECT 'reserva_cuotas - Para reservas con pago en cuotas (50% anticipo + 50% saldo)' as tabla1;
SELECT 'reservas_pago - Para reservas con pago completo inmediato' as tabla2;
SELECT 'pagos - Tabla unificada para todos los tipos de pago' as tabla3;
