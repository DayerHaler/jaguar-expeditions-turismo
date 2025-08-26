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
    $('.menu-toggle').click(function() {
        $('.nav-menu').toggleClass('active');
        $(this).toggleClass('active');
        $('body').toggleClass('menu-open');
    });

    // Cerrar menú al hacer clic en un enlace
    $('.nav-menu a').click(function() {
        $('.nav-menu').removeClass('active');
        $('.menu-toggle').removeClass('active');
        $('body').removeClass('menu-open');
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
        $('.animate-on-scroll').each(function() {
            var element = $(this);
            var elementTop = element.offset().top;
            var windowBottom = $(window).scrollTop() + $(window).height();
            
            if (elementTop < windowBottom - 100) {
                element.addClass('animated');
            }
        });
    }

    $(window).scroll(animateOnScroll);
    animateOnScroll(); // Ejecutar al cargar

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
