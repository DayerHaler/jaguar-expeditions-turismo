<?php
/**
 * JAGUAR EXPEDITIONS - SISTEMA DE SEGURIDAD PHP
 * Protección contra Inyección SQL, XSS y Ataques de Día Cero
 * Version: 2.0
 */

class JaguarSecurityPHP {
    private $config;
    private $db;
    private $logFile;
    
    public function __construct($database = null) {
        $this->config = [
            'max_length' => [
                'general' => 2000,
                'email' => 100,
                'telefono' => 20,
                'codigo' => 50,
                'nombre' => 100,
                'mensaje' => 5000
            ],
            'rate_limit_window' => 60, // 1 minuto en segundos
            'max_requests_per_window' => [
                'contact' => 10,
                'reservation' => 15,
                'search' => 25,
                'payment' => 8,
                'general' => 30
            ],
            'csrf_token_length' => 32,
            'security_level' => 'MEDIUM',
            'log_to_file' => true,
            'blocked_ips_duration' => 300 // 5 minutos
        ];
        
        $this->db = $database;
        $this->logFile = __DIR__ . '/../logs/security.log';
        $this->ensureLogDirectory();
        $this->initializeSecurityTables();
    }
    
    /**
     * Validar entrada de datos con protección completa
     */
    public function validateInput($input, $fieldType = 'general', $required = false) {
        $result = [
            'valid' => false,
            'data' => '',
            'errors' => [],
            'threats' => []
        ];
        
        // Verificar si es requerido
        if ($required && empty($input)) {
            $result['errors'][] = 'Campo requerido no puede estar vacío';
            return $result;
        }
        
        if (empty($input)) {
            $result['valid'] = true;
            $result['data'] = '';
            return $result;
        }
        
        // Convertir a string si no lo es
        $input = (string)$input;
        
        // Verificar longitud máxima
        $maxLength = $this->config['max_length'][$fieldType] ?? $this->config['max_length']['general'];
        if (strlen($input) > $maxLength) {
            $result['errors'][] = "Longitud máxima excedida: {$maxLength} caracteres";
            $input = substr($input, 0, $maxLength);
        }
        
        // Detectar amenazas
        $threats = $this->detectThreats($input);
        if (!empty($threats)) {
            $result['threats'] = $threats;
            $this->logSecurityEvent('malicious_input_detected', [
                'threats' => $threats,
                'input' => $this->truncateForLog($input),
                'field_type' => $fieldType,
                'ip' => $this->getClientIP()
            ]);
            
            // En modo HIGH, rechazar completamente
            if ($this->config['security_level'] === 'HIGH') {
                $result['errors'][] = 'Contenido malicioso detectado';
                return $result;
            }
            
            // En modo MEDIUM, limpiar y continuar con advertencia
            $input = $this->cleanMaliciousInput($input);
            $result['warnings'][] = 'Contenido filtrado por seguridad';
        }
        
        // Sanitizar según tipo de campo
        $sanitized = $this->sanitizeByFieldType($input, $fieldType);
        
        // Validar formato específico
        $formatValid = $this->validateFieldFormat($sanitized, $fieldType);
        if (!$formatValid['valid']) {
            $result['errors'] = array_merge($result['errors'], $formatValid['errors']);
            return $result;
        }
        
        $result['valid'] = true;
        $result['data'] = $sanitized;
        
        return $result;
    }
    
    /**
     * Detectar patrones maliciosos
     */
    private function detectThreats($input) {
        $threats = [];
        $normalizedInput = $this->normalizeInput($input);
        
        // Patrones SQL Injection (versión suavizada)
        $sqlPatterns = [
            '/(\b(select|insert|update|delete|drop|create|alter|exec|execute|union|script)\b.*\b(from|into|table|database)\b)/i',
            '/((\%27)|(\')|(\")|(\%22)).*(\b(union|select|insert|update|delete)\b)/i',
            '/\w*((\%27)|(\'))((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i',
            '/((\%27)|(\')).*union.*select/i',
            '/exec(\s|\+)+(s|x)p\w+/i',
            '/UNION.*SELECT.*FROM/i',
            '/SELECT.*FROM.*WHERE.*1.*=.*1/i',
            '/INSERT.*INTO.*VALUES.*\(/i',
            '/UPDATE.*SET.*WHERE/i',
            '/DELETE.*FROM.*WHERE/i',
            '/DROP.*TABLE.*IF.*EXISTS/i',
            '/XP_CMDSHELL/i',
            '/sp_executesql/i',
            '/\';.*--.*\w+/i',
            '/\/\*.*\*\/.*\b(select|union|insert)\b/i',
            '/benchmark\(.*,.*\)/i',
            '/sleep\(\d+\)/i',
            '/waitfor.*delay.*\d+/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $normalizedInput) || preg_match($pattern, $input)) {
                $threats[] = 'SQL_INJECTION';
                break;
            }
        }
        
        // Patrones XSS (versión suavizada)
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/i',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/i',
            '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/i',
            '/<img[^>]+src[\\s]*=[\\s]*[\"\\\']*javascript:/i',
            '/<[^>]*on(click|load|error|focus|blur)[\\s]*=[^>]*>/i',
            '/javascript:[^\"\\\']*alert\(/i',
            '/vbscript:[^\"\\\']*msgbox\(/i',
            '/expression\([^)]*alert[^)]*\)/i',
            '/<svg[^>]*onload[^>]*alert/i',
            '/<%[\s\S]*?%>/i',
            '/<\?php[\s\S]*?\?>/i',
            '/data:text\/html.*base64/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $normalizedInput) || preg_match($pattern, $input)) {
                $threats[] = 'XSS';
                break;
            }
        }
        
        // Patrones de ataques de día cero
        $zeroDayPatterns = [
            '/\.\.\//i',
            '/\.\.\\/i',
            '/%2e%2e%2f/i',
            '/%2e%2e%5c/i',
            '/\/etc\/passwd/i',
            '/\/windows\/system32/i',
            '/\/proc\/self\/environ/i',
            '/\/var\/log\//i',
            '/\$\{jndi:/i',
            '/\$\{.*\}/i',
            '/\#\{.*\}/i',
            '/__.*__/i',
            '/base64_decode/i',
            '/eval\(/i',
            '/system\(/i',
            '/shell_exec/i',
            '/passthru/i',
            '/file_get_contents/i',
            '/curl_exec/i',
            '/wget/i',
            '/chmod/i',
            '/\/dev\/null/i',
            '/\|nc\s/i',
            '/powershell/i',
            '/cmd\.exe/i',
            '/\/bin\/bash/i',
            '/\/bin\/sh/i',
            '/python.*-c/i',
            '/perl.*-e/i',
            '/ruby.*-e/i',
            '/node.*-e/i',
            '/require\(/i',
            '/import\s/i',
            '/include\s/i',
            '/require_once/i',
            '/include_once/i',
            '/file_include/i',
            '/fopen\(/i',
            '/fwrite\(/i',
            '/fputs\(/i'
        ];
        
        foreach ($zeroDayPatterns as $pattern) {
            if (preg_match($pattern, $normalizedInput) || preg_match($pattern, $input)) {
                $threats[] = 'ZERO_DAY';
                break;
            }
        }
        
        // Verificaciones adicionales
        if ($this->hasMultipleEncodingLayers($input)) {
            $threats[] = 'MULTI_ENCODING';
        }
        
        if ($this->hasUrlEncodedPayload($input)) {
            $threats[] = 'URL_ENCODED_PAYLOAD';
        }
        
        if ($this->hasBase64Payload($input)) {
            $threats[] = 'BASE64_PAYLOAD';
        }
        
        return array_unique($threats);
    }
    
    /**
     * Normalizar entrada para mejor detección
     */
    private function normalizeInput($input) {
        // Decodificar URL encoding
        $normalized = urldecode($input);
        
        // Decodificar HTML entities
        $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Convertir a minúsculas para comparación
        return strtolower($normalized);
    }
    
    /**
     * Verificar múltiples capas de encoding
     */
    private function hasMultipleEncodingLayers($input) {
        $decodedLevels = 0;
        $current = $input;
        
        while ($decodedLevels < 5) {
            $decoded = urldecode($current);
            if ($decoded === $current) break;
            $current = $decoded;
            $decodedLevels++;
        }
        
        return $decodedLevels > 2;
    }
    
    /**
     * Verificar payload URL encoded
     */
    private function hasUrlEncodedPayload($input) {
        return preg_match_all('/%[0-9a-fA-F]{2}/', $input) > 3;
    }
    
    /**
     * Verificar payload Base64
     */
    private function hasBase64Payload($input) {
        if (strlen($input) < 20) return false;
        
        // Verificar si es Base64 válido
        $decoded = base64_decode($input, true);
        if ($decoded === false) return false;
        
        // Verificar si el contenido decodificado es sospechoso
        return preg_match('/^[A-Za-z0-9+\/]{4}*([A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/', $input);
    }
    
    /**
     * Limpiar contenido malicioso
     */
    private function cleanMaliciousInput($input) {
        // Remover patrones SQL peligrosos
        $input = preg_replace('/(\b(select|insert|update|delete|drop|create|alter|exec|execute|union|script)\b)/i', '', $input);
        $input = preg_replace('/((\%27)|(\')|(\")|(\%22))/i', '', $input);
        $input = preg_replace('/UNION.*SELECT/i', '', $input);
        $input = preg_replace('/;.*--/i', '', $input);
        
        // Remover patrones XSS
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $input);
        $input = preg_replace('/<[^>]*on\w+[\\s]*=[^>]*>/i', '', $input);
        $input = preg_replace('/javascript:[^\"\\\'*/i', '', $input);
        
        // Remover patrones de día cero
        $input = preg_replace('/\.\.\//i', '', $input);
        $input = preg_replace('/\/etc\/passwd/i', '', $input);
        $input = preg_replace('/\$\{.*\}/i', '', $input);
        
        // Remover caracteres especiales peligrosos
        $input = preg_replace('/[<>\"\'&]/i', '', $input);
        
        return trim($input);
    }
    
    /**
     * Sanitizar por tipo de campo
     */
    private function sanitizeByFieldType($input, $fieldType) {
        switch ($fieldType) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
                
            case 'codigo':
                return preg_replace('/[^a-zA-Z0-9_-]/', '', strtoupper($input));
                
            case 'telefono':
                return preg_replace('/[^0-9+\-\s()]/', '', $input);
                
            case 'nombre':
                return preg_replace('/[^a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]/', '', $input);
                
            case 'numero':
                return preg_replace('/[^0-9.]/', '', $input);
                
            case 'fecha':
                return preg_replace('/[^0-9\-\/]/', '', $input);
                
            case 'mensaje':
                // Para mensajes, permitir más caracteres pero escapar HTML
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
            default:
                // Sanitización general - escape HTML
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Validar formato específico del campo
     */
    private function validateFieldFormat($input, $fieldType) {
        $result = ['valid' => true, 'errors' => []];
        
        switch ($fieldType) {
            case 'email':
                if (!empty($input) && !filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    $result['valid'] = false;
                    $result['errors'][] = 'Formato de email inválido';
                }
                break;
                
            case 'telefono':
                if (!empty($input) && !preg_match('/^[+]?[0-9\-\s()]{7,20}$/', $input)) {
                    $result['valid'] = false;
                    $result['errors'][] = 'Formato de teléfono inválido';
                }
                break;
                
            case 'fecha':
                if (!empty($input)) {
                    $date = DateTime::createFromFormat('Y-m-d', $input);
                    if (!$date || $date->format('Y-m-d') !== $input) {
                        $result['valid'] = false;
                        $result['errors'][] = 'Formato de fecha inválido (YYYY-MM-DD)';
                    }
                }
                break;
                
            case 'numero':
                if (!empty($input) && !is_numeric($input)) {
                    $result['valid'] = false;
                    $result['errors'][] = 'Debe ser un número válido';
                }
                break;
        }
        
        return $result;
    }
    
    /**
     * Verificar rate limiting
     */
    public function checkRateLimit($action = 'general', $clientIP = null) {
        if (!$clientIP) {
            $clientIP = $this->getClientIP();
        }
        
        $limit = $this->config['max_requests_per_window'][$action] ?? $this->config['max_requests_per_window']['general'];
        $window = $this->config['rate_limit_window'];
        
        // Si no hay base de datos, usar verificación básica
        if (!$this->db) {
            return $this->checkRateLimitBasic($clientIP, $action, $limit, $window);
        }
        
        try {
            // Limpiar registros antiguos
            $stmt = $this->db->prepare("DELETE FROM rate_limiting WHERE timestamp < ?");
            $stmt->execute([time() - $window]);
            
            // Contar requests actuales
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM rate_limiting WHERE ip = ? AND action = ? AND timestamp > ?");
            $stmt->execute([$clientIP, $action, time() - $window]);
            $currentRequests = $stmt->fetchColumn();
            
            if ($currentRequests >= $limit) {
                $this->logSecurityEvent('rate_limit_exceeded', [
                    'ip' => $clientIP,
                    'action' => $action,
                    'requests' => $currentRequests,
                    'limit' => $limit
                ]);
                
                // Bloquear IP si excede demasiado
                if ($currentRequests > $limit * 2) {
                    $this->blockIP($clientIP);
                }
                
                return false;
            }
            
            // Registrar request actual
            $stmt = $this->db->prepare("INSERT INTO rate_limiting (ip, action, timestamp) VALUES (?, ?, ?)");
            $stmt->execute([$clientIP, $action, time()]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logSecurityEvent('rate_limit_error', ['error' => $e->getMessage()]);
            return true; // En caso de error, permitir el request
        }
    }
    
    /**
     * Rate limiting básico sin base de datos
     */
    private function checkRateLimitBasic($clientIP, $action, $limit, $window) {
        $sessionKey = "rate_limit_{$clientIP}_{$action}";
        
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $now = time();
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }
        
        // Limpiar registros antiguos
        $_SESSION[$sessionKey] = array_filter($_SESSION[$sessionKey], function($timestamp) use ($now, $window) {
            return $now - $timestamp < $window;
        });
        
        if (count($_SESSION[$sessionKey]) >= $limit) {
            return false;
        }
        
        // Agregar timestamp actual
        $_SESSION[$sessionKey][] = $now;
        
        return true;
    }
    
    /**
     * Verificar si IP está bloqueada
     */
    public function isIPBlocked($clientIP = null) {
        if (!$clientIP) {
            $clientIP = $this->getClientIP();
        }
        
        if (!$this->db) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM blocked_ips WHERE ip = ? AND blocked_until > ?");
            $stmt->execute([$clientIP, time()]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Bloquear IP
     */
    public function blockIP($clientIP, $duration = null) {
        if (!$duration) {
            $duration = $this->config['blocked_ips_duration'];
        }
        
        if (!$this->db) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("INSERT INTO blocked_ips (ip, blocked_at, blocked_until, reason) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE blocked_until = VALUES(blocked_until)");
            $stmt->execute([
                $clientIP,
                time(),
                time() + $duration,
                'Automatic block due to suspicious activity'
            ]);
            
            $this->logSecurityEvent('ip_blocked', [
                'ip' => $clientIP,
                'duration' => $duration
            ]);
            
            return true;
        } catch (Exception $e) {
            $this->logSecurityEvent('ip_block_error', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Validar token CSRF
     */
    public function validateCSRFToken($token, $sessionToken = null) {
        if (!$sessionToken) {
            session_start();
            $sessionToken = $_SESSION['jaguar_csrf_token'] ?? null;
        }
        
        if (empty($token) || empty($sessionToken)) {
            $this->logSecurityEvent('csrf_token_missing', [
                'has_token' => !empty($token),
                'has_session_token' => !empty($sessionToken)
            ]);
            return false;
        }
        
        if (!hash_equals($sessionToken, $token)) {
            $this->logSecurityEvent('csrf_token_invalid', [
                'provided_token' => substr($token, 0, 8) . '...',
                'session_token' => substr($sessionToken, 0, 8) . '...'
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Generar token CSRF
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $token = bin2hex(random_bytes($this->config['csrf_token_length'] / 2));
        $_SESSION['jaguar_csrf_token'] = $token;
        
        return $token;
    }
    
    /**
     * Obtener IP del cliente
     */
    public function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Log de eventos de seguridad
     */
    public function logSecurityEvent($event, $data = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'data' => $data,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ];
        
        // Log a archivo
        if ($this->config['log_to_file']) {
            $this->writeLogToFile($logEntry);
        }
        
        // Log a base de datos si está disponible
        if ($this->db) {
            $this->writeLogToDatabase($logEntry);
        }
    }
    
    /**
     * Escribir log a archivo
     */
    private function writeLogToFile($logEntry) {
        $logLine = date('Y-m-d H:i:s') . ' [' . $logEntry['event'] . '] ' . 
                   'IP: ' . $logEntry['ip'] . ' ' .
                   'Data: ' . json_encode($logEntry['data']) . PHP_EOL;
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Escribir log a base de datos
     */
    private function writeLogToDatabase($logEntry) {
        try {
            $stmt = $this->db->prepare("INSERT INTO security_logs (timestamp, event_type, ip_address, user_agent, url, event_data) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $logEntry['timestamp'],
                $logEntry['event'],
                $logEntry['ip'],
                $logEntry['user_agent'],
                $logEntry['url'],
                json_encode($logEntry['data'])
            ]);
        } catch (Exception $e) {
            // Si falla el log a DB, al menos loguear a archivo
            error_log("Failed to write security log to database: " . $e->getMessage());
        }
    }
    
    /**
     * Asegurar que el directorio de logs existe
     */
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Inicializar tablas de seguridad si no existen
     */
    private function initializeSecurityTables() {
        if (!$this->db) return;
        
        try {
            // Verificar si las tablas existen
            $stmt = $this->db->query("SHOW TABLES LIKE 'security_logs'");
            if ($stmt->rowCount() == 0) {
                // Las tablas no existen, pero no las creamos automáticamente
                // El administrador debe ejecutar security_tables.sql manualmente
                $this->logSecurityEvent('security_tables_missing', [
                    'message' => 'Security tables not found. Please run database/security_tables.sql'
                ]);
            }
        } catch (Exception $e) {
            $this->logSecurityEvent('security_tables_check_error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Truncar string para log
     */
    private function truncateForLog($str, $maxLength = 100) {
        if (strlen($str) > $maxLength) {
            return substr($str, 0, $maxLength) . '...';
        }
        return $str;
    }
    
    /**
     * Obtener estadísticas de seguridad
     */
    public function getSecurityStats() {
        if (!$this->db) {
            return ['error' => 'Database not available'];
        }
        
        try {
            $stats = [];
            
            // Eventos en las últimas 24 horas
            $stmt = $this->db->prepare("SELECT event_type, COUNT(*) as count FROM security_logs WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY event_type");
            $stmt->execute();
            $stats['events_24h'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // IPs bloqueadas actualmente
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM blocked_ips WHERE blocked_until > ?");
            $stmt->execute([time()]);
            $stats['blocked_ips'] = $stmt->fetchColumn();
            
            // Rate limit activo
            $stmt = $this->db->prepare("SELECT COUNT(DISTINCT ip) FROM rate_limiting WHERE timestamp > ?");
            $stmt->execute([time() - $this->config['rate_limit_window']]);
            $stats['active_rate_limits'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Validar formulario completo
     */
    public function validateForm($formData, $formType = 'general') {
        $result = [
            'valid' => true,
            'data' => [],
            'errors' => [],
            'threats' => []
        ];
        
        // Verificar rate limiting
        if (!$this->checkRateLimit($formType)) {
            $result['valid'] = false;
            $result['errors'][] = 'Demasiadas solicitudes. Intente más tarde.';
            return $result;
        }
        
        // Verificar IP bloqueada
        if ($this->isIPBlocked()) {
            $result['valid'] = false;
            $result['errors'][] = 'Acceso temporalmente bloqueado.';
            return $result;
        }
        
        // Verificar CSRF token (solo obligatorio en modo HIGH)
        if (isset($formData['jaguar_csrf_token'])) {
            if (!$this->validateCSRFToken($formData['jaguar_csrf_token'])) {
                if ($this->config['security_level'] === 'HIGH') {
                    $result['valid'] = false;
                    $result['errors'][] = 'Token de seguridad inválido.';
                    return $result;
                } else {
                    // En modo MEDIUM, solo advertir
                    $result['warnings'][] = 'Token de seguridad inválido - procesando con precaución.';
                }
            }
            unset($formData['jaguar_csrf_token']); // Remover del procesamiento
        } else {
            // Si no hay token CSRF
            if ($this->config['security_level'] === 'HIGH') {
                $result['valid'] = false;
                $result['errors'][] = 'Token de seguridad requerido.';
                return $result;
            } else {
                // En modo MEDIUM, solo advertir
                $result['warnings'][] = 'Token de seguridad no proporcionado - procesando con precaución.';
            }
        }
        
        // Validar cada campo
        foreach ($formData as $field => $value) {
            $fieldType = $this->determineFieldType($field);
            $required = $this->isFieldRequired($field, $formType);
            
            $validation = $this->validateInput($value, $fieldType, $required);
            
            if (!$validation['valid']) {
                $result['valid'] = false;
                $result['errors'] = array_merge($result['errors'], $validation['errors']);
            }
            
            if (!empty($validation['threats'])) {
                $result['threats'] = array_merge($result['threats'], $validation['threats']);
            }
            
            $result['data'][$field] = $validation['data'];
        }
        
        // Log resultado de validación
        if (!$result['valid']) {
            $this->logSecurityEvent('form_validation_failed', [
                'form_type' => $formType,
                'errors' => $result['errors'],
                'threats' => $result['threats']
            ]);
        } else {
            $this->logSecurityEvent('form_validation_success', [
                'form_type' => $formType,
                'fields' => array_keys($result['data'])
            ]);
        }
        
        return $result;
    }
    
    /**
     * Determinar tipo de campo basado en el nombre
     */
    private function determineFieldType($fieldName) {
        $fieldName = strtolower($fieldName);
        
        if (strpos($fieldName, 'email') !== false || strpos($fieldName, 'correo') !== false) {
            return 'email';
        }
        if (strpos($fieldName, 'telefono') !== false || strpos($fieldName, 'phone') !== false) {
            return 'telefono';
        }
        if (strpos($fieldName, 'codigo') !== false || strpos($fieldName, 'code') !== false) {
            return 'codigo';
        }
        if (strpos($fieldName, 'nombre') !== false || strpos($fieldName, 'name') !== false) {
            return 'nombre';
        }
        if (strpos($fieldName, 'fecha') !== false || strpos($fieldName, 'date') !== false) {
            return 'fecha';
        }
        if (strpos($fieldName, 'numero') !== false || strpos($fieldName, 'cantidad') !== false || strpos($fieldName, 'personas') !== false) {
            return 'numero';
        }
        if (strpos($fieldName, 'mensaje') !== false || strpos($fieldName, 'message') !== false || strpos($fieldName, 'comentario') !== false) {
            return 'mensaje';
        }
        
        return 'general';
    }
    
    /**
     * Determinar si un campo es requerido
     */
    private function isFieldRequired($fieldName, $formType) {
        $requiredFields = [
            'contact' => ['nombre', 'email', 'mensaje'],
            'reservation' => ['nombre', 'email', 'telefono', 'fecha_tour', 'num_personas'],
            'search' => ['codigo', 'email']
        ];
        
        $required = $requiredFields[$formType] ?? [];
        return in_array(strtolower($fieldName), $required);
    }
}
?>
