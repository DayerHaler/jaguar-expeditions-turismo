/**
 * JAGUAR SECURITY SYSTEM - VERSIÓN SIMPLIFICADA SIN ERRORES
 * Sistema de protección para formularios web
 * Versión: 2.1 (Estable)
 */

class JaguarSecurity {
    constructor(config = {}) {
        this.config = {
            securityLevel: 'MEDIUM',
            enableRealTimeValidation: true,
            enableCSRFProtection: true,
            enableRateLimit: true,
            maxRequestsPerMinute: 10,
            logToConsole: true,
            blockMaliciousPatterns: true,
            ...config
        };
        
        this.logs = [];
        this.requestCounts = new Map();
        this.blockedIPs = new Set();
        this.csrfTokens = new Map();
        
        this.init();
    }

    init() {
        try {
            console.log('🛡️ Jaguar Security System Iniciando...');
            
            this.setupFormProtection();
            this.setupCSRFTokens();
            this.enableRealTimeValidation();
            
            console.log('🛡️ Jaguar Security System Activado - Nivel: ' + this.config.securityLevel);
            this.log('security_system_initialized', { level: this.config.securityLevel });
        } catch (error) {
            console.error('❌ Error inicializando Jaguar Security:', error);
        }
    }

    // Configurar protección de formularios
    setupFormProtection() {
        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                this.protectForm(form);
            });
        });
    }

    // Proteger formulario individual
    protectForm(form) {
        if (!form) return;

        // Agregar token CSRF
        this.addCSRFToken(form);

        // Agregar honeypot
        this.addHoneypot(form);

        // Validación en tiempo real
        if (this.config.enableRealTimeValidation) {
            this.addRealTimeValidation(form);
        }

        // Protección contra envío múltiple
        this.preventDoubleSubmit(form);
    }

    // Agregar token CSRF
    addCSRFToken(form) {
        try {
            let csrfInput = form.querySelector('input[name="jaguar_csrf_token"]');
            if (!csrfInput) {
                csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'jaguar_csrf_token';
                form.appendChild(csrfInput);
            }
            
            const token = this.generateCSRFToken();
            csrfInput.value = token;
            this.csrfTokens.set(form.id || 'default', token);
        } catch (error) {
            console.warn('⚠️ Error agregando CSRF token:', error);
        }
    }

    // Generar token CSRF
    generateCSRFToken() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let token = 'jaguar_';
        for (let i = 0; i < 16; i++) {
            token += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return token;
    }

    // Agregar honeypot
    addHoneypot(form) {
        try {
            let honeypot = form.querySelector('input[name="honeypot"]');
            if (!honeypot) {
                honeypot = document.createElement('input');
                honeypot.type = 'text';
                honeypot.name = 'honeypot';
                honeypot.style.display = 'none';
                honeypot.style.visibility = 'hidden';
                honeypot.style.position = 'absolute';
                honeypot.style.left = '-9999px';
                honeypot.tabIndex = -1;
                honeypot.autocomplete = 'off';
                form.appendChild(honeypot);
            }
        } catch (error) {
            console.warn('⚠️ Error agregando honeypot:', error);
        }
    }

    // Validación en tiempo real
    addRealTimeValidation(form) {
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.validateInput(e.target);
            });
            
            input.addEventListener('paste', (e) => {
                setTimeout(() => this.validateInput(e.target), 100);
            });
        });
    }

    // Validar entrada individual
    validateInput(input) {
        if (!input || !input.value) return true;

        const value = input.value;
        const threats = this.detectThreats(value);

        if (threats.length > 0) {
            this.handleThreat(input, threats);
            return false;
        }

        return true;
    }

    // Detectar amenazas
    detectThreats(input) {
        const threats = [];
        
        // Patrones SQL Injection básicos
        const sqlPatterns = [
            /(\bunion\s+select\b)/i,
            /(\bselect\s+.*\bfrom\b)/i,
            /(\binsert\s+into\b)/i,
            /(\bdelete\s+from\b)/i,
            /(\bdrop\s+table\b)/i,
            /(exec\s*\()/i,
            /(\bxp_cmdshell\b)/i
        ];

        // Patrones XSS básicos
        const xssPatterns = [
            /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
            /<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi,
            /javascript\s*:/i,
            /on\w+\s*=/i,
            /<img[^>]+src\s*=\s*[\"']?\s*javascript:/i
        ];

        // Verificar SQL
        sqlPatterns.forEach(pattern => {
            if (pattern.test(input)) {
                threats.push('SQL_INJECTION');
            }
        });

        // Verificar XSS
        xssPatterns.forEach(pattern => {
            if (pattern.test(input)) {
                threats.push('XSS');
            }
        });

        return threats;
    }

    // Manejar amenaza detectada
    handleThreat(input, threats) {
        this.log('threat_detected', {
            threats: threats,
            field: input.name || 'unknown',
            value_length: input.value.length
        });

        if (this.config.securityLevel === 'HIGH') {
            // En nivel alto, limpiar el input
            input.value = this.sanitizeInput(input.value);
            input.style.borderColor = '#ff6b6b';
            
            // Mostrar advertencia
            this.showWarning(input, 'Contenido filtrado por seguridad');
        } else {
            // En nivel medio, solo advertir
            input.style.borderColor = '#ffa500';
        }
    }

    // Sanitizar entrada
    sanitizeInput(input) {
        return input
            .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
            .replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '')
            .replace(/javascript\s*:/gi, '')
            .replace(/on\w+\s*=/gi, '')
            .replace(/(\bunion\s+select\b)/gi, '')
            .replace(/(\bselect\s+.*\bfrom\b)/gi, '')
            .replace(/(\binsert\s+into\b)/gi, '')
            .replace(/(\bdelete\s+from\b)/gi, '')
            .replace(/(\bdrop\s+table\b)/gi, '');
    }

    // Mostrar advertencia
    showWarning(input, message) {
        // Remover advertencias anteriores
        const existingWarning = input.parentNode.querySelector('.jaguar-warning');
        if (existingWarning) {
            existingWarning.remove();
        }

        // Crear nueva advertencia
        const warning = document.createElement('div');
        warning.className = 'jaguar-warning';
        warning.style.cssText = `
            color: #ff6b6b;
            font-size: 12px;
            margin-top: 2px;
            padding: 2px 5px;
            background: #ffe6e6;
            border-radius: 3px;
            border: 1px solid #ffcccc;
        `;
        warning.textContent = message;

        input.parentNode.insertBefore(warning, input.nextSibling);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (warning.parentNode) {
                warning.remove();
            }
            input.style.borderColor = '';
        }, 5000);
    }

    // Prevenir envío doble
    preventDoubleSubmit(form) {
        form.addEventListener('submit', (e) => {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Habilitar validación en tiempo real
    enableRealTimeValidation() {
        if (!this.config.enableRealTimeValidation) return;

        document.addEventListener('input', (e) => {
            if (e.target.matches('input, textarea')) {
                this.validateInput(e.target);
            }
        });
    }

    // Configurar tokens CSRF
    setupCSRFTokens() {
        if (!this.config.enableCSRFProtection) return;

        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                this.addCSRFToken(form);
            });
        });
    }

    // Verificar rate limit
    checkRateLimit() {
        const now = Date.now();
        const ip = 'local'; // En cliente, usamos identificador local
        
        if (!this.requestCounts.has(ip)) {
            this.requestCounts.set(ip, []);
        }

        const requests = this.requestCounts.get(ip);
        const oneMinuteAgo = now - 60000;

        // Filtrar requests del último minuto
        const recentRequests = requests.filter(time => time > oneMinuteAgo);
        
        if (recentRequests.length >= this.config.maxRequestsPerMinute) {
            this.log('rate_limit_exceeded', { ip: ip, requests: recentRequests.length });
            return false;
        }

        // Agregar request actual
        recentRequests.push(now);
        this.requestCounts.set(ip, recentRequests);
        
        return true;
    }

    // Función de logging
    log(event, data = {}) {
        const logEntry = {
            timestamp: new Date().toISOString(),
            event: event,
            data: data,
            level: this.config.securityLevel
        };

        this.logs.push(logEntry);

        if (this.config.logToConsole) {
            console.log('🛡️ Jaguar Security:', logEntry);
        }

        // Mantener solo los últimos 100 logs
        if (this.logs.length > 100) {
            this.logs = this.logs.slice(-100);
        }
    }

    // Obtener estadísticas
    getStats() {
        return {
            totalLogs: this.logs.length,
            recentThreats: this.logs.filter(log => 
                log.event === 'threat_detected' && 
                Date.now() - new Date(log.timestamp).getTime() < 3600000
            ).length,
            activeTokens: this.csrfTokens.size,
            securityLevel: this.config.securityLevel
        };
    }

    // Método público para validar formulario completo
    validateForm(formElement) {
        if (!formElement) return { valid: true, errors: [] };

        const errors = [];
        const inputs = formElement.querySelectorAll('input, textarea, select');

        inputs.forEach(input => {
            if (!this.validateInput(input)) {
                errors.push(`Campo ${input.name || 'sin nombre'} contiene contenido no válido`);
            }
        });

        // Verificar honeypot
        const honeypot = formElement.querySelector('input[name="honeypot"]');
        if (honeypot && honeypot.value) {
            errors.push('Formulario marcado como spam');
            this.log('honeypot_triggered', { form: formElement.id });
        }

        // Verificar rate limit
        if (!this.checkRateLimit()) {
            errors.push('Demasiadas solicitudes. Por favor espera un momento.');
        }

        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
}

// Auto-inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.jaguarSecurity === 'undefined') {
        window.jaguarSecurity = new JaguarSecurity();
        console.log('🛡️ Jaguar Security auto-inicializado');
    }
});

// Exportar para uso global
window.JaguarSecurity = JaguarSecurity;
