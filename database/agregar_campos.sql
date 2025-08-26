-- Script simplificado para agregar campos faltantes
USE jaguar_expeditions;

-- Agregar campos faltantes a reserva_cuotas
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

SELECT 'Campos agregados a reserva_cuotas' as status;
