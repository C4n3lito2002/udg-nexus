-- ============================================================
-- UDG NEXUS - Seed 005: coordenadas del mapa (Fase 4)
-- Requiere la migración 006 y el seed 001 (zonas y proyectos).
-- Se puede repetir sin duplicar nada (solo hace UPDATE).
-- Aviso: reimportar 001_seed_catalogo.sql vuelve a crear zonas y proyectos SIN coordenadas;
--        después hay que volver a importar este archivo.
--
-- Plano de 720 x 980 unidades. Stand: 88 x 48 centrado en (mapa_x, mapa_y).
-- Filas de 6 stands con centros en x = 110, 210, 310, 410, 510, 610.
-- ============================================================

USE udg_nexus;

-- Zonas: x, y, ancho, alto
UPDATE zonas SET mapa_x = 24,  mapa_y = 96,  mapa_ancho = 672, mapa_alto = 262 WHERE id = 1; -- Demo Arena
UPDATE zonas SET mapa_x = 340, mapa_y = 606, mapa_ancho = 170, mapa_alto = 100 WHERE id = 2; -- Patent Room
UPDATE zonas SET mapa_x = 24,  mapa_y = 606, mapa_ancho = 300, mapa_alto = 100 WHERE id = 3; -- Reverse Pitch Area
UPDATE zonas SET mapa_x = 24,  mapa_y = 382, mapa_ancho = 672, mapa_alto = 200 WHERE id = 4; -- Maker Gallery
UPDATE zonas SET mapa_x = 526, mapa_y = 606, mapa_ancho = 170, mapa_alto = 100 WHERE id = 5; -- Due Diligence Desk
UPDATE zonas SET mapa_x = 24,  mapa_y = 790, mapa_ancho = 672, mapa_alto = 160 WHERE id = 6; -- Conjunto Santander

-- Demo Arena (zona 1): stands A-G
UPDATE proyectos SET mapa_x = 110, mapa_y = 172 WHERE zona_id = 1 AND stand = 'A-01';
UPDATE proyectos SET mapa_x = 210, mapa_y = 172 WHERE zona_id = 1 AND stand = 'A-02';
UPDATE proyectos SET mapa_x = 310, mapa_y = 172 WHERE zona_id = 1 AND stand = 'A-03';
UPDATE proyectos SET mapa_x = 410, mapa_y = 172 WHERE zona_id = 1 AND stand = 'B-01';
UPDATE proyectos SET mapa_x = 510, mapa_y = 172 WHERE zona_id = 1 AND stand = 'B-02';
UPDATE proyectos SET mapa_x = 610, mapa_y = 172 WHERE zona_id = 1 AND stand = 'C-01';
UPDATE proyectos SET mapa_x = 110, mapa_y = 236 WHERE zona_id = 1 AND stand = 'C-02';
UPDATE proyectos SET mapa_x = 210, mapa_y = 236 WHERE zona_id = 1 AND stand = 'D-01';
UPDATE proyectos SET mapa_x = 310, mapa_y = 236 WHERE zona_id = 1 AND stand = 'E-01';
UPDATE proyectos SET mapa_x = 410, mapa_y = 236 WHERE zona_id = 1 AND stand = 'F-01';
UPDATE proyectos SET mapa_x = 510, mapa_y = 236 WHERE zona_id = 1 AND stand = 'G-01';
UPDATE proyectos SET mapa_x = 610, mapa_y = 236 WHERE zona_id = 1 AND stand = 'G-02';

-- Maker Gallery (zona 4): stands M-01 a M-12
UPDATE proyectos SET mapa_x = 110, mapa_y = 458 WHERE zona_id = 4 AND stand = 'M-01';
UPDATE proyectos SET mapa_x = 210, mapa_y = 458 WHERE zona_id = 4 AND stand = 'M-02';
UPDATE proyectos SET mapa_x = 310, mapa_y = 458 WHERE zona_id = 4 AND stand = 'M-03';
UPDATE proyectos SET mapa_x = 410, mapa_y = 458 WHERE zona_id = 4 AND stand = 'M-04';
UPDATE proyectos SET mapa_x = 510, mapa_y = 458 WHERE zona_id = 4 AND stand = 'M-05';
UPDATE proyectos SET mapa_x = 610, mapa_y = 458 WHERE zona_id = 4 AND stand = 'M-06';
UPDATE proyectos SET mapa_x = 110, mapa_y = 522 WHERE zona_id = 4 AND stand = 'M-07';
UPDATE proyectos SET mapa_x = 210, mapa_y = 522 WHERE zona_id = 4 AND stand = 'M-08';
UPDATE proyectos SET mapa_x = 310, mapa_y = 522 WHERE zona_id = 4 AND stand = 'M-09';
UPDATE proyectos SET mapa_x = 410, mapa_y = 522 WHERE zona_id = 4 AND stand = 'M-10';
UPDATE proyectos SET mapa_x = 510, mapa_y = 522 WHERE zona_id = 4 AND stand = 'M-11';
UPDATE proyectos SET mapa_x = 610, mapa_y = 522 WHERE zona_id = 4 AND stand = 'M-12';
