-- Actualizar tabla reservas para webhooks
ALTER TABLE reservas ADD webhook_verified TINYINT(1) DEFAULT 0;
ALTER TABLE reservas ADD webhook_data TEXT;
ALTER TABLE reservas ADD fecha_webhook TIMESTAMP NULL;

-- Crear tabla para log de webhooks
/* CREATE TABLE webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100),
    paypal_id VARCHAR(100),
    reserva_id INT,
    webhook_data TEXT,
    processed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_paypal_id (paypal_id),
    INDEX idx_event_type (event_type)
);
 */