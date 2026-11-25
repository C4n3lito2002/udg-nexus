-- ============================================================
-- UDG NEXUS - Avisos de demostración (FICTICIOS)
-- 7 avisos publicados el día 1 del evento (11 mar 2027), antes de las 11:40:
--   todos (2) · estudiante · inversionista · corporativo · expositor · staff
-- Marcas de lectura de ejemplo: el aviso 1 lo leyeron estudiante, inversionista y admin;
-- el aviso 2 lo leyó estudiante. Lo demás queda sin leer para ver la insignia.
-- Es seguro repetirlo: solo reemplaza los avisos con id 1 a 7 (y sus marcas de lectura);
-- los avisos que publique el administrador después (id 8 en adelante) no se tocan.
-- Requiere la migración 007 y 002_seed_usuarios.sql.
-- ============================================================

USE udg_nexus;

SET @admin = (SELECT id FROM usuarios WHERE correo = 'admin@udgnexus.test');

DELETE FROM notificaciones WHERE id BETWEEN 1 AND 7;

INSERT INTO notificaciones (id, titulo, cuerpo, audiencia, nivel, creada_por, publicada_en) VALUES
(1, 'Bienvenida a UDG NEXUS',
    'Ya está abierto el acceso por Plaza Bicentenario. Ten tu gafete QR a la mano: cada boleto entra una sola vez.',
    'todos', 'importante', @admin, '2027-03-11 08:30:00'),
(2, 'Bebidas y pagos sin efectivo',
    'Hay bebidas de cortesía y terminales cashless en los stands de alimentos. Aprovecha antes de las demostraciones del mediodía.',
    'todos', 'info', @admin, '2027-03-11 10:15:00'),
(3, 'Taller de propiedad intelectual',
    'El taller «Cómo proteger tu prototipo» tiene lugares limitados. Agrégalo a tu agenda para recibir su alerta.',
    'estudiante', 'info', @admin, '2027-03-11 10:45:00'),
(4, 'Due Diligence Desk con cupo',
    'Reserva tu revisión de patentes con los asesores del Due Diligence Desk antes de la rueda de negocios de salud.',
    'inversionista', 'importante', @admin, '2027-03-11 11:05:00'),
(5, 'Reverse Pitch: retos de manufactura',
    'Pasa 10 minutos antes por el mostrador de la zona Reverse Pitch Area para confirmar el turno de tu reto.',
    'corporativo', 'info', @admin, '2027-03-11 11:10:00'),
(6, 'Demostración de salud a las 12:00',
    'Mantén tu prototipo encendido y ten a la mano tu resumen para compradores durante la demostración en vivo.',
    'expositor', 'info', @admin, '2027-03-11 11:20:00'),
(7, 'Cambio de turno en acceso',
    'Briefing de staff a las 12:30 en Conjunto Santander. Confirma tu zona asignada con el comité.',
    'staff', 'importante', @admin, '2027-03-11 11:30:00');

INSERT INTO notificaciones_leidas (usuario_id, notificacion_id)
SELECT u.id, n.id
FROM usuarios u JOIN notificaciones n
WHERE (n.id = 1 AND u.correo IN ('estudiante@udgnexus.test', 'inversionista@udgnexus.test', 'admin@udgnexus.test'))
   OR (n.id = 2 AND u.correo = 'estudiante@udgnexus.test');
