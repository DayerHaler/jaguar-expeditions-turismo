// ========================================
// JAGUAR EXPEDITIONS - JAVASCRIPT PRINCIPAL
// ========================================

$(document).ready(function() {
    
    // ========================================
    // INICIALIZACIÓN DEL CARRUSEL SLICK
    // ========================================
    $('.carousel').slick({
        autoplay: true,
        autoplaySpeed: 5000,
        dots: true,
        infinite: true,
        speed: 1000,
        fade: true,
        cssEase: 'ease-in-out',
        pauseOnHover: true,
        pauseOnFocus: true,
        arrows: true,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    arrows: false,
                    dots: true
                }
            },
            {
                breakpoint: 480,
                settings: {
                    arrows: false,
                    dots: true,
                    autoplaySpeed: 4000
                }
            }
        ]
    });

    // ========================================
    // CARRUSEL DE RESEÑAS
    // ========================================
    $('.reviews-carousel').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 4000,
        dots: false,
        arrows: true,
        infinite: true,
        pauseOnHover: true,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: false,
                    dots: true
                }
            }
        ]
    });

    // ========================================
    // NAVEGACIÓN SUAVE
    // ========================================
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 1000);
        }
    });

    // ========================================
    // MENÚ MÓVIL
    // ========================================
    $('.hamburger').click(function(e) {
        e.stopPropagation();
        $('.nav-menu').toggleClass('active');
        $(this).toggleClass('active');
        $('body').toggleClass('menu-open');
    });

    // Cerrar menú al hacer clic en un enlace
    $('.nav-menu a').click(function() {
        $('.nav-menu').removeClass('active');
        $('.hamburger').removeClass('active');
        $('body').removeClass('menu-open');
    });

    // Cerrar menú al hacer clic fuera de él
    $(document).click(function(e) {
        if (!$(e.target).closest('.navbar').length) {
            $('.nav-menu').removeClass('active');
            $('.hamburger').removeClass('active');
            $('body').removeClass('menu-open');
        }
    });

    // Cerrar menú al presionar Escape
    $(document).keydown(function(e) {
        if (e.key === 'Escape') {
            $('.nav-menu').removeClass('active');
            $('.hamburger').removeClass('active');
            $('body').removeClass('menu-open');
        }
    });

    // ========================================
    // BOTÓN SCROLL TO TOP
    // ========================================
    const scrollButton = $('<button>')
        .addClass('scroll-to-top')
        .html('<i class="fas fa-arrow-up"></i>')
        .appendTo('body');

    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            scrollButton.fadeIn();
        } else {
            scrollButton.fadeOut();
        }
    });

    scrollButton.click(function() {
        $('html, body').animate({scrollTop: 0}, 600);
    });

    // ========================================
    // ANIMACIONES EN SCROLL
    // ========================================
    function animateOnScroll() {
        // Animaciones para elementos generales
        $('.animate-on-scroll').each(function() {
            var element = $(this);
            var elementTop = element.offset().top;
            var windowBottom = $(window).scrollTop() + $(window).height();
            
            if (elementTop < windowBottom - 100) {
                element.addClass('animated');
            }
        });
        
        // Animaciones específicas para tour-cards y features
        $('.tour-card, .feature').each(function() {
            var element = $(this);
            var elementTop = element.offset().top;
            var windowBottom = $(window).scrollTop() + $(window).height();
            
            if (elementTop < windowBottom - 100) {
                element.addClass('animate-in');
            }
        });
    }

    $(window).scroll(animateOnScroll);
    animateOnScroll(); // Ejecutar al cargar
    
    // Ejecutar animación también cuando todas las imágenes estén cargadas
    $(window).on('load', function() {
        setTimeout(animateOnScroll, 500);
    });

    // ========================================
    // FORMULARIO DE CONTACTO PRINCIPAL
    // ========================================
    $('.contact-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('.btn-primary');
        const originalText = submitBtn.text();
        
        // Mostrar estado de carga
        submitBtn.text('Enviando...').prop('disabled', true);
        
        // Simular envío (aquí agregarías la lógica real)
        setTimeout(function() {
            showAlert('¡Mensaje enviado correctamente! Te contactaremos pronto.', 'success');
            form[0].reset();
            submitBtn.text(originalText).prop('disabled', false);
        }, 2000);
    });

    // ========================================
    // SISTEMA DE ALERTAS
    // ========================================
    window.showAlert = function(message, type = 'info') {
        const alertTypes = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };

        const alert = $(`
            <div class="custom-alert alert-${type}">
                <div class="alert-content">
                    <i class="fas ${alertTypes[type]}"></i>
                    <span>${message}</span>
                    <button class="alert-close">&times;</button>
                </div>
            </div>
        `);

        $('body').append(alert);
        
        setTimeout(() => alert.addClass('show'), 100);
        
        setTimeout(() => {
            alert.removeClass('show');
            setTimeout(() => alert.remove(), 300);
        }, 4000);

        alert.find('.alert-close').click(() => {
            alert.removeClass('show');
            setTimeout(() => alert.remove(), 300);
        });
    };

    // ========================================
    // MODAL TOUR
    // ========================================
    window.openTourModal = function(tourId) {
        $('#tourModal').fadeIn(300);
        $('body').css('overflow', 'hidden');
        
        // Aquí cargarías los datos específicos del tour
        console.log('Abriendo tour:', tourId);
    };

    $('.modal .close, .modal-overlay').click(function() {
        $('.modal').fadeOut(300);
        $('body').css('overflow', 'auto');
    });

    // ========================================
    // FUNCIONALIDAD EXPERIENCIAS AMAZÓNICAS
    // ========================================
    $('.experience-card').on('click', function() {
        var experienceName = $(this).find('h3').text();
        showAlert('Explorando: ' + experienceName, 'info');
    });

    // ========================================
    // INICIALIZAR FUNCIONALIDADES ESPECÍFICAS
    // ========================================
    initPageSpecificFeatures();

    // ========================================
    // INICIALIZACIÓN FINAL
    // ========================================
    console.log('🐆 Jaguar Expeditions - Sistema cargado correctamente');
});

// ========================================
// FUNCIONALIDADES ESPECÍFICAS POR PÁGINA
// ========================================

function initPageSpecificFeatures() {
    const currentPage = window.location.pathname.toLowerCase();
    
    if (currentPage.includes('contacto')) {
        initContactPage();
    } else if (currentPage.includes('preguntas')) {
        initFAQPage();
    } else if (currentPage.includes('galeria')) {
        initGalleryPage();
    } else if (currentPage.includes('nosotros')) {
        initAboutPage();
    }
}

// ========================================
// PÁGINA DE CONTACTO
// ========================================

function initContactPage() {
    console.log('📞 Inicializando página de Contacto');
    
    // Validación del formulario de contacto
    $('#contactForm').on('submit', function(e) {
        e.preventDefault();
        
        const nombre = $('#nombre').val().trim();
        const email = $('#email').val().trim();
        const mensaje = $('#mensaje').val().trim();
        const terminos = $('#terminos').is(':checked');
        
        // Validaciones básicas
        if (!nombre || nombre.length < 2) {
            showNotification('Por favor, ingresa tu nombre completo', 'error');
            $('#nombre').focus();
            return;
        }
        
        if (!email || !isValidEmail(email)) {
            showNotification('Por favor, ingresa un email válido', 'error');
            $('#email').focus();
            return;
        }
        
        if (!mensaje || mensaje.length < 10) {
            showNotification('Por favor, escribe un mensaje más detallado (mínimo 10 caracteres)', 'error');
            $('#mensaje').focus();
            return;
        }
        
        if (!terminos) {
            showNotification('Debes aceptar los términos y condiciones', 'error');
            $('#terminos').focus();
            return;
        }
        
        // Simular envío del formulario
        const submitBtn = $('.btn-submit');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Enviando...');
        submitBtn.prop('disabled', true);
        
        setTimeout(() => {
            showNotification('¡Mensaje enviado correctamente! Te contactaremos pronto.', 'success');
            this.reset();
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }, 2000);
    });
    
    // Inicializar chat en vivo
    window.openLiveChat = function() {
        showNotification('Función de chat en vivo próximamente disponible. Mientras tanto, contáctanos por WhatsApp.', 'info');
    };
}

// ========================================
// PÁGINA DE PREGUNTAS FRECUENTES
// ========================================

function initFAQPage() {
    console.log('❓ Inicializando página de Preguntas Frecuentes');
    
    // Funcionalidad de acordeón para las preguntas
    $('.faq-question').on('click', function() {
        const faqItem = $(this).closest('.faq-item');
        const isActive = faqItem.hasClass('active');
        
        // Cerrar todas las demás preguntas
        $('.faq-item').removeClass('active');
        
        // Si no estaba activa, abrirla
        if (!isActive) {
            faqItem.addClass('active');
        }
    });
    
    // Filtrado por categorías
    $('.category-btn').on('click', function() {
        const category = $(this).data('category');
        
        // Actualizar botón activo
        $('.category-btn').removeClass('active');
        $(this).addClass('active');
        
        // Mostrar/ocultar categorías
        if (category === 'all') {
            $('.faq-category').show();
        } else {
            $('.faq-category').hide();
            $(`.faq-category[data-category="${category}"]`).show();
        }
        
        // Cerrar todas las preguntas abiertas
        $('.faq-item').removeClass('active');
    });
    
    // Búsqueda en FAQ
    let searchTimeout;
    $('#faqSearchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val().toLowerCase().trim();
        
        searchTimeout = setTimeout(() => {
            if (searchTerm === '') {
                // Mostrar todas las categorías y preguntas
                $('.faq-category').show();
                $('.faq-item').show();
                $('#noResults').hide();
            } else {
                // Buscar en preguntas y respuestas
                let hasResults = false;
                $('.faq-category').hide();
                
                $('.faq-item').each(function() {
                    const question = $(this).find('.faq-question h3').text().toLowerCase();
                    const answer = $(this).find('.faq-answer').text().toLowerCase();
                    
                    if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                        $(this).closest('.faq-category').show();
                        $(this).show();
                        hasResults = true;
                    } else {
                        $(this).hide();
                    }
                });
                
                // Mostrar mensaje de "no resultados" si es necesario
                if (!hasResults) {
                    $('#noResults').show();
                } else {
                    $('#noResults').hide();
                }
            }
        }, 300);
    });
    
    // Botón de búsqueda
    $('#faqSearchBtn').on('click', function() {
        $('#faqSearchInput').trigger('input');
    });
}

// ========================================
// PÁGINA DE GALERÍA
// ========================================

function initGalleryPage() {
    console.log('🖼️ Inicializando página de Galería');
    
    let currentImageIndex = 0;
    let filteredImages = [];
    
    // Filtrado de imágenes
    $('.filter-btn').on('click', function() {
        const filter = $(this).data('filter');
        
        // Actualizar botón activo
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        // Filtrar imágenes
        if (filter === 'all') {
            $('.gallery-item').show();
        } else {
            $('.gallery-item').hide();
            $(`.gallery-item[data-category="${filter}"]`).show();
        }
        
        // Actualizar array de imágenes filtradas para el modal
        updateFilteredImages();
    });
    
    // Modal de imagen
    $('.view-btn').on('click', function(e) {
        e.stopPropagation();
        
        const imageSrc = $(this).data('image');
        const imageTitle = $(this).data('title');
        const imageDescription = $(this).data('description');
        
        // Encontrar el índice de la imagen actual
        currentImageIndex = filteredImages.findIndex(img => img.src === imageSrc);
        
        openImageModal(imageSrc, imageTitle, imageDescription);
    });
    
    // Cerrar modal
    $('.close-modal, .modal-overlay').on('click', function() {
        closeImageModal();
    });
    
    // Navegación en el modal
    $('.prev-btn').on('click', function(e) {
        e.stopPropagation();
        navigateImage(-1);
    });
    
    $('.next-btn').on('click', function(e) {
        e.stopPropagation();
        navigateImage(1);
    });
    
    // Teclas de navegación
    $(document).on('keydown', function(e) {
        if ($('#imageModal').hasClass('active')) {
            if (e.key === 'Escape') {
                closeImageModal();
            } else if (e.key === 'ArrowLeft') {
                navigateImage(-1);
            } else if (e.key === 'ArrowRight') {
                navigateImage(1);
            }
        }
    });
    
    // Funciones auxiliares del modal
    function updateFilteredImages() {
        filteredImages = [];
        $('.gallery-item:visible').each(function() {
            const viewBtn = $(this).find('.view-btn');
            filteredImages.push({
                src: viewBtn.data('image'),
                title: viewBtn.data('title'),
                description: viewBtn.data('description')
            });
        });
    }
    
    function openImageModal(src, title, description) {
        $('#modalImage').attr('src', src);
        $('#modalTitle').text(title);
        $('#modalDescription').text(description);
        $('#imageModal').addClass('active');
        $('body').css('overflow', 'hidden');
    }
    
    function closeImageModal() {
        $('#imageModal').removeClass('active');
        $('body').css('overflow', 'auto');
    }
    
    function navigateImage(direction) {
        if (filteredImages.length === 0) return;
        
        currentImageIndex += direction;
        
        if (currentImageIndex < 0) {
            currentImageIndex = filteredImages.length - 1;
        } else if (currentImageIndex >= filteredImages.length) {
            currentImageIndex = 0;
        }
        
        const currentImage = filteredImages[currentImageIndex];
        openImageModal(currentImage.src, currentImage.title, currentImage.description);
    }
    
    // Inicializar array de imágenes filtradas
    updateFilteredImages();
}

// ========================================
// PÁGINA DE NOSOTROS
// ========================================

function initAboutPage() {
    console.log('👥 Inicializando página de Nosotros');
    
    // Carrusel de testimonios del equipo
    if ($('.testimonials-carousel').length && $('.testimonial-card').length > 1) {
        $('.testimonials-carousel').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 5000,
            dots: true,
            arrows: false,
            fade: true,
            cssEase: 'ease-in-out',
            pauseOnHover: true
        });
    }
    
    // Contador animado para estadísticas
    function animateCounters() {
        $('.stat-number').each(function() {
            const $this = $(this);
            const countTo = $this.text().replace(/[^\d]/g, '');
            
            if (countTo) {
                $({ countNum: 0 }).animate({ countNum: countTo }, {
                    duration: 2000,
                    easing: 'swing',
                    step: function() {
                        const suffix = $this.text().includes('+') ? '+' : '';
                        $this.text(Math.floor(this.countNum) + suffix);
                    },
                    complete: function() {
                        const suffix = $this.text().includes('+') ? '+' : '';
                        $this.text(this.countNum + suffix);
                    }
                });
            }
        });
    }
    
    // Iniciar contadores cuando estén visibles
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.gallery-stats').forEach(section => {
        observer.observe(section);
    });
}

// ========================================
// FUNCIONES AUXILIARES
// ========================================

// Validación de email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Sistema de notificaciones mejorado
function showNotification(message, type = 'info') {
    // Remover notificaciones existentes
    $('.notification').remove();
    
    const notification = $(`
        <div class="notification notification-${type}">
            <div class="notification-content">
                <i class="fas ${getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        </div>
    `);
    
    $('body').append(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.addClass('show');
    }, 100);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        hideNotification(notification);
    }, 5000);
    
    // Cerrar manualmente
    notification.find('.notification-close').on('click', () => {
        hideNotification(notification);
    });
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        default: return 'fa-info-circle';
    }
}

function hideNotification(notification) {
    notification.removeClass('show');
    setTimeout(() => {
        notification.remove();
    }, 300);
}

// ========================================
// ESTILOS CSS DINÁMICOS
// ========================================

// Agregar estilos CSS para las notificaciones y otros componentes
$(document).ready(function() {
    $('<style>').text(`
        /* Estilos para notificaciones */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification-content {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notification-info { border-left: 4px solid #2196f3; }
        .notification-success { border-left: 4px solid #4caf50; }
        .notification-warning { border-left: 4px solid #ff9800; }
        .notification-error { border-left: 4px solid #f44336; }
        
        .notification i {
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .notification-info i { color: #2196f3; }
        .notification-success i { color: #4caf50; }
        .notification-warning i { color: #ff9800; }
        .notification-error i { color: #f44336; }
        
        .notification span {
            flex: 1;
            color: #333;
            line-height: 1.4;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .notification-close:hover {
            background: #f5f5f5;
            color: #333;
        }
        
        /* Estilos para alertas personalizadas */
        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            min-width: 300px;
            max-width: 500px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .custom-alert.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .alert-content {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success { border-left: 4px solid #4caf50; }
        .alert-error { border-left: 4px solid #f44336; }
        .alert-warning { border-left: 4px solid #ff9800; }
        .alert-info { border-left: 4px solid #2196f3; }
        
        .alert-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #999;
            margin-left: auto;
        }
        
        /* Botón scroll to top */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            transition: all 0.3s ease;
            display: none;
        }
        
        .scroll-to-top:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        /* Responsive para notificaciones */
        @media (max-width: 480px) {
            .notification,
            .custom-alert {
                top: 10px;
                right: 10px;
                left: 10px;
                min-width: auto;
                max-width: none;
            }
            
            .notification-content,
            .alert-content {
                padding: 15px;
            }
            
            .scroll-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
        }
    `).appendTo('head');
});

// ========================================
// ESTADO DE RESERVA - FUNCIONALIDAD
// ========================================

// Variables globales para estado de reserva
let tiempoRestante = 0;
let contadorInterval = null;

$(document).ready(function() {
    // Si estamos en la página de estado de reserva
    if (window.location.pathname.includes('estado_reserva.html')) {
        inicializarEstadoReserva();
    }
});

function inicializarEstadoReserva() {
    const urlParams = new URLSearchParams(window.location.search);
    const codigoReserva = urlParams.get('codigo');
    const email = urlParams.get('email');
    
    if (codigoReserva && codigoReserva.trim() !== '') {
        // Solo consultar si hay un código válido en la URL
        consultarReserva(codigoReserva, email);
    } else {
        // Mostrar formulario de búsqueda por defecto
        mostrarFormularioBusqueda();
    }
}

function mostrarFormularioBusqueda() {
    const contenido = `
        <div class="estado-reserva-form">
            <div class="form-header">
                <i class="fas fa-search" style="font-size: 48px; color: var(--primary-green); margin-bottom: 20px;"></i>
                <h2>Consultar Estado de Reserva</h2>
                <p>Ingresa tu código de reserva y email para verificar el estado</p>
            </div>
            
            <form id="form-busqueda" class="reservation-search-form">
                <div class="form-group">
                    <label for="codigo-busqueda">Código de Reserva:</label>
                    <input type="text" id="codigo-busqueda" placeholder="Ej: JE123456" 
                           style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;"
                           required>
                    <small class="form-help">Código que recibiste en tu email de confirmación</small>
                </div>
                <div class="form-group">
                    <label for="email-busqueda">Email de contacto:</label>
                    <input type="email" id="email-busqueda" placeholder="tu-email@ejemplo.com" 
                           style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;"
                           required>
                    <small class="form-help">Email usado al hacer la reserva</small>
                </div>
                <button type="submit" class="btn-accion btn-info">
                    <i class="fas fa-search"></i> Consultar Estado
                </button>
            </form>
            
            <div class="help-section">
                <p><strong>¿No tienes tu código de reserva?</strong></p>
                <p>Contáctanos al <strong>+51 999 123 456</strong> o escríbenos a <strong>info@jaguarexpeditions.com</strong></p>
            </div>
        </div>
    `;
    
    $('#estado-contenido').html(contenido);
    
    // Manejar envío del formulario
    $('#form-busqueda').on('submit', function(e) {
        e.preventDefault();
        const codigo = $('#codigo-busqueda').val().trim();
        const email = $('#email-busqueda').val().trim();
        
        if (!codigo) {
            alert('Por favor ingresa el código de reserva');
            return;
        }
        
        if (!email) {
            alert('Por favor ingresa tu email');
            return;
        }
        
        consultarReserva(codigo, email);
    });
}

function consultarReserva(codigo, email) {
    // Validar que los parámetros no estén vacíos
    if (!codigo || codigo.trim() === '') {
        mostrarError('Por favor ingresa un código de reserva válido');
        return;
    }
    
    // Mostrar loading
    $('#estado-contenido').html(`
        <div class="loading">
            <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary-green);"></i>
            <p>Consultando estado de tu reserva...</p>
        </div>
    `);
    
    // Llamada AJAX para consultar la reserva
    $.ajax({
        url: 'api/consultar_reserva.php',
        method: 'POST',
        dataType: 'json',
        data: {
            codigo_reserva: codigo.trim(),
            email: email ? email.trim() : ''
        },
        success: function(response) {
            if (response.success) {
                mostrarEstadoReserva(response.reserva);
            } else {
                mostrarError(response.message || 'No se encontró la reserva');
            }
        },
        error: function() {
            mostrarError('Error al consultar la reserva. Intenta nuevamente.');
        }
    });
}

function mostrarEstadoReserva(reserva) {
    let iconoEstado = '';
    let claseEstado = '';
    let textoEstado = '';
    
    switch (reserva.estado) {
        case 'pendiente':
            iconoEstado = 'fas fa-clock';
            claseEstado = 'estado-pendiente';
            textoEstado = 'Pago Pendiente';
            break;
        case 'confirmada':
            iconoEstado = 'fas fa-check-circle';
            claseEstado = 'estado-confirmado';
            textoEstado = 'Reserva Confirmada';
            break;
        case 'cancelada':
            iconoEstado = 'fas fa-times-circle';
            claseEstado = 'estado-cancelado';
            textoEstado = 'Reserva Cancelada';
            break;
        case 'completada':
            iconoEstado = 'fas fa-check-double';
            claseEstado = 'estado-confirmado';
            textoEstado = 'Tour Completado';
            break;
        default:
            iconoEstado = 'fas fa-question-circle';
            claseEstado = 'estado-pendiente';
            textoEstado = 'Estado Desconocido';
    }
    
    // Generar información de participantes
    let participantesHtml = '';
    if (reserva.tiene_participantes && reserva.participantes.length > 0) {
        participantesHtml = `
            <div style="margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 8px;">
                <h4 style="color: var(--primary-green); margin-bottom: 15px;">
                    <i class="fas fa-users"></i> Participantes Adicionales (${reserva.participantes.length})
                </h4>
                ${reserva.participantes.map((p, index) => `
                    <div style="margin-bottom: 10px; padding: 10px; background: white; border-radius: 5px;">
                        <strong>${index + 1}. ${p.nombre} ${p.apellido}</strong>
                        <br><small>📧 ${p.email || 'No especificado'} | 📱 ${p.celular || 'No especificado'}</small>
                        <br><small>🆔 ${p.tipo_documento}: ${p.documento} | 🎂 ${p.edad ? p.edad + ' años' : 'No especificado'}</small>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Generar historial de pagos
    let historialPagosHtml = '';
    if (reserva.pagos && reserva.pagos.length > 0) {
        historialPagosHtml = `
            <div style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 8px;">
                <h4 style="color: var(--primary-green); margin-bottom: 15px;">
                    <i class="fas fa-credit-card"></i> Historial de Pagos
                </h4>
                ${reserva.pagos.map((pago, index) => {
                    let estadoColor = pago.estado === 'Completado' ? '#28a745' : 
                                     pago.estado === 'Pendiente' ? '#ffc107' : '#dc3545';
                    return `
                        <div style="margin-bottom: 10px; padding: 10px; background: white; border-left: 4px solid ${estadoColor}; border-radius: 5px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Pago ${index + 1}: $${parseFloat(pago.monto).toFixed(2)}</strong>
                                    <br><small>${pago.metodo_pago} | ${pago.fecha_pago ? new Date(pago.fecha_pago).toLocaleDateString('es-ES') : 'Fecha pendiente'}</small>
                                    ${pago.codigo_transaccion ? `<br><small>ID: ${pago.codigo_transaccion}</small>` : ''}
                                </div>
                                <span style="background: ${estadoColor}; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;">
                                    ${pago.estado}
                                </span>
                            </div>
                        </div>
                    `;
                }).join('')}
                <div style="margin-top: 15px; padding: 10px; background: #e8f5e8; border-radius: 5px;">
                    <strong>Total Pagado: $${parseFloat(reserva.total_pagado).toFixed(2)}</strong>
                    ${reserva.monto_restante > 0 ? `<br><span style="color: #dc3545;">Monto Restante: $${parseFloat(reserva.monto_restante).toFixed(2)}</span>` : ''}
                </div>
            </div>
        `;
    }
    
    const contenido = `
        <div>
            <i class="${iconoEstado} icono-estado ${claseEstado}"></i>
            <h2>${textoEstado}</h2>
            
            <div class="codigo-reserva">
                Código: ${reserva.codigo_reserva}
            </div>
            
            <div class="detalles-reserva">
                <h3 style="margin-bottom: 20px; color: var(--primary-green);">Detalles de la Reserva</h3>
                
                <div class="detalle-item">
                    <span><strong>Tour:</strong></span>
                    <span>${reserva.tour_nombre}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Descripción:</strong></span>
                    <span>${reserva.tour_descripcion || 'No disponible'}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Duración:</strong></span>
                    <span>${reserva.tour_duracion || 'No especificada'}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Ubicación:</strong></span>
                    <span>${reserva.tour_ubicacion || 'No especificada'}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Fecha del Tour:</strong></span>
                    <span>${formatearFecha(reserva.fecha_tour)}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Participantes:</strong></span>
                    <span>${reserva.numero_personas} persona${reserva.numero_personas > 1 ? 's' : ''}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Precio por Persona:</strong></span>
                    <span>$${parseFloat(reserva.precio_por_persona).toFixed(2)}</span>
                </div>
                
                ${reserva.descuento > 0 ? `
                <div class="detalle-item">
                    <span><strong>Descuento:</strong></span>
                    <span>-$${parseFloat(reserva.descuento).toFixed(2)}</span>
                </div>
                ` : ''}
                
                <div class="detalle-item">
                    <span><strong>Total:</strong></span>
                    <span style="font-size: 1.2em; font-weight: bold; color: var(--primary-green);">$${parseFloat(reserva.monto_total).toFixed(2)}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Tipo de Pago:</strong></span>
                    <span>${reserva.tipo_pago === 'Cuotas' ? 'Pago por Cuotas' : 'Pago Completo'}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Cliente Responsable:</strong></span>
                    <span>${reserva.cliente_nombre} ${reserva.cliente_apellido}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Email:</strong></span>
                    <span>${reserva.cliente_email}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Teléfono:</strong></span>
                    <span>${reserva.cliente_celular || 'No especificado'}</span>
                </div>
                
                <div class="detalle-item">
                    <span><strong>Documento:</strong></span>
                    <span>${reserva.cliente_tipo_documento}: ${reserva.cliente_documento}</span>
                </div>
                
                ${participantesHtml}
                ${historialPagosHtml}
            </div>
            
            ${generarAccionesSegunEstado(reserva)}
        </div>
    `;
    
    $('#estado-contenido').html(contenido);
    
    // Si hay tiempo límite para pago, iniciar contador
    if (reserva.tiempo_limite) {
        iniciarContadorTiempo(reserva.tiempo_limite);
    }
}

function generarAccionesSegunEstado(reserva) {
    let acciones = '';
    const accionesDisponibles = reserva.acciones_disponibles || [];
    
    // Generar tiempo límite si aplica
    if (reserva.tiempo_limite && !reserva.pago_completo) {
        acciones += `
            <div class="tiempo-limite">
                <p><strong>⏰ Tiempo límite para realizar el pago</strong></p>
                <div id="contador-tiempo" class="contador-tiempo">--:--:--</div>
                <p>Después de este tiempo, la reserva será cancelada automáticamente.</p>
            </div>
        `;
    }
    
    // Generar botones según las acciones disponibles
    let botonesAccion = '';
    
    // Botón para pagar primera cuota
    if (accionesDisponibles.includes('pagar_primera_cuota')) {
        const montoPrimeracuota = (parseFloat(reserva.monto_total) * 0.5).toFixed(2);
        botonesAccion += `
            <a href="reservar.html?completar=${reserva.codigo_reserva}&tipo=primera_cuota" class="btn-accion btn-pagar">
                <i class="fas fa-credit-card"></i> Pagar Primera Cuota ($${montoPrimeracuota})
            </a>
        `;
    }
    
    // Botón para pagar pago completo
    if (accionesDisponibles.includes('pagar_completo')) {
        botonesAccion += `
            <a href="reservar.html?completar=${reserva.codigo_reserva}&tipo=completo" class="btn-accion btn-pagar">
                <i class="fas fa-credit-card"></i> Pagar Total ($${parseFloat(reserva.monto_total).toFixed(2)})
            </a>
        `;
    }
    
    // Botón para pagar segunda cuota
    if (accionesDisponibles.includes('pagar_segunda_cuota')) {
        botonesAccion += `
            <a href="reservar.html?completar=${reserva.codigo_reserva}&tipo=segunda_cuota" class="btn-accion btn-pagar">
                <i class="fas fa-credit-card"></i> Pagar Segunda Cuota ($${parseFloat(reserva.monto_restante).toFixed(2)})
            </a>
        `;
    }
    
    // Botón de reembolso
    if (accionesDisponibles.includes('solicitar_reembolso')) {
        botonesAccion += `
            <button onclick="solicitarReembolso('${reserva.codigo_reserva}')" class="btn-accion" style="background: #17a2b8; color: white;">
                <i class="fas fa-undo"></i> Solicitar Reembolso
            </button>
        `;
    }
    
    // Botón de cancelación
    if (accionesDisponibles.includes('cancelar_reserva')) {
        botonesAccion += `
            <button onclick="cancelarReserva('${reserva.codigo_reserva}')" class="btn-accion btn-cancelar">
                <i class="fas fa-times"></i> Cancelar Reserva
            </button>
        `;
    }
    
    // Acciones para reservas confirmadas/completadas
    if (reserva.estado === 'confirmada' || reserva.estado === 'completada') {
        botonesAccion += `
            <button onclick="window.print()" class="btn-accion btn-info">
                <i class="fas fa-print"></i> Imprimir Comprobante
            </button>
            <a href="contacto.html" class="btn-accion btn-info">
                <i class="fas fa-envelope"></i> Contactar Soporte
            </a>
        `;
    }
    
    // Acciones para reservas canceladas
    if (reserva.estado === 'cancelada') {
        botonesAccion += `
            <a href="tours.html" class="btn-accion btn-info">
                <i class="fas fa-search"></i> Ver Otros Tours
            </a>
        `;
    }
    
    // Agregar botones si hay alguno
    if (botonesAccion) {
        acciones += `
            <div class="acciones-reserva">
                ${botonesAccion}
            </div>
        `;
    }
    
    // Siempre agregar botón de volver al inicio
    acciones += `
        <div class="acciones-reserva" style="margin-top: 20px;">
            <a href="index.html" class="btn-accion btn-volver">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
            <button onclick="mostrarFormularioBusqueda()" class="btn-accion btn-info">
                <i class="fas fa-search"></i> Buscar Otra Reserva
            </button>
        </div>
    `;
    
    return acciones;
}

function iniciarContadorTiempo(tiempoLimite) {
    const ahora = new Date().getTime();
    const limite = new Date(tiempoLimite).getTime();
    tiempoRestante = Math.floor((limite - ahora) / 1000);
    
    if (tiempoRestante <= 0) {
        $('#contador-tiempo').text('TIEMPO AGOTADO');
        $('.tiempo-limite').css('background', '#ffebee');
        return;
    }
    
    contadorInterval = setInterval(function() {
        if (tiempoRestante <= 0) {
            clearInterval(contadorInterval);
            $('#contador-tiempo').text('TIEMPO AGOTADO');
            $('.tiempo-limite').css('background', '#ffebee');
            // Recargar página para actualizar estado
            setTimeout(() => location.reload(), 2000);
            return;
        }
        
        const horas = Math.floor(tiempoRestante / 3600);
        const minutos = Math.floor((tiempoRestante % 3600) / 60);
        const segundos = tiempoRestante % 60;
        
        const tiempo = `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
        $('#contador-tiempo').text(tiempo);
        
        tiempoRestante--;
    }, 1000);
}

function cancelarReserva(codigoReserva) {
    if (!confirm('¿Estás seguro de que deseas cancelar esta reserva?')) {
        return;
    }
    
    $.ajax({
        url: 'api/cancelar_reserva.php',
        method: 'POST',
        dataType: 'json',
        data: {
            codigo_reserva: codigoReserva
        },
        success: function(response) {
            if (response.success) {
                alert('Reserva cancelada exitosamente');
                location.reload();
            } else {
                alert('Error al cancelar la reserva: ' + response.message);
            }
        },
        error: function() {
            alert('Error al procesar la cancelación');
        }
    });
}

function solicitarReembolso(codigoReserva) {
    // Mostrar información de política de reembolso
    const confirmarPolitica = confirm(`🔄 POLÍTICA DE REEMBOLSO

📅 Más de 48 horas antes: Reembolso del 100%
⚠️ Entre 24-48 horas: Reembolso del 75%
❌ Menos de 24 horas: Reembolso del 50%

💰 Reembolsos procesados en 5-7 días hábiles
📧 Recibirás confirmación por email

¿Deseas continuar con la solicitud de reembolso?`);
    
    if (!confirmarPolitica) {
        return;
    }
    
    const motivo = prompt('Por favor, indica el motivo del reembolso (opcional):');
    
    if (motivo === null) {
        return; // Usuario canceló
    }
    
    if (!confirm('¿Estás completamente seguro? Una vez procesado, el reembolso no se puede cancelar.')) {
        return;
    }

    // Mostrar indicador de carga
    const loadingHtml = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;">
            <div style="background: white; padding: 30px; border-radius: 10px; text-align: center;">
                <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary-green);"></i>
                <p style="margin-top: 15px;">Procesando solicitud de reembolso...</p>
            </div>
        </div>
    `;
    $('body').append(loadingHtml);

    $.ajax({
        url: 'api/solicitar_reembolso.php',
        method: 'POST',
        dataType: 'json',
        data: {
            codigo_reserva: codigoReserva,
            motivo: motivo
        },
        success: function(response) {
            $('body').find('div').last().remove(); // Remover loading
            
            if (response.success) {
                alert('✅ Solicitud de reembolso enviada exitosamente.\n\n' +
                      'Recibirás una confirmación por email con los detalles del proceso.\n' +
                      'El reembolso será procesado en 5-7 días hábiles.');
                location.reload();
            } else {
                alert('❌ Error al procesar la solicitud de reembolso: ' + response.message);
            }
        },
        error: function() {
            $('body').find('div').last().remove(); // Remover loading
            alert('❌ Error al procesar la solicitud. Intenta nuevamente o contacta al soporte.');
        }
    });
}

function mostrarError(mensaje) {
    const contenido = `
        <div style="text-align: center;">
            <i class="fas fa-exclamation-triangle icono-estado" style="color: #ff9800;"></i>
            <h2>Error</h2>
            <p style="font-size: 18px; margin: 20px 0;">${mensaje}</p>
            
            <div class="acciones-reserva">
                <button id="btn-intentar-nuevamente" class="btn-accion btn-info">
                    <i class="fas fa-search"></i> Intentar Nuevamente
                </button>
                <a href="index.html" class="btn-accion btn-volver">
                    <i class="fas fa-home"></i> Volver al Inicio
                </a>
            </div>
        </div>
    `;
    
    $('#estado-contenido').html(contenido);
    
    // Agregar event listener para el botón "Intentar Nuevamente"
    $('#btn-intentar-nuevamente').on('click', function() {
        mostrarFormularioBusqueda();
    });
}

function formatearFecha(fecha) {
    const opciones = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        weekday: 'long'
    };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}
