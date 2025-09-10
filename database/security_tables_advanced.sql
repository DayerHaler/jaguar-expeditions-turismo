-- JAGUAR EXPEDITIONS - TABLAS DE SEGURIDAD
-- Sistema de protección contra inyección SQL y ataques de día cero
-- Version: 2.0

-- Tabla de logs de seguridad
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    url VARCHAR(500),
    event_data JSON,
    severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'MEDIUM',
    INDEX idx_timestamp (timestamp),
    INDEX idx_event_type (event_type),
    INDEX idx_ip_address (ip_address),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de IPs bloqueadas
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL UNIQUE,
    blocked_at INT NOT NULL,
    blocked_until INT NOT NULL,
    reason VARCHAR(500),
    attempts INT DEFAULT 1,
    INDEX idx_ip (ip),
    INDEX idx_blocked_until (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de rate limiting
CREATE TABLE IF NOT EXISTS rate_limiting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    timestamp INT NOT NULL,
    INDEX idx_ip_action (ip, action),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de intentos de contacto para detectar spam
CREATE TABLE IF NOT EXISTS contact_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(255),
    attempts_count INT DEFAULT 1,
    last_attempt DATETIME NOT NULL,
    status ENUM('NORMAL', 'SUSPICIOUS', 'BLOCKED') DEFAULT 'NORMAL',
    INDEX idx_ip (ip),
    INDEX idx_email (email),
    INDEX idx_last_attempt (last_attempt),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración de seguridad
CREATE TABLE IF NOT EXISTS security_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración inicial
INSERT INTO security_config (config_key, config_value, description) VALUES
('max_contact_attempts_per_hour', '3', 'Máximo número de intentos de contacto por hora por IP'),
('max_reservation_attempts_per_hour', '5', 'Máximo número de intentos de reserva por hora por IP'),
('max_search_attempts_per_hour', '10', 'Máximo número de búsquedas por hora por IP'),
('ip_block_duration_minutes', '5', 'Duración del bloqueo de IP en minutos'),
('security_level', 'HIGH', 'Nivel de seguridad: LOW, MEDIUM, HIGH'),
('enable_csrf_protection', '1', 'Habilitar protección CSRF'),
('enable_rate_limiting', '1', 'Habilitar limitación de velocidad'),
('enable_ip_blocking', '1', 'Habilitar bloqueo de IPs'),
('log_security_events', '1', 'Registrar eventos de seguridad'),
('auto_clean_logs_days', '30', 'Días después de los cuales limpiar logs automáticamente')
ON DUPLICATE KEY UPDATE 
    config_value = VALUES(config_value),
    description = VALUES(description);

-- Tabla de whitelist de IPs (opcional)
CREATE TABLE IF NOT EXISTS ip_whitelist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar IPs de desarrollo en whitelist
INSERT INTO ip_whitelist (ip, description) VALUES
('127.0.0.1', 'Localhost'),
('::1', 'IPv6 Localhost')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Tabla de patrones maliciosos detectados
CREATE TABLE IF NOT EXISTS threat_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pattern_type ENUM('SQL_INJECTION', 'XSS', 'ZERO_DAY', 'SPAM') NOT NULL,
    pattern_regex TEXT NOT NULL,
    description VARCHAR(500),
    severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'MEDIUM',
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pattern_type (pattern_type),
    INDEX idx_severity (severity),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunos patrones básicos
INSERT INTO threat_patterns (pattern_type, pattern_regex, description, severity) VALUES
('SQL_INJECTION', '/UNION.*SELECT/i', 'Patrón de UNION SELECT clásico', 'HIGH'),
('SQL_INJECTION', '/\';.*--/i', 'Patrón de comentario SQL', 'HIGH'),
('XSS', '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', 'Tag script malicioso', 'HIGH'),
('XSS', '/<[^>]*on\w+[\\s]*=[^>]*>/i', 'Eventos JavaScript inline', 'MEDIUM'),
('ZERO_DAY', '/\.\.\//i', 'Path traversal básico', 'MEDIUM'),
('ZERO_DAY', '/\/etc\/passwd/i', 'Intento de acceso a passwd', 'CRITICAL'),
('SPAM', '/viagra|casino|lottery|inheritance/i', 'Palabras típicas de spam', 'LOW')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Procedimiento para limpiar logs antiguos
DELIMITER $$
CREATE PROCEDURE CleanOldSecurityLogs()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Obtener días de retención desde configuración
    SET @retention_days = (SELECT config_value FROM security_config WHERE config_key = 'auto_clean_logs_days' LIMIT 1);
    SET @retention_days = IFNULL(@retention_days, 30);
    
    -- Limpiar logs antiguos
    DELETE FROM security_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL @retention_days DAY);
    
    -- Limpiar rate limiting antiguo (más de 1 hora)
    DELETE FROM rate_limiting WHERE timestamp < UNIX_TIMESTAMP() - 3600;
    
    -- Limpiar IPs bloqueadas expiradas
    DELETE FROM blocked_ips WHERE blocked_until < UNIX_TIMESTAMP();
    
    -- Limpiar intentos de contacto antiguos (más de 24 horas)
    DELETE FROM contact_attempts WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    COMMIT;
END$$
DELIMITER ;

-- Evento para limpieza automática (ejecutar cada día a las 2 AM)
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS CleanSecurityLogsDaily
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE() + INTERVAL 1 DAY, ' 02:00:00')
DO
  CALL CleanOldSecurityLogs();

-- Función para verificar si una IP está en whitelist
DELIMITER $$
CREATE FUNCTION IsIPWhitelisted(ip_to_check VARCHAR(45)) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE ip_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO ip_count 
    FROM ip_whitelist 
    WHERE ip = ip_to_check;
    
    RETURN ip_count > 0;
END$$
DELIMITER ;

-- Vista para estadísticas de seguridad
CREATE VIEW security_stats AS
SELECT 
    DATE(timestamp) as date,
    event_type,
    COUNT(*) as event_count,
    COUNT(DISTINCT ip_address) as unique_ips,
    severity
FROM security_logs 
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(timestamp), event_type, severity
ORDER BY date DESC, event_count DESC;

-- Vista para IPs más activas
CREATE VIEW top_active_ips AS
SELECT 
    ip_address,
    COUNT(*) as total_events,
    COUNT(DISTINCT event_type) as event_types,
    MAX(timestamp) as last_activity,
    (SELECT COUNT(*) FROM blocked_ips WHERE ip = sl.ip_address AND blocked_until > UNIX_TIMESTAMP()) as is_blocked
FROM security_logs sl
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY ip_address
ORDER BY total_events DESC
LIMIT 20;

-- Trigger para auto-bloqueo de IPs sospechosas
DELIMITER $$
CREATE TRIGGER AutoBlockSuspiciousIP
    AFTER INSERT ON security_logs
    FOR EACH ROW
BEGIN
    DECLARE threat_count INT DEFAULT 0;
    DECLARE is_whitelisted BOOLEAN DEFAULT FALSE;
    
    -- Verificar si la IP está en whitelist
    SET is_whitelisted = IsIPWhitelisted(NEW.ip_address);
    
    -- Si está en whitelist, no hacer nada
    IF is_whitelisted THEN
        LEAVE AutoBlockSuspiciousIP;
    END IF;
    
    -- Contar eventos críticos en la última hora
    SELECT COUNT(*) INTO threat_count
    FROM security_logs 
    WHERE ip_address = NEW.ip_address 
      AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
      AND (severity = 'CRITICAL' OR event_type IN ('malicious_input_detected', 'sql_injection_attempt', 'xss_attempt', 'zero_day_attempt'));
    
    -- Si hay más de 3 eventos críticos, bloquear por 15 minutos
    IF threat_count >= 3 THEN
        INSERT INTO blocked_ips (ip, blocked_at, blocked_until, reason, attempts)
        VALUES (NEW.ip_address, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 900, 'Auto-blocked due to suspicious activity', threat_count)
        ON DUPLICATE KEY UPDATE 
            blocked_until = UNIX_TIMESTAMP() + 900,
            attempts = attempts + 1,
            reason = 'Auto-blocked due to repeated suspicious activity';
    END IF;
END$$
DELIMITER ;

-- Índices adicionales para optimización
CREATE INDEX idx_security_logs_recent ON security_logs (timestamp DESC, severity);
CREATE INDEX idx_security_logs_ip_time ON security_logs (ip_address, timestamp);
CREATE INDEX idx_contact_attempts_composite ON contact_attempts (ip, last_attempt, status);

-- Insertar log inicial del sistema
INSERT INTO security_logs (timestamp, event_type, ip_address, user_agent, url, event_data, severity)
VALUES (NOW(), 'security_system_initialized', '127.0.0.1', 'System', '/install', '{"version": "2.0", "tables_created": true}', 'LOW');

-- Mostrar resumen de instalación
SELECT 
    'Security tables created successfully' as status,
    COUNT(*) as total_config_items
FROM security_config;

SELECT 
    'Security system ready' as message,
    (SELECT config_value FROM security_config WHERE config_key = 'security_level') as current_level,
    NOW() as installation_time;
