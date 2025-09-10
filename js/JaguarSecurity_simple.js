/**
 * JAGUAR EXPEDITIONS - SISTEMA DE SEGURIDAD JAVASCRIPT SIMPLIFICADO
 * Protección básica sin errores de consola
 * Version: 2.1 (Simplificada)
 */

class JaguarSecurity {
    constructor() {
        this.config = {
            securityLevel: 'MEDIUM',
            maxLength: {
                general: 2000,
                email: 100,
                telefono: 20,
                nombre: 100,
                mensaje: 5000
            },
            rateLimit: {
                maxAttempts: 10,
                windowMs: 60000 // 1 minuto
            }
        };
        
        this.attempts = new Map();
        this.init();
    }
    
    init() {
        this.generateCSRFToken();
        this.setupBasicProtection();
        console.log('🛡️ Jaguar Security System Activated (Simplified)');
    }
    
    // Generar token CSRF básico
    generateCSRFToken() {
        const token = 'jaguar_' + Math.random().toString(36).substr(2, 16) + '_' + Date.now();
        sessionStorage.setItem('jaguar_csrf_token', token);
        
        // Agregar a formularios existentes
        document.querySelectorAll('form').forEach(form => {
            this.addCSRFTokenToForm(form);
        });
        
        return token;
    }
    
    // Agregar token CSRF a formulario
    addCSRFTokenToForm(form) {
        try {
            const existingToken = form.querySelector('input[name="jaguar_csrf_token"]');
            if (existingToken) {
                existingToken.remove();
            }
            
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'jaguar_csrf_token';
            tokenInput.value = sessionStorage.getItem('jaguar_csrf_token');
            form.appendChild(tokenInput);
        } catch (error) {
            console.log('Advertencia: No se pudo agregar token CSRF');
        }
    }
    
    // Protección básica
    setupBasicProtection() {
        // Proteger formularios
        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form.tagName === 'FORM') {
                this.addCSRFTokenToForm(form);
                
                // Rate limiting básico
                if (!this.checkRateLimit()) {
                    alert('Por favor espere un momento antes de enviar otro formulario.');
                    event.preventDefault();
                    return false;
                }
            }
        });
        
        // Proteger inputs en tiempo real
        document.addEventListener('input', (event) => {
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
                this.sanitizeInput(event.target);
            }
        });
    }
    
    // Sanitización básica de inputs
    sanitizeInput(element) {
        try {
            let value = element.value;
            const fieldType = this.getFieldType(element);
            
            // Límite de longitud
            const maxLength = this.config.maxLength[fieldType] || this.config.maxLength.general;
            if (value.length > maxLength) {
                element.value = value.substring(0, maxLength);
                this.showWarning(element, `Máximo ${maxLength} caracteres`);
            }
            
            // Detección de patrones peligrosos básicos
            const dangerousPatterns = [
                /<script[^>]*>/gi,
                /javascript:/gi,
                /on\w+\s*=/gi,
                /\bselect\s+.*\s+from\s+/gi,
                /\bunion\s+select\s+/gi,
                /\binsert\s+into\s+/gi,
                /\bdelete\s+from\s+/gi,
                /\bdrop\s+table\s+/gi
            ];
            
            for (let pattern of dangerousPatterns) {
                if (pattern.test(value)) {
                    console.log('⚠️ Patrón peligroso detectado en input');
                    element.value = value.replace(pattern, '');
                    this.showWarning(element, 'Contenido filtrado por seguridad');
                    break;
                }
            }
        } catch (error) {
            console.log('Advertencia: Error en sanitización de input');
        }
    }
    
    // Determinar tipo de campo
    getFieldType(element) {
        const name = element.name || element.id || '';
        const type = element.type || '';
        
        if (name.includes('email') || type === 'email') return 'email';
        if (name.includes('phone') || name.includes('telefono') || type === 'tel') return 'telefono';
        if (name.includes('nombre') || name.includes('name')) return 'nombre';
        if (name.includes('mensaje') || name.includes('message') || element.tagName === 'TEXTAREA') return 'mensaje';
        
        return 'general';
    }
    
    // Rate limiting básico
    checkRateLimit() {
        const now = Date.now();
        const clientId = this.getClientId();
        
        if (!this.attempts.has(clientId)) {
            this.attempts.set(clientId, []);
        }
        
        const attempts = this.attempts.get(clientId);
        
        // Limpiar intentos antiguos
        const validAttempts = attempts.filter(time => 
            now - time < this.config.rateLimit.windowMs
        );
        
        if (validAttempts.length >= this.config.rateLimit.maxAttempts) {
            return false;
        }
        
        validAttempts.push(now);
        this.attempts.set(clientId, validAttempts);
        
        return true;
    }
    
    // Obtener ID del cliente
    getClientId() {
        let clientId = sessionStorage.getItem('jaguar_client_id');
        if (!clientId) {
            clientId = 'client_' + Math.random().toString(36).substr(2, 12);
            sessionStorage.setItem('jaguar_client_id', clientId);
        }
        return clientId;
    }
    
    // Mostrar advertencia
    showWarning(element, message) {
        try {
            // Remover advertencias anteriores
            const existingWarning = element.parentNode.querySelector('.jaguar-warning');
            if (existingWarning) {
                existingWarning.remove();
            }
            
            // Crear nueva advertencia
            const warning = document.createElement('div');
            warning.className = 'jaguar-warning';
            warning.style.cssText = `
                color: #856404;
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                padding: 5px 10px;
                margin-top: 5px;
                border-radius: 4px;
                font-size: 12px;
            `;
            warning.textContent = message;
            
            element.parentNode.insertBefore(warning, element.nextSibling);
            
            // Auto-remover después de 3 segundos
            setTimeout(() => {
                if (warning && warning.parentNode) {
                    warning.remove();
                }
            }, 3000);
        } catch (error) {
            console.log('Advertencia: No se pudo mostrar warning');
        }
    }
    
    // Validar email básico
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Obtener token CSRF
    getCSRFToken() {
        return sessionStorage.getItem('jaguar_csrf_token');
    }
    
    // Log básico (sin envío al servidor para evitar errores)
    logEvent(eventType, data = {}) {
        console.log(`🔒 Jaguar Security: ${eventType}`, data);
    }
}

// Inicializar automáticamente cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si no existe ya
    if (typeof window.jaguarSecurity === 'undefined') {
        window.jaguarSecurity = new JaguarSecurity();
    }
});

// También inicializar si el DOM ya está cargado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.jaguarSecurity === 'undefined') {
            window.jaguarSecurity = new JaguarSecurity();
        }
    });
} else {
    if (typeof window.jaguarSecurity === 'undefined') {
        window.jaguarSecurity = new JaguarSecurity();
    }
}

// Exportar para uso global
window.JaguarSecurity = JaguarSecurity;
