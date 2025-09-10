<?php
// Consulta a la base de datos
$host = 'localhost';
$dbname = 'jaguar_expeditions';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, nombre, descripcion_corta, duracion, precio_descuento, precio_original, max_personas, categoria, imagen FROM tours ORDER BY id");
    $stmt->execute();
    $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $tours = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tours - Jaguar Expeditions</title>
    <meta name="description" content="Descubre todos nuestros tours y aventuras amazónicas en Iquitos. Expediciones personalizadas con guías expertos locales.">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="responsive.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <img src="img/logo.png" alt="Jaguar Expeditions">
                    <span>Jaguar Expeditions</span>
                </div>
                
                <nav class="nav">
                    <ul>
                        <li><a href="index.html">Inicio</a></li>
                        <li><a href="tours.html" class="active">Tours</a></li>
                        <li><a href="galeria.html">Galería</a></li>
                        <li><a href="nosotros.html">Nosotros</a></li>
                        <li><a href="preguntas.html">Preguntas</a></li>
                        <li><a href="contacto.html">Contacto</a></li>
                    </ul>
                </nav>
                
                <div class="nav-actions">
                    <button class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="menu-toggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-tours">
        <!-- Hero Section -->
        <section class="tours-hero">
            <div class="hero-background">
                <video autoplay muted loop>
                    <source src="img/tours-video.mp4" type="video/mp4">
                </video>
                <div class="hero-overlay"></div>
            </div>
            <div class="container">
                <div class="hero-content">
                    <h1>Nuestros Tours Amazónicos</h1>
                    <p>Descubre la magia de la selva amazónica con nuestras expediciones únicas y personalizadas</p>
                    <div class="hero-stats">
                        <div class="stat">
                            <span class="number">25+</span>
                            <span class="label">Tours Únicos</span>
                        </div>
                        <div class="stat">
                            <span class="number">500+</span>
                            <span class="label">Aventureros</span>
                        </div>
                        <div class="stat">
                            <span class="number">5</span>
                            <span class="label">Años de Experiencia</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search and Filter Section -->
        <section class="tours-search">
            <div class="container">
                <div class="search-wrapper">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar tours..." id="tourSearch">
                        <button class="search-submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="filters">
                        <div class="filter-group">
                            <select id="categoryFilter">
                                <option value="">Todas las categorías</option>
                                <option value="adventure">Aventura</option>
                                <option value="wildlife">Vida Silvestre</option>
                                <option value="cultural">Cultural</option>
                                <option value="gastronomic">Gastronómico</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select id="durationFilter">
                                <option value="">Todas las duraciones</option>
                                <option value="1">Medio día</option>
                                <option value="2">1 día</option>
                                <option value="3">2-3 días</option>
                                <option value="4">4+ días</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select id="priceFilter">
                                <option value="">Todos los precios</option>
                                <option value="1">$0 - $100</option>
                                <option value="2">$100 - $300</option>
                                <option value="3">$300 - $500</option>
                                <option value="4">$500+</option>
                            </select>
                        </div>
                        
                        <button class="filter-clear">
                            <i class="fas fa-times"></i>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tours Grid Section -->
        <section class="tours-grid-section">
            <div class="container">
                <div class="section-header">
                    <h2>Todos Nuestros Tours</h2>
                    <p>Explora cada rincón de la Amazonía con nuestras aventuras cuidadosamente diseñadas</p>
                </div>
                
                <!-- Grid de Tours Dinámico -->
                <div class="all-tours-grid">
                    <?php foreach ($tours as $tour): ?>
                    <div class="tour-item" data-category="<?php echo strtolower($tour['categoria']); ?>">
                        <div class="tour-image">
                            <img src="<?php echo $tour['imagen'] ? 'img/' . $tour['imagen'] : 'img/default-tour.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($tour['nombre']); ?>" loading="lazy">
                            <div class="tour-overlay">
                                <div class="tour-actions">
                                    <button class="btn-quick-view" data-tour="<?php echo $tour['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-add-cart" data-tour="<?php echo $tour['id']; ?>">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                    <button class="btn-share" data-tour="<?php echo $tour['id']; ?>">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="tour-content">
                            <div class="tour-meta">
                                <span class="tour-duration">
                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($tour['duracion']); ?>
                                </span>
                                <div class="tour-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <span>4.8</span>
                                </div>
                            </div>
                            <h3><?php echo htmlspecialchars($tour['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($tour['descripcion_corta']); ?></p>
                            <div class="tour-features">
                                <span><i class="fas fa-users"></i> Máx <?php echo $tour['max_personas']; ?> personas</span>
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($tour['categoria']); ?></span>
                            </div>
                            <div class="tour-pricing">
                                <?php if ($tour['precio_original'] && $tour['precio_original'] > $tour['precio_descuento']): ?>
                                    <span class="price-original">$<?php echo number_format($tour['precio_original'], 0); ?></span>
                                    <span class="price-current">$<?php echo number_format($tour['precio_descuento'], 0); ?></span>
                                    <span class="price-savings">¡Ahorra $<?php echo number_format($tour['precio_original'] - $tour['precio_descuento'], 0); ?>!</span>
                                <?php else: ?>
                                    <span class="price-current">$<?php echo number_format($tour['precio_descuento'], 0); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="tour-buttons">
                                <button class="btn-details" data-tour="<?php echo $tour['id']; ?>">Ver Detalles</button>
                                <button class="btn-book-tour">Reservar Ahora</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Botón Ver Más -->
                <div class="load-more-section">
                    <button class="btn-load-more">
                        <i class="fas fa-plus"></i>
                        Ver Más Tours
                    </button>
                    <p class="tours-info">Mostrando <?php echo count($tours); ?> tours disponibles</p>
                </div>
            </div>
        </section>

        <!-- Sección de Información Adicional -->
        <section class="tours-info-section">
            <div class="container">
                <div class="info-cards-grid">
                    <div class="info-card-tour">
                        <div class="info-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Seguridad Garantizada</h3>
                        <p>Todos nuestros tours cuentan con guías certificados y equipos de seguridad de primera calidad</p>
                    </div>
                    
                    <div class="info-card-tour">
                        <div class="info-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3>Reserva Flexible</h3>
                        <p>Cancela o reprograma tu tour hasta 24 horas antes sin costo adicional</p>
                    </div>
                    
                    <div class="info-card-tour">
                        <div class="info-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Soporte 24/7</h3>
                        <p>Nuestro equipo está disponible las 24 horas para ayudarte en cualquier momento</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Jaguar Expeditions</h3>
                    <p>Tu puerta de entrada a la aventura amazónica más auténtica</p>
                </div>
                
                <div class="footer-section">
                    <h4>Contacto</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Iquitos, Perú</p>
                    <p><i class="fas fa-phone"></i> +51 999 123 456</p>
                    <p><i class="fas fa-envelope"></i> info@jaguarexpeditions.com</p>
                </div>
                
                <div class="footer-section">
                    <h4>Síguenos</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Jaguar Expeditions. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
