-- ============================================================
-- UDG NEXUS - Migración 002: cuentas de usuario y roles
-- Requiere haber importado antes db/migraciones/001_catalogo.sql.
-- Es seguro volver a importarlo (usa IF NOT EXISTS).
-- ============================================================

USE udg_nexus;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('estudiante','inversionista','corporativo','expositor','staff','admin') NOT NULL DEFAULT 'estudiante',
    institucion VARCHAR(150) NOT NULL DEFAULT '',
    intereses VARCHAR(255) NOT NULL DEFAULT '',   -- slugs de areas_tecnologicas separados por coma
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
