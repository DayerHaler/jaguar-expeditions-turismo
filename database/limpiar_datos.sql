-- Script de limpieza antes de la reestructuración
-- Eliminar datos con fechas inválidas

USE jaguar_expeditions;

-- Verificar datos problemáticos
SELECT 'Verificando datos problemáticos...' as status;

-- Mostrar registros con fechas inválidas
SELECT COUNT(*) as registros_con_fechas_invalidas
FROM clientes_reserva 
WHERE fecha_nacimiento = '0000-00-00' OR fecha_nacimiento IS NULL;

-- Limpiar o corregir datos problemáticos
-- Opción 1: Eliminar registros con datos inválidos (CUIDADO: esto elimina datos)
-- DELETE FROM clientes_reserva WHERE fecha_nacimiento = '0000-00-00';

-- Opción 2: Actualizar fechas inválidas a NULL (más seguro)
UPDATE clientes_reserva 
SET fecha_nacimiento = NULL 
WHERE fecha_nacimiento = '0000-00-00' OR fecha_nacimiento = '';

-- Verificar que no hay más datos problemáticos
SELECT 'Limpieza completada' as status;
SELECT COUNT(*) as registros_restantes FROM clientes_reserva;
