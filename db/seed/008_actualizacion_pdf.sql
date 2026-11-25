-- ============================================================
-- UDG NEXUS - Seed 008: actualización con el PDF del proyecto (Fase 5C)
-- Evento de 2 días: jueves 11 y viernes 12 de marzo de 2027 (fecha ficticia).
-- Requiere: migraciones 001 a 008 y los seeds 001 y 005 (zonas con coordenadas) ya importados.
-- Es repetible: solo hace UPDATE por id e INSERT ... ON DUPLICATE KEY UPDATE; no vacía ninguna tabla.
-- Los ids de actividades, proyectos y empresas no cambian, así que agenda, boletos, avisos y mapa siguen igual.
-- Si algún día se reimporta 001_seed_catalogo.sql, este archivo se importa al final (ver docs/FASE5C.md).
--
-- Qué hace:
--   1. Reloj y fechas del evento: 11 y 12 de marzo, 2 días, reloj fijo en 11 mar 11:40.
--   2. Reacomoda las 16 actividades en 2 días (las 3, 4, 7 y 10 de la agenda del estudiante conservan hora y día).
--   3. Topa el TRL de los proyectos a 4-7, como pide el filtro del PDF.
--   4. Actualiza empresas 1 a 5 y agrega 5 aliados que ya salían en patrocinadores.php (ids 18 a 22).
--   5. Agrega 3 zonas pequeñas al mapa (ids 7 a 9) en la franja libre bajo la plaza.
--   6. Mueve la fecha de los avisos 1 a 7 al 11 de marzo.
-- ============================================================

USE udg_nexus;
SET NAMES utf8mb4;

-- ---------- 1. Fechas y reloj ----------
UPDATE config_evento SET valor = '2027-03-11'          WHERE clave = 'evento_inicio';
UPDATE config_evento SET valor = '2'                   WHERE clave = 'evento_dias';
UPDATE config_evento SET valor = '2027-03-11 11:40:00' WHERE clave = 'reloj_simulado';
UPDATE config_evento SET valor = '2027-03-11 11:40:00' WHERE clave = 'reloj_base';

-- ---------- 2. Actividades en 2 días ----------
SET @d0 = DATE('2027-03-11');   -- Día 1 (jueves)
SET @d1 = DATE('2027-03-12');   -- Día 2 (viernes)

-- Día 1
UPDATE actividades SET zona_id = 1, inicio = TIMESTAMP(@d0,'10:00:00'), fin = TIMESTAMP(@d0,'11:30:00'),
  ponente = 'Rectoría UdeG, Gobierno de Jalisco, cámaras industriales e IMPI',
  descripcion = 'Acto inaugural en la Demo Arena con el presidium institucional y el comité organizador.' WHERE id = 1;
UPDATE actividades SET zona_id = 3, inicio = TIMESTAMP(@d0,'11:30:00'), fin = TIMESTAMP(@d0,'13:00:00') WHERE id = 3;
UPDATE actividades SET zona_id = 1, inicio = TIMESTAMP(@d0,'12:00:00'), fin = TIMESTAMP(@d0,'12:45:00'),
  descripcion = 'Dos prototipos de salud, ambos con TRL 7, funcionando frente al público.' WHERE id = 4;
UPDATE actividades SET zona_id = 2, inicio = TIMESTAMP(@d0,'13:00:00'), fin = TIMESTAMP(@d0,'15:00:00'),
  descripcion = 'Citas 1:1 de 30 minutos entre expositores del eje de salud, compradores e inversionistas.' WHERE id = 5;
UPDATE actividades SET zona_id = 1, inicio = TIMESTAMP(@d0,'13:30:00'), fin = TIMESTAMP(@d0,'15:00:00') WHERE id = 13;
UPDATE actividades SET zona_id = 6, inicio = TIMESTAMP(@d0,'15:00:00'), fin = TIMESTAMP(@d0,'16:00:00') WHERE id = 2;
UPDATE actividades SET zona_id = 5, inicio = TIMESTAMP(@d0,'16:00:00'), fin = TIMESTAMP(@d0,'17:00:00') WHERE id = 6;

-- Día 2
UPDATE actividades SET zona_id = 2, inicio = TIMESTAMP(@d1,'09:00:00'), fin = TIMESTAMP(@d1,'11:00:00'),
  descripcion = 'Citas 1:1 de 30 minutos entre equipos de energía y manufactura, compradores e inversionistas.' WHERE id = 14;
UPDATE actividades SET zona_id = 6, inicio = TIMESTAMP(@d1,'10:00:00'), fin = TIMESTAMP(@d1,'11:00:00') WHERE id = 7;
UPDATE actividades SET zona_id = 3, inicio = TIMESTAMP(@d1,'11:30:00'), fin = TIMESTAMP(@d1,'13:00:00') WHERE id = 8;
UPDATE actividades SET zona_id = 1, inicio = TIMESTAMP(@d1,'12:00:00'), fin = TIMESTAMP(@d1,'13:30:00'),
  titulo = 'Presentaciones TRL 7: energía y manufactura',
  descripcion = 'Prototipos de alta madurez tecnológica (TRL 7) presentan sus resultados.' WHERE id = 9;
UPDATE actividades SET zona_id = 2, inicio = TIMESTAMP(@d1,'14:00:00'), fin = TIMESTAMP(@d1,'16:00:00'),
  descripcion = 'Citas 1:1 de 30 minutos entre equipos de software, fondos y compradores.' WHERE id = 10;
UPDATE actividades SET zona_id = 3, inicio = TIMESTAMP(@d1,'14:00:00'), fin = TIMESTAMP(@d1,'15:30:00') WHERE id = 12;
UPDATE actividades SET zona_id = 5, inicio = TIMESTAMP(@d1,'15:00:00'), fin = TIMESTAMP(@d1,'16:30:00') WHERE id = 11;
UPDATE actividades SET zona_id = 1, inicio = TIMESTAMP(@d1,'16:30:00'), fin = TIMESTAMP(@d1,'18:00:00'),
  descripcion = 'Cierre institucional, reconocimiento a los prototipos destacados y anuncio del Fondo Semilla UDG NEXUS.' WHERE id = 15;
UPDATE actividades SET zona_id = 1, inicio = TIMESTAMP(@d1,'18:00:00'), fin = TIMESTAMP(@d1,'21:00:00'),
  descripcion = 'Cóctel de clausura para inversionistas, speakers y directores de investigación y desarrollo. Áreas VIP delimitadas, consumo responsable y verificación de mayoría de edad.' WHERE id = 16;

-- ---------- 3. TRL de 4 a 7 ----------
UPDATE proyectos SET trl = 7 WHERE id IN (3, 9, 10, 18, 21);   -- antes 8 o 9
UPDATE proyectos SET trl = 4 WHERE id IN (8, 13);              -- antes 3

-- ---------- 4. Empresas ----------
UPDATE empresas SET descripcion = 'Patrocinio en especie: 15,000 latas de refresco variado (1.5 bebidas gratuitas por asistente), Zona Lounge & Chill Out con 8 hieleras y reciclaje de las latas junto con la UdeG.' WHERE id = 1;
UPDATE empresas SET descripcion = 'Patrocinador oficial de hidratación clínica: pabellón temático HealthTech by PiSA, 5,000 sueros Electrolit donados y módulo de salud preventiva (presión y glucosa). Esquema de corporate venturing: busca absorber prototipos o talento.' WHERE id = 2;
UPDATE empresas SET descripcion = 'Patrocinador fintech: 30 terminales de pago para stands de alimentos y merchandising (entorno cashless).' WHERE id = 3;
UPDATE empresas SET descripcion = 'Aliado tecnológico: equipo de 15 personas para cobertura audiovisual y de comunicación (2 cámaras, sonido y 2 videos verticales), 15 radios de frecuencia cerrada para la logística y 2,000 libretas corporativas para los kits VIP. Esquema de corporate venturing.' WHERE id = 4;
UPDATE empresas SET descripcion = 'Patrocinio de networking: 500 botellas, barra móvil y 3 bartenders para el cóctel de clausura, en áreas VIP y con consumo responsable.' WHERE id = 5;

INSERT INTO empresas (id, nombre, tipo, nivel_patrocinio, sector, descripcion, busca, ticket, es_ficticia) VALUES
(18, 'Bigol Bagel', 'aliado', NULL, 'Alimentos',
     'Oferta principal de alimento sólido: captura proyectada del 40% del aforo (4,000 bagels Choriqueso, Napoli, Odi y Laica), con venta a precio de lista.', NULL, NULL, 0),
(19, 'Eloise Baked Goods', 'aliado', NULL, 'Repostería',
     'Desayunos, postres y coffee breaks: captura proyectada del 35% del aforo (3,500 piezas de repostería), con venta a precio de lista.', NULL, NULL, 0),
(20, 'Botanas Lily', 'patrocinador', 'aliado', 'Alimentos',
     'Patrocinio en especie de 2,000 kits VIP de botana premium para inversionistas y speakers, más venta minorista de 3,000 unidades en la Isla Comercial.', NULL, NULL, 0),
(21, 'La Flor de Córdoba', 'aliado', NULL, 'Café',
     'Barras de café con insumos para 5,000 bebidas durante las jornadas (calientes, frías y a las rocas), con venta a precio de lista.', NULL, NULL, 0),
(22, 'Cruz Verde Zapopan', 'aliado', NULL, 'Servicios médicos municipales',
     'Soporte vital avanzado: ambulancia y paramédicos fijos para la Protección Civil del aforo masivo.', NULL, NULL, 0)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo = VALUES(tipo), nivel_patrocinio = VALUES(nivel_patrocinio),
  sector = VALUES(sector), descripcion = VALUES(descripcion), es_ficticia = VALUES(es_ficticia);

-- ---------- 5. Zonas pequeñas del mapa (franja libre entre la plaza y el Conjunto Santander) ----------
INSERT INTO zonas (id, nombre, tipo, descripcion, mapa_x, mapa_y, mapa_ancho, mapa_alto) VALUES
(7, 'Acreditación', 'soporte',
    'Acceso principal: registro, verificación de identidad y entrega de gafetes inteligentes con código QR.', 250, 732, 220, 52),
(8, 'Lounge Arca', 'exhibicion',
    'Zona Lounge & Chill Out de Arca Continental: área techada de descanso y networking informal, con bebidas de cortesía y recarga de celulares.', 24, 732, 210, 52),
(9, 'Sala de prensa', 'restringida',
    'Área de medios para la rueda de prensa, la cobertura de actos protocolares y las entrevistas con voceros. Solo prensa acreditada.', 486, 732, 210, 52)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo = VALUES(tipo), descripcion = VALUES(descripcion),
  mapa_x = VALUES(mapa_x), mapa_y = VALUES(mapa_y), mapa_ancho = VALUES(mapa_ancho), mapa_alto = VALUES(mapa_alto);

-- ---------- 6. Avisos 1 a 7 al día 1 (11 mar) conservando la hora ----------
UPDATE notificaciones SET publicada_en = CONCAT('2027-03-11 ', TIME(publicada_en)) WHERE id BETWEEN 1 AND 7;
