-- ============================================================
-- UDG NEXUS - Migración 004: agenda personal con alertas
-- Requiere las migraciones 001 (actividades) y 002 (usuarios).
-- Es seguro volver a importarla (usa IF NOT EXISTS).
--
-- alerta_minutos  NULL = sin alerta; 15, 30 o 60 = avisar esos minutos
--                 antes del inicio, según el RELOJ DEL EVENTO.
-- alerta_vista    1 = la persona ya vio/descartó la alerta. Se vuelve a 0
--                 al cambiar la alerta o cuando el admin mueve el reloj.
-- ============================================================

USE udg_nexus;

CREATE TABLE IF NOT EXISTS agenda_usuario (
    usuario_id INT UNSIGNED NOT NULL,
    actividad_id INT UNSIGNED NOT NULL,
    alerta_minutos SMALLINT UNSIGNED NULL,
    alerta_vista TINYINT(1) NOT NULL DEFAULT 0,
    agregado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, actividad_id),
    CONSTRAINT fk_agenda_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)    ON DELETE CASCADE,
    CONSTRAINT fk_agenda_actividad FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE,
    INDEX idx_agenda_actividad (actividad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
