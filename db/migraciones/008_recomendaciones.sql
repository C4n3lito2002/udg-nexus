-- ============================================================
-- UDG NEXUS - Migración 008: recomendaciones por perfil (Fase 5B)
-- Requiere las migraciones 001 (actividades, proyectos, ejes) y 002 (usuarios).
-- Es seguro volver a importarla: la tabla usa IF NOT EXISTS y la columna y su llave
-- foránea se agregan solo si no existen (MySQL 8 no tiene ADD COLUMN IF NOT EXISTS,
-- por eso se consulta information_schema, igual que la migración 006).
--
-- actividad_area       ejes tecnológicos de cada actividad (N:M). Una actividad sin filas es
--                      "general" (inauguración, clausura, propiedad intelectual...).
-- usuarios.proyecto_id proyecto que expone una cuenta de expositor (su zona sale de ese proyecto).
--                      NULL = la cuenta no está ligada a un proyecto.
-- Los datos los carga db/seed/007_seed_recomendaciones.sql.
-- ============================================================

USE udg_nexus;

CREATE TABLE IF NOT EXISTS actividad_area (
    actividad_id INT UNSIGNED NOT NULL,
    area_id TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (actividad_id, area_id),
    CONSTRAINT fk_actarea_actividad FOREIGN KEY (actividad_id) REFERENCES actividades(id)       ON DELETE CASCADE,
    CONSTRAINT fk_actarea_area      FOREIGN KEY (area_id)      REFERENCES areas_tecnologicas(id) ON DELETE CASCADE,
    INDEX idx_actarea_area (area_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE usuarios ADD COLUMN proyecto_id INT UNSIGNED NULL AFTER intereses',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'proyecto_id');
PREPARE paso FROM @sql;
EXECUTE paso;
DEALLOCATE PREPARE paso;

SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE SET NULL',
    'DO 0')
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
    AND CONSTRAINT_NAME = 'fk_usuarios_proyecto' AND CONSTRAINT_TYPE = 'FOREIGN KEY');
PREPARE paso FROM @sql;
EXECUTE paso;
DEALLOCATE PREPARE paso;
