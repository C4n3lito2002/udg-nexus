-- ============================================================
-- UDG NEXUS - Migración 001: catálogo, agenda base y avisos
-- Requiere haber importado antes db/udg_nexus.sql.
-- Es seguro volver a importarlo (usa IF NOT EXISTS).
-- ============================================================

CREATE DATABASE IF NOT EXISTS udg_nexus
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE udg_nexus;

-- Configuración del evento (incluye el reloj simulado para la demo)
CREATE TABLE IF NOT EXISTS config_evento (
    clave VARCHAR(60) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Los 7 ejes tecnológicos de la Red Universitaria
CREATE TABLE IF NOT EXISTS areas_tecnologicas (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    slug VARCHAR(40) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zonas del recinto (Plaza Bicentenario y Conjunto Santander)
CREATE TABLE IF NOT EXISTS zonas (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    tipo ENUM('publica','restringida','retos','exhibicion','soporte','escenario') NOT NULL,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Empresas, fondos, compradores y patrocinadores
CREATE TABLE IF NOT EXISTS empresas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    tipo ENUM('patrocinador','comprador','fondo','aliado') NOT NULL,
    nivel_patrocinio ENUM('principal','oficial','aliado') NULL,
    sector VARCHAR(100) NULL,
    descripcion TEXT NULL,
    busca VARCHAR(255) NULL,
    ticket VARCHAR(100) NULL,
    sitio_web VARCHAR(200) NULL,
    es_ficticia TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_empresas_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empresa_area (
    empresa_id INT UNSIGNED NOT NULL,
    area_id TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (empresa_id, area_id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES areas_tecnologicas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS investigadores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    centro VARCHAR(100) NOT NULL,
    especialidad VARCHAR(120) NULL,
    bio TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prototipos / proyectos expositores
CREATE TABLE IF NOT EXISTS proyectos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(140) NOT NULL,
    resumen VARCHAR(280) NOT NULL,
    descripcion TEXT NULL,
    area_id TINYINT UNSIGNED NOT NULL,
    trl TINYINT UNSIGNED NOT NULL,
    busca_inversion TINYINT(1) NOT NULL DEFAULT 0,
    destacado TINYINT(1) NOT NULL DEFAULT 0,
    estado_pi ENUM('Patente concedida','Patente en trámite','Secreto industrial','Sin protección aún') NOT NULL DEFAULT 'Sin protección aún',
    ruta_salida ENUM('Licenciamiento','Co-desarrollo','Piloto pagado','Inversión en spin-off') NOT NULL DEFAULT 'Licenciamiento',
    centro VARCHAR(100) NULL,
    zona_id TINYINT UNSIGNED NULL,
    stand VARCHAR(10) NULL,
    FOREIGN KEY (area_id) REFERENCES areas_tecnologicas(id),
    FOREIGN KEY (zona_id) REFERENCES zonas(id) ON DELETE SET NULL,
    INDEX idx_proyectos_trl (trl),
    INDEX idx_proyectos_area (area_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyecto_investigador (
    proyecto_id INT UNSIGNED NOT NULL,
    investigador_id INT UNSIGNED NOT NULL,
    rol ENUM('Líder','Investigador','Estudiante','Asesor') NOT NULL DEFAULT 'Investigador',
    PRIMARY KEY (proyecto_id, investigador_id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE,
    FOREIGN KEY (investigador_id) REFERENCES investigadores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actividades del programa (la agenda personal llega en la Fase 2)
CREATE TABLE IF NOT EXISTS actividades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(160) NOT NULL,
    tipo ENUM('Conferencia','Reto industrial','Rueda de negocios','Presentación','Reverse Pitch','Taller','Networking') NOT NULL,
    descripcion VARCHAR(400) NULL,
    ponente VARCHAR(160) NULL,
    zona_id TINYINT UNSIGNED NOT NULL,
    inicio DATETIME NOT NULL,
    fin DATETIME NOT NULL,
    cupo SMALLINT UNSIGNED NULL,
    FOREIGN KEY (zona_id) REFERENCES zonas(id),
    INDEX idx_actividades_inicio (inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Banners del Home: patrocinadores y avisos del comité
CREATE TABLE IF NOT EXISTS anuncios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('patrocinador','comite') NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    texto VARCHAR(300) NOT NULL,
    empresa_id INT UNSIGNED NULL,
    enlace VARCHAR(200) NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
