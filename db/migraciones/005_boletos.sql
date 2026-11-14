-- ============================================================
-- UDG NEXUS - Migración 005: boletos, gafete QR y registro de accesos
-- Requiere las migraciones 001 y 002 (usuarios).
-- Es seguro volver a importarla (usa IF NOT EXISTS).
--
-- tipos_boleto   catálogo de boletos (precios ficticios, en pesos).
-- boletos        un boleto por persona; token_qr es lo ÚNICO que lleva el QR
--                (16 bytes aleatorios en hexadecimal = 32 caracteres, sin datos personales).
--                estatus: vigente -> usado (al entrar, una sola vez) | cancelado.
-- accesos        bitácora del escáner del staff: cada lectura queda registrada.
--                resultado: entrada, repetido (ya usado), cancelado o invalido
--                (código que no existe; boleto_id queda vacío y se guarda lo leído).
-- El pago es SIMULADO: pago_simulado_ref es una referencia ficticia, no hay cobro real.
-- ============================================================

USE udg_nexus;

CREATE TABLE IF NOT EXISTS tipos_boleto (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(30) NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(255) NOT NULL DEFAULT '',
    incluye TEXT NOT NULL,                              -- un beneficio por línea
    precio DECIMAL(8,2) NOT NULL DEFAULT 0.00,          -- pesos, ficticio
    cupo INT UNSIGNED NOT NULL DEFAULT 0,
    rol_sugerido VARCHAR(100) NOT NULL DEFAULT '',      -- roles (separados por coma) a los que se les recomienda
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_tipos_boleto_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boletos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo_boleto_id INT UNSIGNED NOT NULL,
    token_qr CHAR(32) NOT NULL,
    estatus ENUM('vigente','usado','cancelado') NOT NULL DEFAULT 'vigente',
    precio_pagado DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    pago_simulado_ref VARCHAR(20) NULL,
    comprado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usado_en TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_boletos_token (token_qr),
    INDEX idx_boletos_usuario (usuario_id, estatus),
    INDEX idx_boletos_tipo (tipo_boleto_id, estatus),
    CONSTRAINT fk_boletos_usuario FOREIGN KEY (usuario_id)     REFERENCES usuarios(id)      ON DELETE CASCADE,
    CONSTRAINT fk_boletos_tipo    FOREIGN KEY (tipo_boleto_id) REFERENCES tipos_boleto(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accesos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    boleto_id INT UNSIGNED NULL,
    staff_id INT UNSIGNED NULL,
    resultado ENUM('entrada','repetido','cancelado','invalido') NOT NULL,
    token_leido VARCHAR(64) NULL,                       -- solo para lecturas inválidas
    escaneado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_accesos_boleto (boleto_id),
    INDEX idx_accesos_fecha (escaneado_en),
    CONSTRAINT fk_accesos_boleto FOREIGN KEY (boleto_id) REFERENCES boletos(id)   ON DELETE CASCADE,
    CONSTRAINT fk_accesos_staff  FOREIGN KEY (staff_id)  REFERENCES usuarios(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
