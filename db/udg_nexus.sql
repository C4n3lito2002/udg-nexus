-- ============================================================
-- UDG NEXUS - Esquema de base de datos
-- Importar este archivo desde phpMyAdmin (MAMP) para crear
-- la base de datos y sus tablas.
-- ============================================================

CREATE DATABASE IF NOT EXISTS udg_nexus
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE udg_nexus;

-- ------------------------------------------------------------
-- Tabla: acreditaciones
-- Registro de asistentes por tipo de gafete
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS acreditaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    telefono VARCHAR(30),
    institucion_empresa VARCHAR(150),
    tipo_gafete ENUM(
        'Inversionista/Comprador',
        'Expositor/Investigador',
        'Prensa',
        'VIP',
        'Staff',
        'Estudiante/Público general'
    ) NOT NULL,
    eje_tematico VARCHAR(100),
    comentarios TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: citas_b2b
-- Solicitudes de citas 1:1 / Patent Room / Due Diligence Desk
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS citas_b2b (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    institucion_empresa VARCHAR(150) NOT NULL,
    tipo_asistente ENUM('Comprador/Inversionista', 'Expositor/Prototipador') NOT NULL,
    tema_interes VARCHAR(150),
    horario_preferido VARCHAR(100),
    requiere_nda ENUM('Sí', 'No') DEFAULT 'No',
    mensaje TEXT,
    estatus ENUM('Solicitada', 'Confirmada', 'Cancelada') DEFAULT 'Solicitada',
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: contactos_patrocinio
-- Formulario de contacto general / interés en patrocinio
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contactos_patrocinio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    empresa VARCHAR(150),
    asunto ENUM(
        'Interés en patrocinio',
        'Solicitud de Dossier Comercial',
        'Prensa y medios',
        'Otro'
    ) DEFAULT 'Otro',
    mensaje TEXT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
