-- ============================================================
-- UDG NEXUS - Datos de demostración de boletos (Fase 3)
-- Requiere la migración 005 y el seed de usuarios (002).
-- Es seguro repetirlo: actualiza los tipos y deja los boletos de demo
-- como "vigente" y sin accesos (útil para repetir la demo del escáner).
--
-- Boletos de demo (los tokens son fijos SOLO para la demostración):
--   estudiante@udgnexus.test     vigente   a4bf9a423c9a101a18adef73e11d85de
--   inversionista@udgnexus.test  vigente   6afceec67a2b45010483bdc6dd085d2d
--   corporativo@udgnexus.test    cancelado 7a3549c1ccb305fdad89282898fb2263
--   expositor@udgnexus.test      sin boleto (para probar el registro/compra)
-- Los boletos que genera la app usan random_bytes() y cambian cada vez.
-- ============================================================

USE udg_nexus;

INSERT INTO tipos_boleto (clave, nombre, descripcion, incluye, precio, cupo, rol_sugerido, orden, activo) VALUES
('estudiante',  'Estudiante',
 'Con credencial vigente de una institución educativa.',
 'Exposición de proyectos y Maker Gallery\nDemostraciones en la Demo Arena\nConferencias y retos de innovación', 0.00, 400, 'estudiante', 1, 1),
('publico',     'Público general',
 'Para quien quiera conocer los prototipos de la Red Universitaria.',
 'Exposición de proyectos y Maker Gallery\nDemostraciones en la Demo Arena\nConferencias y retos de innovación', 120.00, 300, '', 2, 1),
('profesional', 'Profesional (inversión y compras)',
 'Para fondos, corporativos y compradores que vienen a negociar.',
 'Todo lo del boleto general\nReverse Pitch y Patent Room\nDue Diligence Desk y rondas de negocios\nSala de networking con comida', 850.00, 150, 'inversionista,corporativo', 3, 1),
('expositor',   'Expositor',
 'Para equipos de investigación con stand en la exposición.',
 'Acceso a montaje y stand\nTodo lo del boleto general\nParticipación en el Demo Arena', 0.00, 100, 'expositor', 4, 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), descripcion = VALUES(descripcion), incluye = VALUES(incluye),
    precio = VALUES(precio), cupo = VALUES(cupo), rol_sugerido = VALUES(rol_sugerido),
    orden = VALUES(orden), activo = VALUES(activo);

-- Reinicia las lecturas de los boletos de demo.
DELETE FROM accesos WHERE boleto_id IN (SELECT id FROM boletos WHERE token_qr IN (
    'a4bf9a423c9a101a18adef73e11d85de', '6afceec67a2b45010483bdc6dd085d2d', '7a3549c1ccb305fdad89282898fb2263'));

INSERT INTO boletos (usuario_id, tipo_boleto_id, token_qr, estatus, precio_pagado, pago_simulado_ref, usado_en)
SELECT u.id, t.id, 'a4bf9a423c9a101a18adef73e11d85de', 'vigente', t.precio, NULL, NULL
FROM usuarios u JOIN tipos_boleto t ON t.clave = 'estudiante' WHERE u.correo = 'estudiante@udgnexus.test'
ON DUPLICATE KEY UPDATE estatus = 'vigente', usado_en = NULL, usuario_id = VALUES(usuario_id), tipo_boleto_id = VALUES(tipo_boleto_id);

INSERT INTO boletos (usuario_id, tipo_boleto_id, token_qr, estatus, precio_pagado, pago_simulado_ref, usado_en)
SELECT u.id, t.id, '6afceec67a2b45010483bdc6dd085d2d', 'vigente', t.precio, 'SIM-DEMO0001', NULL
FROM usuarios u JOIN tipos_boleto t ON t.clave = 'profesional' WHERE u.correo = 'inversionista@udgnexus.test'
ON DUPLICATE KEY UPDATE estatus = 'vigente', usado_en = NULL, usuario_id = VALUES(usuario_id), tipo_boleto_id = VALUES(tipo_boleto_id);

INSERT INTO boletos (usuario_id, tipo_boleto_id, token_qr, estatus, precio_pagado, pago_simulado_ref, usado_en)
SELECT u.id, t.id, '7a3549c1ccb305fdad89282898fb2263', 'cancelado', t.precio, 'SIM-DEMO0002', NULL
FROM usuarios u JOIN tipos_boleto t ON t.clave = 'profesional' WHERE u.correo = 'corporativo@udgnexus.test'
ON DUPLICATE KEY UPDATE estatus = 'cancelado', usado_en = NULL, usuario_id = VALUES(usuario_id), tipo_boleto_id = VALUES(tipo_boleto_id);
