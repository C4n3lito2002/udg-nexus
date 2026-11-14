-- ============================================================
-- UDG NEXUS - Migración 007: avisos y notificaciones (Fase 5)
-- Requiere la migración 002 (usuarios).
-- Es seguro volver a importarla (usa IF NOT EXISTS).
--
-- notificaciones          un aviso publicado por el administrador.
--   audiencia             'todos' o la clave de un rol (estudiante, inversionista,
--                         corporativo, expositor, staff, admin).
--   nivel                 info | importante (solo cambia cómo se ve).
--   publicada_en          hora del EVENTO (ahora_evento()) en que se publicó; es la que
--                         se muestra en la app, para que tenga sentido con el reloj de la demo.
--   creada_en             hora real de la base (auditoría).
--   retirada_en           NULL = visible. Retirar un aviso NO lo borra: solo deja de
--                         mostrarse y de contar como no leído; así se conserva cuántos lo leyeron.
-- notificaciones_leidas   quién ya marcó como leído cada aviso (clave compuesta).
-- Borrado en cascada: si se borra una cuenta o un aviso, sus marcas de lectura se van con él.
-- ============================================================

USE udg_nexus;

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(80) NOT NULL,
    cuerpo VARCHAR(500) NOT NULL,
    audiencia VARCHAR(20) NOT NULL DEFAULT 'todos',
    nivel ENUM('info','importante') NOT NULL DEFAULT 'info',
    creada_por INT UNSIGNED NULL,
    creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    publicada_en DATETIME NOT NULL,
    retirada_en TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_notif_visibles (retirada_en, audiencia, id),
    CONSTRAINT fk_notif_autor FOREIGN KEY (creada_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificaciones_leidas (
    usuario_id INT UNSIGNED NOT NULL,
    notificacion_id INT UNSIGNED NOT NULL,
    leida_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, notificacion_id),
    INDEX idx_leidas_aviso (notificacion_id),
    CONSTRAINT fk_leidas_usuario FOREIGN KEY (usuario_id)      REFERENCES usuarios(id)       ON DELETE CASCADE,
    CONSTRAINT fk_leidas_aviso   FOREIGN KEY (notificacion_id) REFERENCES notificaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
