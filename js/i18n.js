/**
 * SISTEMA DE TRADUCCIÓN - JAGUAR EXPEDITIONS
 * ==========================================
 * 
 * Sistema de internacionalización simple usando archivos JSON
 * Compatible con cualquier página HTML del proyecto
 */

class JaguarI18n {
    constructor() {
        this.currentLang = 'es';
        this.translations = {};
        this.defaultLang = 'es';
        this.supportedLanguages = ['es', 'en', 'de'];
        this.basePath = 'lang/';
        
        // Detectar idioma inicial
        this.detectInitialLanguage();
        
        // Inicializar al cargar el DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }
    
    /**
     * Detecta el idioma inicial desde localStorage o navegador
     */
    detectInitialLanguage() {
        // 1. Prioridad: localStorage (preferencia guardada)
        const savedLang = localStorage.getItem('jaguar_lang');
        if (savedLang && this.supportedLanguages.includes(savedLang)) {
            this.currentLang = savedLang;
            return;
        }
        
        // 2. Detectar desde navegador
        const browserLang = navigator.language.split('-')[0];
        if (this.supportedLanguages.includes(browserLang)) {
            this.currentLang = browserLang;
        }
        
        // 3. Fallback al idioma por defecto
        console.log(`Idioma detectado: ${this.currentLang}`);
    }
    
    /**
     * Inicialización del sistema
     */
    async init() {
        try {
            // Cargar traducciones del idioma actual
            await this.loadLanguage(this.currentLang);
            
            // Configurar selectores de idioma
            this.setupLanguageSelectors();
            
            // Aplicar traducciones inicial
            this.applyTranslations();
            
            // Configurar observador para contenido dinámico
            this.setupMutationObserver();
            
            console.log(`✅ Sistema de traducción iniciado en: ${this.currentLang}`);
            
        } catch (error) {
            console.error('❌ Error al inicializar traducciones:', error);
            // Fallback al idioma por defecto si hay error
            if (this.currentLang !== this.defaultLang) {
                this.currentLang = this.defaultLang;
                await this.init();
            }
        }
    }
    
    /**
     * Carga las traducciones de un idioma específico
     */
    async loadLanguage(lang) {
        if (this.translations[lang]) {
            return this.translations[lang];
        }
        
        try {
            const response = await fetch(`${this.basePath}${lang}.json`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const translations = await response.json();
            this.translations[lang] = translations;
            
            console.log(`📥 Traducciones cargadas para: ${lang}`);
            return translations;
            
        } catch (error) {
            console.error(`❌ Error cargando idioma ${lang}:`, error);
            throw error;
        }
    }
    
    /**
     * Configura los selectores de idioma en la página
     */
    setupLanguageSelectors() {
        // Seleccionamos selects y también imágenes de banderas (.flag-option)
        const selectSelectors = Array.from(document.querySelectorAll('#language, .language-selector select, .language-selector-flags select, [data-i18n-selector]'));
        const flagImages = Array.from(document.querySelectorAll('.flag-option, .language-selector .flag-option, .flag-selector .flag-option'));

        // Configurar selects (dropdowns)
        selectSelectors.forEach(selector => {
            try {
                // Establecer valor actual cuando sea posible
                if ('value' in selector) selector.value = this.currentLang;

                // Configurar banderas/labels para selects
                this.setupLanguageFlagsForSelector(selector);

                // Remover listeners anteriores y agregar nuevo
                selector.removeEventListener('change', this.handleLanguageChange);
                selector.addEventListener('change', (e) => this.handleLanguageChange(e));
            } catch (err) {
                // No bloquear si el elemento no es un select
                console.warn('setupLanguageSelectors: elemento no tratado como select', selector, err);
            }
        });

        // Configurar imágenes de banderas (click)
        flagImages.forEach(img => {
            const lang = img.getAttribute('data-lang');
            if (!lang) return;

            // Estado inicial
            img.classList.toggle('active', lang === this.currentLang);

            // Remover listener previo si existe (no hay remove fácil para funciones anónimas)
            img.addEventListener('click', async (e) => {
                const newLang = img.getAttribute('data-lang');
                if (!newLang || newLang === this.currentLang) return;

                try {
                    this.showLoadingIndicator();
                    await this.loadLanguage(newLang);
                    this.currentLang = newLang;
                    localStorage.setItem('jaguar_lang', newLang);
                    document.documentElement.lang = newLang;
                    this.applyTranslations();
                    this.syncLanguageSelectors();
                    this.updateLanguageFlags();
                    console.log(`🔄 Idioma cambiado a: ${newLang} (por bandera)`);
                } catch (error) {
                    console.error('Error al cambiar idioma por bandera:', error);
                } finally {
                    this.hideLoadingIndicator();
                }
            });
        });

        // Actualizar banderas/selectores para el idioma actual
        this.updateLanguageFlags();
    }
    
    /**
     * Configura las opciones con banderas para un selector específico
     */
    setupLanguageFlagsForSelector(selector) {
        const options = selector.querySelectorAll('option');
        const languageNames = {
            'es': 'Español',
            'en': 'English', 
            'de': 'Deutsch'
        };
        
        options.forEach(option => {
            const lang = option.value;
            
            // Configurar el texto de las opciones para el dropdown
            if (languageNames[lang]) {
                option.textContent = languageNames[lang];
                option.setAttribute('data-lang', lang);
                option.title = languageNames[lang];
            }
        });
        
        // Añadir tooltip al selector
        const currentLangName = languageNames[this.currentLang] || this.currentLang;
        selector.title = `Idioma actual: ${currentLangName}`;
    }
    
    /**
     * Maneja el cambio de idioma
     */
    handleLanguageChange = async (event) => {
        const newLang = event.target.value;
        
        if (newLang === this.currentLang) return;
        
        try {
            // Mostrar indicador de carga
            this.showLoadingIndicator();
            
            // Cargar nuevo idioma
            await this.loadLanguage(newLang);
            
            // Cambiar idioma actual
            this.currentLang = newLang;
            
            // Guardar preferencia
            localStorage.setItem('jaguar_lang', newLang);
            
            // Actualizar atributo lang del documento
            document.documentElement.lang = newLang;
            
            // Aplicar traducciones
            this.applyTranslations();
            
            // Sincronizar otros selectores
            this.syncLanguageSelectors();
            
            // Actualizar banderas
            this.updateLanguageFlags();
            
            console.log(`🔄 Idioma cambiado a: ${newLang}`);
            
        } catch (error) {
            console.error('❌ Error al cambiar idioma:', error);
            // Revertir selector en caso de error
            event.target.value = this.currentLang;
        } finally {
            this.hideLoadingIndicator();
        }
    }
    
    /**
     * Aplica las traducciones a toda la página
     */
    applyTranslations() {
        if (!this.translations[this.currentLang]) {
            console.warn(`⚠️ No hay traducciones para: ${this.currentLang}`);
            return;
        }
        
        const dict = this.translations[this.currentLang];
        
        // 1. Elementos con data-i18n
        this.translateElements(dict);
        
        // 2. Placeholders
        this.translatePlaceholders(dict);
        
        // 3. Títulos y atributos
        this.translateAttributes(dict);
        
        // 4. Variables dinámicas
        this.translateVariables(dict);
        
        // 5. Actualizar banderas en selectores
        this.updateLanguageFlags();
        
        console.log(`🌐 Traducciones aplicadas: ${this.currentLang}`);
    }
    
    /**
     * Traduce elementos con data-i18n
     */
    translateElements(dict) {
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            const translation = dict[key];
            
            if (translation) {
                // Verificar si es un atributo específico
                const attr = element.getAttribute('data-i18n-attr');
                if (attr) {
                    element.setAttribute(attr, translation);
                } else {
                    // Preservar HTML interno si existe
                    if (element.children.length === 0) {
                        element.textContent = translation;
                    } else {
                        // Solo reemplazar nodos de texto
                        this.replaceTextNodes(element, translation);
                    }
                }
            }
        });
    }
    
    /**
     * Traduce placeholders de inputs
     */
    translatePlaceholders(dict) {
        document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
            const key = element.getAttribute('data-i18n-placeholder');
            const translation = dict[key];
            
            if (translation) {
                element.placeholder = translation;
            }
        });
    }
    
    /**
     * Traduce atributos como title, alt, etc.
     */
    translateAttributes(dict) {
        document.querySelectorAll('[data-i18n-title]').forEach(element => {
            const key = element.getAttribute('data-i18n-title');
            const translation = dict[key];
            
            if (translation) {
                element.title = translation;
            }
        });
    }
    
    /**
     * Maneja variables dinámicas con {{variable}}
     */
    translateVariables(dict) {
        document.querySelectorAll('[data-i18n-vars]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            const varsData = element.getAttribute('data-i18n-vars');
            
            if (key && varsData && dict[key]) {
                try {
                    const variables = JSON.parse(varsData);
                    let text = dict[key];
                    
                    // Reemplazar variables
                    for (const [varName, varValue] of Object.entries(variables)) {
                        const regex = new RegExp(`{{\\s*${varName}\\s*}}`, 'g');
                        text = text.replace(regex, varValue);
                    }
                    
                    element.textContent = text;
                } catch (error) {
                    console.error('Error parseando variables i18n:', error);
                }
            }
        });
    }
    
    /**
     * Reemplaza solo nodos de texto, preservando HTML
     */
    replaceTextNodes(element, newText) {
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        const textNodes = [];
        let node;
        while (node = walker.nextNode()) {
            if (node.textContent.trim()) {
                textNodes.push(node);
            }
        }
        
        if (textNodes.length === 1) {
            textNodes[0].textContent = newText;
        }
    }
    
    /**
     * Sincroniza todos los selectores de idioma
     */
    syncLanguageSelectors() {
        const selectors = document.querySelectorAll('#language, .language-selector select, .language-selector-flags select, [data-i18n-selector]');
        selectors.forEach(selector => {
            if (selector.value !== this.currentLang) {
                selector.value = this.currentLang;
            }
        });
    }
    
    /**
     * Actualiza las banderas en los selectores de idioma
     */
    updateLanguageFlags() {
        const selectors = document.querySelectorAll('#language, .language-selector select, .language-selector-flags select, [data-i18n-selector]');
        const languageNames = {
            'es': 'Español',
            'en': 'English', 
            'de': 'Deutsch'
        };
        
        selectors.forEach(selector => {
            // Remover clases anteriores de idioma
            selector.classList.remove('lang-es', 'lang-en', 'lang-de');
            // Añadir clase del idioma actual
            selector.classList.add(`lang-${this.currentLang}`);
            // Actualizar tooltip
            const currentLangName = languageNames[this.currentLang] || this.currentLang;
            selector.title = `Idioma actual: ${currentLangName}`;
        });
        
        // Actualizar banderas individuales
        const flagImages = document.querySelectorAll('.flag-option');
        flagImages.forEach(flag => {
            const flagLang = flag.getAttribute('data-lang');
            if (flagLang) {
                flag.classList.toggle('active', flagLang === this.currentLang);
            }
        });
    }
    
    /**
     * Configura observador para contenido dinámico
     */
    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            let shouldTranslate = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            if (node.hasAttribute && (
                                node.hasAttribute('data-i18n') ||
                                node.querySelector('[data-i18n]')
                            )) {
                                shouldTranslate = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldTranslate) {
                setTimeout(() => this.applyTranslations(), 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Muestra indicador de carga
     */
    showLoadingIndicator() {
        // Opcional: mostrar spinner o indicador
        document.body.style.cursor = 'wait';
    }
    
    /**
     * Oculta indicador de carga
     */
    hideLoadingIndicator() {
        document.body.style.cursor = '';
    }
    
    /**
     * API pública para traducir texto dinámicamente
     */
    t(key, variables = null) {
        const dict = this.translations[this.currentLang] || this.translations[this.defaultLang];
        
        if (!dict || !dict[key]) {
            console.warn(`⚠️ Traducción no encontrada: ${key}`);
            return key;
        }
        
        let text = dict[key];
        
        // Reemplazar variables si se proporcionan
        if (variables) {
            for (const [varName, varValue] of Object.entries(variables)) {
                const regex = new RegExp(`{{\\s*${varName}\\s*}}`, 'g');
                text = text.replace(regex, varValue);
            }
        }
        
        return text;
    }
    
    /**
     * Obtiene el idioma actual
     */
    getCurrentLanguage() {
        return this.currentLang;
    }
    
    /**
     * Obtiene idiomas soportados
     */
    getSupportedLanguages() {
        return this.supportedLanguages;
    }
    
    /**
     * Obtiene una traducción específica
     */
    getTranslation(key) {
        if (!this.translations[this.currentLang]) {
            return key;
        }
        return this.translations[this.currentLang][key] || key;
    }
}

// Instancia global
window.jaguarI18n = new JaguarI18n();
window.i18n = window.jaguarI18n;

// Función auxiliar global para traducir
window.t = (key, variables = null) => window.i18n.t(key, variables);

console.log('🌍 Sistema de traducción Jaguar Expeditions cargado');
