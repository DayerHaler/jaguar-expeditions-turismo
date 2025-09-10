-- ========================================
-- SCRIPT PARA AGREGAR CAMPOS MULTIIDIOMA
-- ========================================
-- 
-- Este script agrega campos de traducción para inglés y alemán
-- a la tabla tours existente
--
-- Fecha: 2025-09-02
-- Propósito: Implementar sistema de traducciones para tours

USE jaguar_expeditions;

-- Agregar campos para inglés (EN)
ALTER TABLE tours 
ADD COLUMN nombre_en VARCHAR(255) NULL COMMENT 'Nombre del tour en inglés',
ADD COLUMN descripcion_en TEXT NULL COMMENT 'Descripción completa en inglés',
ADD COLUMN descripcion_corta_en VARCHAR(500) NULL COMMENT 'Descripción corta en inglés';

-- Agregar campos para alemán (DE)
ALTER TABLE tours 
ADD COLUMN nombre_de VARCHAR(255) NULL COMMENT 'Nombre del tour en alemán',
ADD COLUMN descripcion_de TEXT NULL COMMENT 'Descripción completa en alemán',
ADD COLUMN descripcion_corta_de VARCHAR(500) NULL COMMENT 'Descripción corta en alemán';

-- Agregar campos para traducciones de JSON (incluye, no_incluye, itinerario)
ALTER TABLE tours 
ADD COLUMN incluye_en JSON NULL COMMENT 'Qué incluye el tour en inglés',
ADD COLUMN incluye_de JSON NULL COMMENT 'Qué incluye el tour en alemán',
ADD COLUMN no_incluye_en JSON NULL COMMENT 'Qué NO incluye el tour en inglés',
ADD COLUMN no_incluye_de JSON NULL COMMENT 'Qué NO incluye el tour en alemán',
ADD COLUMN itinerario_en JSON NULL COMMENT 'Itinerario del tour en inglés',
ADD COLUMN itinerario_de JSON NULL COMMENT 'Itinerario del tour en alemán';

-- Verificar que los campos se agregaron correctamente
DESCRIBE tours;

-- Mostrar mensaje de confirmación
SELECT 'Campos de traducción agregados exitosamente a la tabla tours' AS mensaje;

-- ========================================
-- DATOS DE EJEMPLO PARA LOS TOURS EXISTENTES
-- ========================================

-- Actualizar tour "Aventura Extrema"
UPDATE tours SET 
    nombre_en = 'Extreme Adventure',
    nombre_de = 'Extremes Abenteuer',
    descripcion_corta_en = 'Canopy, trekking, rappelling and extreme sports in the heart of the Amazon rainforest',
    descripcion_corta_de = 'Canopy, Trekking, Abseilen und Extremsport im Herzen des Amazonas-Regenwaldes',
    descripcion_en = 'Experience the ultimate Amazon adventure with our extreme sports package. Includes canopy zip-lining through the forest canopy, challenging trekking routes, rappelling down waterfalls, and various extreme sports activities. Perfect for adrenaline seekers looking for an unforgettable experience in the Peruvian Amazon.',
    descripcion_de = 'Erleben Sie das ultimative Amazonas-Abenteuer mit unserem Extremsport-Paket. Beinhaltet Canopy-Seilrutschen durch das Waldkronendach, herausfordernde Trekking-Routen, Abseilen an Wasserfällen und verschiedene Extremsportaktivitäten. Perfekt für Adrenalinjunkies, die ein unvergessliches Erlebnis im peruanischen Amazonas suchen.',
    incluye_en = JSON_ARRAY(
        'Professional extreme sports guide',
        'All safety equipment',
        'Transportation from/to Iquitos',
        'Lunch and snacks',
        'Accident insurance',
        'Professional photography'
    ),
    incluye_de = JSON_ARRAY(
        'Professioneller Extremsport-Guide',
        'Komplette Sicherheitsausrüstung',
        'Transport von/nach Iquitos',
        'Mittagessen und Snacks',
        'Unfallversicherung',
        'Professionelle Fotografie'
    ),
    no_incluye_en = JSON_ARRAY(
        'Personal equipment',
        'Additional meals',
        'Accommodation',
        'Tips for guides'
    ),
    no_incluye_de = JSON_ARRAY(
        'Persönliche Ausrüstung',
        'Zusätzliche Mahlzeiten',
        'Unterkunft',
        'Trinkgelder für Guides'
    )
WHERE nombre = 'Aventura Extrema';

-- Actualizar tour "Comunidades Nativas"
UPDATE tours SET 
    nombre_en = 'Native Communities',
    nombre_de = 'Eingeborene Gemeinden',
    descripcion_corta_en = 'Live with indigenous communities and learn about ancestral culture',
    descripcion_corta_de = 'Leben Sie mit indigenen Gemeinden und lernen Sie die Ahnenkultur kennen',
    descripcion_en = 'Immerse yourself in the rich culture of Amazonian indigenous communities. Learn about their traditions, customs, and ancestral knowledge. Participate in daily activities, traditional ceremonies, and discover the deep connection between these communities and the rainforest.',
    descripcion_de = 'Tauchen Sie ein in die reiche Kultur der amazonischen indigenen Gemeinden. Lernen Sie ihre Traditionen, Bräuche und das Ahnenwissen kennen. Nehmen Sie an täglichen Aktivitäten und traditionellen Zeremonien teil und entdecken Sie die tiefe Verbindung zwischen diesen Gemeinden und dem Regenwald.',
    incluye_en = JSON_ARRAY(
        'Native community guide',
        'Cultural activities and ceremonies',
        'Traditional meals',
        'Community accommodation',
        'Handicraft workshop',
        'Medicinal plant tour'
    ),
    incluye_de = JSON_ARRAY(
        'Einheimischer Gemeinde-Guide',
        'Kulturelle Aktivitäten und Zeremonien',
        'Traditionelle Mahlzeiten',
        'Gemeinschaftsunterkunft',
        'Handwerks-Workshop',
        'Heilpflanzen-Tour'
    ),
    no_incluye_en = JSON_ARRAY(
        'Transportation to the community',
        'Personal expenses',
        'Souvenirs',
        'Tips'
    ),
    no_incluye_de = JSON_ARRAY(
        'Transport zur Gemeinde',
        'Persönliche Ausgaben',
        'Souvenirs',
        'Trinkgelder'
    )
WHERE nombre = 'Comunidades Nativas';

-- Actualizar tour "Expedición Completa VIP"
UPDATE tours SET 
    nombre_en = 'Complete VIP Expedition',
    nombre_de = 'Komplette VIP-Expedition',
    descripcion_corta_en = 'The definitive experience: 7 days of complete adventure in the Amazon',
    descripcion_corta_de = 'Das ultimative Erlebnis: 7 Tage komplettes Abenteuer im Amazonas',
    descripcion_en = 'Our most exclusive and complete Amazon experience. 7 days of luxury adventure including the best lodges, expert guides, gourmet cuisine, and access to the most remote and pristine areas of the Peruvian Amazon. Limited to small groups for a personalized experience.',
    descripcion_de = 'Unser exklusivstes und komplettestes Amazonas-Erlebnis. 7 Tage Luxus-Abenteuer inklusive der besten Lodges, Expertenführer, Gourmet-Küche und Zugang zu den entlegensten und unberührtesten Gebieten des peruanischen Amazonas. Begrenzt auf kleine Gruppen für ein personalisiertes Erlebnis.',
    incluye_en = JSON_ARRAY(
        'Luxury lodge accommodation',
        'Expert bilingual guide',
        'All gourmet meals',
        'Private transportation',
        'Boat excursions',
        'Night tours',
        'Fishing activities',
        'Wildlife observation',
        'Premium insurance',
        'Professional photography service'
    ),
    incluye_de = JSON_ARRAY(
        'Luxus-Lodge-Unterkunft',
        'Experten-Guide zweisprachig',
        'Alle Gourmet-Mahlzeiten',
        'Privater Transport',
        'Boot-Exkursionen',
        'Nacht-Touren',
        'Angel-Aktivitäten',
        'Wildtierbeobachtung',
        'Premium-Versicherung',
        'Professioneller Fotografie-Service'
    ),
    no_incluye_en = JSON_ARRAY(
        'International flights',
        'Alcoholic beverages',
        'Personal expenses',
        'Laundry service'
    ),
    no_incluye_de = JSON_ARRAY(
        'Internationale Flüge',
        'Alkoholische Getränke',
        'Persönliche Ausgaben',
        'Wäsche-Service'
    )
WHERE nombre = 'Expedición Completa VIP';

-- Mensaje final
SELECT 'Traducciones de ejemplo agregadas exitosamente' AS mensaje;
