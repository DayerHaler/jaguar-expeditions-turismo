-- ========================================
-- INSERTAR TODOS LOS TOURS DE TOURS.HTML
-- ========================================

-- Primero eliminamos los tours existentes para evitar duplicados
DELETE FROM tours WHERE id IN (1,2,3,4,5,6);

-- Ahora insertamos todos los 15 tours con información completa
INSERT INTO tours (
    id, nombre, descripcion, descripcion_corta, duracion, precio, precio_descuento, 
    imagen_principal, categoria, dificultad, max_personas, min_personas, destacado, estado,
    incluye, no_incluye, itinerario
) VALUES 

-- Tour 1: Expedición Río Amazonas
(1, 'Expedición Río Amazonas', 
'Navega por el río más largo del mundo y descubre la magia de la selva tropical. Una aventura de 3 días que te llevará por los rincones más remotos del Amazonas, donde podrás observar fauna única, conocer comunidades locales y vivir una experiencia inolvidable en el corazón de la selva.',
'Navega por el río más largo del mundo y descubre la magia de la selva tropical',
'3 días', 299.00, 399.00, 'tour1.jpg', 'Aventura', 'Moderado', 8, 2, TRUE, 'Activo',
'["Transporte en bote", "Guía bilingüe especializado", "Comidas completas", "Alojamiento en lodge", "Equipo de seguridad", "Binoculares profesionales"]',
'["Vuelos a Iquitos", "Bebidas alcohólicas", "Propinas", "Seguro de viaje", "Gastos personales"]',
'[{"dia": 1, "actividades": ["Recepción en puerto", "Navegación río arriba", "Almuerzo a bordo", "Instalación en lodge", "Caminata nocturna"]}, {"dia": 2, "actividades": ["Avistamiento al amanecer", "Visita comunidad nativa", "Pesca de pirañas", "Observación de delfines", "Cena tradicional"]}, {"dia": 3, "actividades": ["Trekking en selva", "Canopy tour", "Almuerzo de despedida", "Retorno a Iquitos"]}]'),

-- Tour 2: Safari Nocturno Amazónico  
(2, 'Safari Nocturno Amazónico',
'Descubre la vida nocturna de la selva y observa caimanes bajo las estrellas. Una experiencia única donde la naturaleza cobra vida de manera completamente diferente cuando cae la noche en el Amazonas.',
'Descubre la vida nocturna de la selva y observa caimanes bajo las estrellas',
'1 día', 89.00, NULL, 'tour2.jpg', 'Naturaleza', 'Fácil', 6, 2, TRUE, 'Activo',
'["Transporte nocturno", "Guía especializado", "Linternas profesionales", "Snack ligero", "Equipo de observación"]',
'["Cena completa", "Bebidas", "Repelente de insectos", "Ropa impermeable"]',
'[{"horario": "18:00", "actividades": ["Salida desde puerto", "Navegación nocturna", "Búsqueda de caimanes", "Observación de aves nocturnas", "Retorno 22:00"]}]'),

-- Tour 3: Comunidades Nativas
(3, 'Comunidades Nativas',
'Convive con las comunidades indígenas y aprende sobre la cultura ancestral amazónica. Una inmersión cultural que te permitirá conocer tradiciones milenarias, artesanías típicas y la sabiduría ancestral de los pueblos originarios.',
'Convive con las comunidades indígenas y aprende sobre la cultura ancestral',
'2 días', 199.00, NULL, 'tour3.jpeg', 'Cultural', 'Fácil', 10, 2, TRUE, 'Activo',
'["Transporte completo", "Guía cultural", "Alojamiento en comunidad", "Comidas tradicionales", "Actividades culturales", "Traducción"]',
'["Artesanías (compras)", "Bebidas comerciales", "Donaciones voluntarias", "Medicamentos personales"]',
'[{"dia": 1, "actividades": ["Llegada a comunidad", "Bienvenida tradicional", "Almuerzo comunitario", "Taller de artesanías", "Danzas típicas", "Pernocte"]}, {"dia": 2, "actividades": ["Actividades agrícolas", "Preparación alimentos", "Intercambio cultural", "Ceremonia despedida", "Retorno"]}]'),

-- Tour 4: Aventura Extrema
(4, 'Aventura Extrema',
'Canopy, trekking, rappel y deportes extremos en plena selva amazónica. Para los más aventureros que buscan emociones fuertes y desafíos únicos en el corazón de la selva.',
'Canopy, trekking, rappel y deportes extremos en plena selva amazónica',
'4 días', 449.00, 599.00, 'tour4.jpeg', 'Aventura', 'Difícil', 6, 2, TRUE, 'Activo',
'["Equipo deportivo completo", "Instructor certificado", "Seguro de actividades", "Alojamiento", "Comidas energéticas", "Primeros auxilios"]',
'["Ropa deportiva especializada", "Medicamentos personales", "Seguro médico adicional", "Propinas"]',
'[{"dia": 1, "actividades": ["Briefing seguridad", "Canopy básico", "Trekking inicial", "Campamento base"]}, {"dia": 2, "actividades": ["Rappel cascadas", "Escalada en roca", "Navegación rapids", "Camping selvático"]}, {"dia": 3, "actividades": ["Canopy extremo", "Puenting", "Supervivencia", "Fogata nocturna"]}, {"dia": 4, "actividades": ["Actividad libre", "Evaluación", "Certificación", "Retorno"]}]'),

-- Tour 5: Observación de Fauna
(5, 'Observación de Fauna',
'Especializado en avistamiento de animales únicos de la Amazonía. Perfecto para amantes de la naturaleza y fotógrafos que buscan capturar la increíble biodiversidad amazónica.',
'Especializado en avistamiento de animales únicos de la Amazonía',
'2 días', 179.00, NULL, 'tour5.jpg', 'Naturaleza', 'Fácil', 8, 2, FALSE, 'Activo',
'["Binoculares profesionales", "Guía biólogo", "Transporte especializado", "Comidas", "Alojamiento", "Lista de especies"]',
'["Cámara fotográfica", "Teleobjetivos", "Repelente", "Botas de jungla", "Ropa camuflaje"]',
'[{"dia": 1, "actividades": ["Torre observación", "Sendero fauna", "Almuerzo campo", "Avistamiento vespertino", "Lodge nocturno"]}, {"dia": 2, "actividades": ["Madrugada aves", "Búsqueda primates", "Observación reptiles", "Registro fotográfico", "Retorno"]}]'),

-- Tour 6: Tour Gastronómico
(6, 'Tour Gastronómico',
'Descubre los sabores únicos de la Amazonía y aprende a cocinar platos típicos. Una experiencia culinaria que despertará todos tus sentidos con los ingredientes más exóticos de la selva.',
'Descubre los sabores únicos de la Amazonía y aprende a cocinar platos típicos',
'1 día', 129.00, NULL, 'tour6.jpg', 'Gastronomía', 'Fácil', 12, 2, TRUE, 'Activo',
'["Chef especializado", "Ingredientes exóticos", "Taller de cocina", "Degustación completa", "Recetario", "Certificado"]',
'["Bebidas alcohólicas", "Ingredientes para llevar", "Utensilios de cocina", "Delantal personalizado"]',
'[{"horario": "9:00-17:00", "actividades": ["Mercado local", "Selección ingredientes", "Taller cocina", "Almuerzo preparado", "Degustación postres", "Recetas para casa"]}]'),

-- Tour 7: Mercado de Belén
(7, 'Mercado de Belén',
'Explora el mercado flotante más famoso de la Amazonía y su cultura local. Sumérgete en la vida cotidiana de Iquitos y descubre productos únicos de la región.',
'Explora el mercado flotante más famoso de la Amazonía y su cultura local',
'Medio día', 45.00, NULL, 'tour7.jpeg', 'Cultural', 'Fácil', 15, 2, FALSE, 'Activo',
'["Guía local", "Transporte", "Degustación frutas", "Tour explicativo", "Mapa del mercado"]',
'["Compras personales", "Almuerzo", "Bebidas", "Artesanías", "Souvenirs"]',
'[{"horario": "8:00-12:00", "actividades": ["Historia del mercado", "Recorrido secciones", "Productos amazónicos", "Interacción vendedores", "Compras opcionales"]}]'),

-- Tour 8: Nado con Delfines Rosados
(8, 'Nado con Delfines Rosados',
'Una experiencia única nadando con los delfines rosados del Amazonas. Interactúa con estos magníficos mamíferos acuáticos en su hábitat natural.',
'Una experiencia única nadando con los delfines rosados del Amazonas',
'1 día', 149.00, 189.00, 'tour8.jpeg', 'Naturaleza', 'Fácil', 8, 2, TRUE, 'Activo',
'["Transporte acuático", "Guía biólogo marino", "Equipo de nado", "Almuerzo", "Fotografías", "Chaleco salvavidas"]',
'["Traje de neopreno", "Cámara acuática", "Toallas", "Cambio de ropa", "Protector solar"]',
'[{"horario": "7:00-16:00", "actividades": ["Navegación zona delfines", "Observación comportamiento", "Nado supervisado", "Almuerzo ribereño", "Sesión fotos", "Retorno"]}]'),

-- Tour 9: Trekking Selvático
(9, 'Trekking Selvático',
'Caminata profunda por senderos vírgenes de la selva amazónica. Para aventureros que buscan conectar directamente con la naturaleza más pura y salvaje.',
'Caminata profunda por senderos vírgenes de la selva amazónica',
'3 días', 259.00, NULL, 'tour9.jpeg', 'Aventura', 'Moderado', 6, 2, FALSE, 'Activo',
'["Guía especialista", "Equipo camping", "Comidas energéticas", "Kit supervivencia", "Botiquín", "Comunicación emergencia"]',
'["Botas trekking", "Mochila personal", "Ropa técnica", "Sleeping bag", "Medicamentos", "Linterna"]',
'[{"dia": 1, "actividades": ["Inicio sendero", "Técnicas orientación", "Campamento 1", "Fogata nocturna"]}, {"dia": 2, "actividades": ["Trekking profundo", "Cruce ríos", "Observación fauna", "Campamento 2"]}, {"dia": 3, "actividades": ["Sendero retorno", "Supervivencia", "Emergencia", "Llegada base"]}]'),

-- Tour 10: Casa de Hierro Histórica
(10, 'Casa de Hierro Histórica',
'Descubre la historia y arquitectura colonial de Iquitos en este icónico edificio. Un viaje en el tiempo por la rica historia de la ciudad durante el boom del caucho.',
'Descubre la historia y arquitectura colonial de Iquitos en este icónico edificio',
'Medio día', 35.00, NULL, 'tour10.jpeg', 'Cultural', 'Fácil', 20, 2, FALSE, 'Activo',
'["Guía historiador", "Entrada al edificio", "Material educativo", "Mapa histórico", "Folletos informativos"]',
'["Cámara fotográfica", "Libreta de notas", "Agua", "Propinas", "Transporte personal"]',
'[{"horario": "14:00-17:00", "actividades": ["Historia del caucho", "Arquitectura detallada", "Exposición fotográfica", "Anécdotas locales", "Tour azotea"]}]'),

-- Tour 11: Navegación Nocturna
(11, 'Navegación Nocturna',
'Navega por el Amazonas bajo las estrellas y vive la magia nocturna del río más grande del mundo. Una experiencia romántica y mística.',
'Navega por el Amazonas bajo las estrellas y vive la magia nocturna',
'2 días', 199.00, NULL, 'tour11.jpeg', 'Aventura', 'Fácil', 10, 2, FALSE, 'Activo',
'["Bote nocturno", "Capitán experimentado", "Cena a bordo", "Hamacas", "Observación estrellas", "Música ambiente"]',
'["Repelente", "Ropa abrigada", "Cámara nocturna", "Bebidas personales", "Medicamentos"]',
'[{"noche": 1, "actividades": ["Partida nocturna", "Cena río", "Observación estrellas", "Hamacas cubierta", "Amanecer río"]}, {"dia": 2, "actividades": ["Desayuno abordo", "Pesca matutina", "Exploración orillas", "Almuerzo", "Retorno"]}]'),

-- Tour 12: Observación de Aves
(12, 'Observación de Aves',
'Especializado en avistamiento de las 500+ especies de aves amazónicas. Para ornitólogos aficionados y amantes del birdwatching.',
'Especializado en avistamiento de las 500+ especies de aves amazónicas',
'1 día', 99.00, NULL, 'tour12.jpg', 'Naturaleza', 'Fácil', 8, 2, FALSE, 'Activo',
'["Binoculares HD", "Guía ornitólogo", "Lista especies", "Transporte", "Almuerzo campo", "Guía identificación"]',
'["Cámara teleobjetivo", "Libreta campo", "Ropa camuflaje", "Repelente", "Gorra", "Protector solar"]',
'[{"horario": "5:00-15:00", "actividades": ["Madrugada avistamiento", "Torre observación", "Senderos especializados", "Registro especies", "Almuerzo", "Tarde aves"]}]'),

-- Tour 13: Pesca Deportiva Amazónica
(13, 'Pesca Deportiva Amazónica',
'Pesca el famoso paiche y otras especies únicas del río Amazonas. Una experiencia deportiva en las aguas más ricas en biodiversidad del planeta.',
'Pesca el famoso paiche y otras especies únicas del río Amazonas',
'1 día', 139.00, NULL, 'tour13.jpeg', 'Gastronomía', 'Fácil', 6, 2, FALSE, 'Activo',
'["Equipo pesca completo", "Capitán pescador", "Cebos especializados", "Almuerzo pescado", "Licencia pesca", "Cooler"]',
'["Sombrero", "Protector solar", "Ropa cómoda", "Bebidas", "Cámara", "Propinas"]',
'[{"horario": "6:00-16:00", "actividades": ["Salida madrugada", "Zonas pesca", "Técnicas locales", "Almuerzo pescado fresco", "Competencia amigable", "Retorno"]}]'),

-- Tour 14: Medicina Ancestral
(14, 'Medicina Ancestral',
'Aprende sobre plantas medicinales amazónicas con chamanes locales. Una experiencia espiritual y educativa sobre la sabiduría ancestral de la selva.',
'Aprende sobre plantas medicinales amazónicas con chamanes locales',
'2 días', 229.00, NULL, 'tour14.jpg', 'Cultural', 'Fácil', 8, 2, FALSE, 'Activo',
'["Chamán certificado", "Traductor", "Material educativo", "Alojamiento", "Comidas", "Plantas medicinales"]',
'["Libreta personal", "Recipientes", "Medicamentos personales", "Ropa cómoda", "Mente abierta"]',
'[{"dia": 1, "actividades": ["Ceremonia bienvenida", "Jardín medicinal", "Preparaciones básicas", "Ritual nocturno"]}, {"dia": 2, "actividades": ["Recolección plantas", "Taller preparación", "Ceremonia cierre", "Bendición final"]}]'),

-- Tour 15: Expedición Completa VIP
(15, 'Expedición Completa VIP',
'La experiencia definitiva: 7 días de aventura completa en la Amazonía con servicio premium y atención personalizada.',
'La experiencia definitiva: 7 días de aventura completa en la Amazonía',
'7 días', 999.00, 1299.00, 'tour15.jpeg', 'Aventura', 'Moderado', 4, 2, TRUE, 'Activo',
'["Guía privado", "Lodge premium", "Todas las comidas", "Actividades múltiples", "Transporte privado", "Servicio mayordomía", "Bar incluido", "Spa selvático"]',
'["Vuelos internacionales", "Compras personales", "Excursiones extra", "Tratamientos spa adicionales"]',
'[{"dia": 1, "actividades": ["Recepción VIP", "Lodge premium", "Cena gourmet", "Briefing personalizado"]}, {"dia": 2, "actividades": ["Expedición río", "Comunidad exclusiva", "Almuerzo gourmet", "Spa natural"]}, {"dia": 3, "actividades": ["Canopy privado", "Observación fauna", "Cena especial", "Música en vivo"]}, {"dia": 4, "actividades": ["Pesca deportiva", "Cocina gourmet", "Navegación privada", "Cóctel sunset"]}, {"dia": 5, "actividades": ["Trekking guiado", "Medicina ancestral", "Masaje spa", "Cena romántica"]}, {"dia": 6, "actividades": ["Actividad elegida", "Compras exclusivas", "Preparación equipaje", "Cena despedida"]}, {"dia": 7, "actividades": ["Desayuno gourmet", "Traslado VIP", "Despedida personal", "Transfer aeropuerto"]}]');

-- Insertar algunas fechas de disponibilidad para los próximos 30 días
INSERT INTO disponibilidad_tours (tour_id, fecha, cupos_disponibles, estado)
SELECT 
    t.id,
    DATE_ADD(CURDATE(), INTERVAL d.day_offset DAY) as fecha,
    CASE 
        WHEN t.max_personas <= 6 THEN t.max_personas
        WHEN t.max_personas <= 10 THEN t.max_personas - 2
        ELSE t.max_personas - 4
    END as cupos_disponibles,
    'Disponible' as estado
FROM tours t
CROSS JOIN (
    SELECT 1 as day_offset UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION
    SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION
    SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
    SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
    SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION
    SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
) d
WHERE t.estado = 'Activo';

-- Mensaje de confirmación
SELECT 'Todos los tours han sido insertados correctamente' as mensaje,
       COUNT(*) as total_tours_insertados
FROM tours;
