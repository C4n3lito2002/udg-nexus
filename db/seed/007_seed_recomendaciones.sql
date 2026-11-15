-- ============================================================
-- UDG NEXUS - Seed 007: ejes de las actividades y proyecto del expositor (FICTICIO)
-- Requiere la migración 008, el seed 001 (actividades 1 a 16, ejes y proyectos) y el seed 002 (usuarios).
-- Se puede repetir sin duplicar nada (reemplaza todas las filas de actividad_area).
-- Aviso: reimportar 001_seed_catalogo.sql deja actividad_area con ids viejos; vuelve a importar este archivo.
--
-- Ejes: 1 Salud, 2 Agroindustria, 3 Manufactura, 4 Energía, 5 Ciudades, 6 Industrias creativas, 7 Software.
-- Sin fila = actividad general: 1 Inauguración, 2 Panorama de la transferencia, 6 Taller de propiedad
-- intelectual, 7 Financiamiento para spin-offs, 11 Due diligence, 15 Clausura, 16 Cóctel de networking.
-- ============================================================

USE udg_nexus;

DELETE FROM actividad_area;

INSERT INTO actividad_area (actividad_id, area_id) VALUES
(3, 3),            -- Reverse Pitch: retos de manufactura
(4, 1),            -- Demostración RehabGlove y NeuroBand
(5, 1),            -- Rueda de negocios: salud y biotecnología
(8, 2), (8, 5),    -- Reverse Pitch: agroindustria y agua
(9, 3), (9, 4),    -- Presentaciones TRL 7+: energía y manufactura
(10, 7),           -- Rueda de negocios: software y ciberseguridad
(12, 5),           -- Reverse Pitch: ciudades y movilidad
(13, 6), (13, 7),  -- Presentaciones: industrias creativas y software
(14, 3), (14, 4);  -- Rueda de negocios: energía y manufactura

-- El Dr. Mauricio Lara (expositor de demo) presenta SolarTejado (proyecto 12, eje Energía, Demo Arena).
UPDATE usuarios SET proyecto_id = 12 WHERE correo = 'expositor@udgnexus.test';
