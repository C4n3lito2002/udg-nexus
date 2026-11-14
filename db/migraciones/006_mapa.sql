-- ============================================================
-- UDG NEXUS - Migración 006: coordenadas del mapa (Fase 4)
-- Requiere la migración 001 (zonas y proyectos).
-- Es seguro volver a importarla: cada columna se agrega solo si no existe
-- (MySQL 8 no tiene ADD COLUMN IF NOT EXISTS, por eso se consulta information_schema).
--
-- El plano es un SVG de 720 x 980 unidades (esquemático, sin escala).
-- zonas.mapa_x, mapa_y, mapa_ancho, mapa_alto   rectángulo de la zona (esquina superior izquierda).
-- proyectos.mapa_x, mapa_y                      CENTRO del stand del proyecto (el tamaño del stand es fijo).
-- Las coordenadas las carga db/seed/005_seed_mapa.sql.
-- ============================================================

USE udg_nexus;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE zonas ADD COLUMN mapa_x SMALLINT UNSIGNED NULL, ADD COLUMN mapa_y SMALLINT UNSIGNED NULL, ADD COLUMN mapa_ancho SMALLINT UNSIGNED NULL, ADD COLUMN mapa_alto SMALLINT UNSIGNED NULL',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'zonas' AND COLUMN_NAME = 'mapa_x');
PREPARE paso FROM @sql;
EXECUTE paso;
DEALLOCATE PREPARE paso;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE proyectos ADD COLUMN mapa_x SMALLINT UNSIGNED NULL, ADD COLUMN mapa_y SMALLINT UNSIGNED NULL',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proyectos' AND COLUMN_NAME = 'mapa_x');
PREPARE paso FROM @sql;
EXECUTE paso;
DEALLOCATE PREPARE paso;
