-- CREACIÓN DE TABLAS PARA SISTEMA DE SEGURIDAD
-- ESPECÍFICO PARA APLICACIÓN DE TURISMO SIN LOGIN
-- =====================================================

-- Tabla para logs de seguridad (eventos maliciosos)
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_evento ENUM('form_attack', 'injection_attempt', 'spam_detected', 'rate_limit', 'suspicious_ip', 'file_upload_attack') NOT NULL,
    severidad ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    url_origen VARCHAR(500),
    datos_evento JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_ip_fecha (ip_address, created_at),
    INDEX idx_severidad_fecha (severidad, created_at),
    INDEX idx_tipo_evento (tipo_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para IPs bloqueadas temporalmente
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    motivo VARCHAR(255) NOT NULL,
    bloqueado_hasta TIMESTAMP NOT NULL,
    intentos_maliciosos INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_ip_vigencia (ip_address, bloqueado_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para control de rate limiting por IP
CREATE TABLE IF NOT EXISTS rate_limiting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    accion VARCHAR(50) NOT NULL, -- 'form_contact', 'form_reservation', 'api_call'
    contador INT DEFAULT 1,
    ventana_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_ip_accion (ip_address, accion),
    INDEX idx_ventana_inicio (ventana_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para intentos de contacto sospechosos
CREATE TABLE IF NOT EXISTS contact_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255),
    nombre VARCHAR(255),
    mensaje_muestra TEXT, -- Primeros 200 caracteres del mensaje
    es_spam BOOLEAN DEFAULT FALSE,
    motivo_bloqueo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_ip_fecha (ip_address, created_at),
    INDEX idx_email_fecha (email, created_at),
    INDEX idx_spam (es_spam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para configuración de seguridad específica del turismo
CREATE TABLE IF NOT EXISTS security_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    descripcion TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración inicial específica para turismo
INSERT INTO security_config (config_key, config_value, descripcion) VALUES
('max_contactos_por_hora', '5', 'Máximo de formularios de contacto por IP por hora'),
('max_reservas_por_dia', '3', 'Máximo de reservas por IP por día'),
('bloqueo_duracion_minutos', '30', 'Duración del bloqueo temporal en minutos'),
('palabras_spam', '["casino", "viagra", "crypto", "bitcoin", "loan", "money", "win", "free money", "click here", "urgent"]', 'Lista de palabras que indican spam'),
('dominios_email_bloqueados', '["tempmail.org", "10minutemail.com", "guerrillamail.com"]', 'Dominios de email temporal bloqueados'),
('paises_bloqueados', '[]', 'Códigos de países bloqueados (ej: ["CN", "RU"])'),
('activar_bloqueo_ip', '1', 'Activar bloqueo automático de IPs (1=sí, 0=no)'),
('log_retention_days', '30', 'Días para mantener logs de seguridad'),
('nivel_seguridad', 'medium', 'Nivel de seguridad: low, medium, high')
ON DUPLICATE KEY UPDATE 
config_value = VALUES(config_value),
descripcion = VALUES(descripcion);

-- Procedimiento para limpiar datos antiguos
DELIMITER //
CREATE PROCEDURE CleanSecurityData()
BEGIN
    DECLARE retention_days INT DEFAULT 30;
    
    -- Obtener configuración de retención
    SELECT CAST(config_value AS SIGNED) INTO retention_days 
    FROM security_config 
    WHERE config_key = 'log_retention_days'
    LIMIT 1;
    
    -- Limpiar logs antiguos
    DELETE FROM security_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    -- Limpiar intentos de contacto antiguos
    DELETE FROM contact_attempts 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    -- Limpiar IPs desbloqueadas
    DELETE FROM blocked_ips 
    WHERE bloqueado_hasta < NOW();
    
    -- Limpiar rate limiting antiguos (mayores a 24 horas)
    DELETE FROM rate_limiting 
    WHERE ventana_inicio < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    -- Log de limpieza
    INSERT INTO security_logs (tipo_evento, severidad, ip_address, user_agent, datos_evento) 
    VALUES ('system_cleanup', 'low', '127.0.0.1', 'SYSTEM', '{"action": "cleanup_completed"}');
END //
DELIMITER ;

-- Evento para limpieza automática diaria
CREATE EVENT IF NOT EXISTS daily_security_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  CALL CleanSecurityData();

-- Vista para resumen de seguridad por día
CREATE VIEW security_daily_summary AS
SELECT 
    DATE(created_at) as fecha,
    tipo_evento,
    severidad,
    COUNT(*) as total_eventos,
    COUNT(DISTINCT ip_address) as ips_unicas
FROM security_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at), tipo_evento, severidad
ORDER BY fecha DESC, total_eventos DESC;

-- Vista para IPs más problemáticas
CREATE VIEW ips_problematicas AS
SELECT 
    ip_address,
    COUNT(*) as total_eventos,
    COUNT(CASE WHEN severidad IN ('high', 'critical') THEN 1 END) as eventos_criticos,
    MAX(created_at) as ultimo_evento,
    CASE 
        WHEN COUNT(*) > 20 THEN 'Crítico'
        WHEN COUNT(*) > 10 THEN 'Alto'
        WHEN COUNT(*) > 5 THEN 'Medio'
        ELSE 'Bajo'
    END as nivel_amenaza
FROM security_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY ip_address
HAVING total_eventos > 3
ORDER BY eventos_criticos DESC, total_eventos DESC;

-- Función para verificar si una IP está bloqueada
DELIMITER //
CREATE FUNCTION IsIPBlocked(check_ip VARCHAR(45)) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE is_blocked BOOLEAN DEFAULT FALSE;
    
    SELECT COUNT(*) > 0 INTO is_blocked
    FROM blocked_ips 
    WHERE ip_address = check_ip 
    AND bloqueado_hasta > NOW();
    
    RETURN is_blocked;
END //
DELIMITER ;

-- Función para verificar rate limiting
DELIMITER //
CREATE FUNCTION CheckRateLimit(check_ip VARCHAR(45), action_type VARCHAR(50), max_attempts INT) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE current_count INT DEFAULT 0;
    DECLARE is_within_limit BOOLEAN DEFAULT TRUE;
    
    -- Contar intentos en la última hora
    SELECT COUNT(*) INTO current_count
    FROM rate_limiting 
    WHERE ip_address = check_ip 
    AND accion = action_type
    AND ventana_inicio > DATE_SUB(NOW(), INTERVAL 1 HOUR);
    
    IF current_count >= max_attempts THEN
        SET is_within_limit = FALSE;
    END IF;
    
    RETURN is_within_limit;
END //
DELIMITER ;

-- Trigger para contactos - detectar spam automáticamente
DELIMITER //
CREATE TRIGGER after_contact_insert
AFTER INSERT ON contact_attempts
FOR EACH ROW
BEGIN
    DECLARE spam_words JSON;
    DECLARE spam_count INT DEFAULT 0;
    
    -- Obtener palabras spam de configuración
    SELECT config_value INTO spam_words
    FROM security_config 
    WHERE config_key = 'palabras_spam';
    
    -- Contar cuántas palabras spam contiene el mensaje (simplificado)
    IF NEW.mensaje_muestra REGEXP 'casino|viagra|crypto|bitcoin|loan|money|win|free money|click here|urgent' THEN
        UPDATE contact_attempts 
        SET es_spam = TRUE, motivo_bloqueo = 'Contiene palabras spam'
        WHERE id = NEW.id;
        
        -- Log del evento spam
        INSERT INTO security_logs (tipo_evento, severidad, ip_address, datos_evento) 
        VALUES ('spam_detected', 'medium', NEW.ip_address, 
                JSON_OBJECT('email', NEW.email, 'reason', 'spam_words'));
    END IF;
END //
DELIMITER ;

-- Índices adicionales para optimización en aplicación de turismo
CREATE INDEX idx_security_logs_turismo ON security_logs (created_at DESC, tipo_evento, ip_address);
CREATE INDEX idx_contact_attempts_recent ON contact_attempts (created_at DESC, es_spam);
CREATE INDEX idx_rate_limiting_active ON rate_limiting (ip_address, accion, ventana_inicio);

-- Insertar algunos logs de ejemplo para testing (opcional)
-- INSERT INTO security_logs (tipo_evento, severidad, ip_address, user_agent, url_origen, datos_evento) VALUES
-- ('form_attack', 'high', '192.168.1.100', 'Mozilla/5.0...', '/contacto.html', '{"attack_type": "sql_injection", "field": "mensaje"}'),
-- ('rate_limit', 'medium', '192.168.1.101', 'Mozilla/5.0...', '/api/procesar_contacto.php', '{"attempts": 6, "limit": 5}');

COMMIT;
