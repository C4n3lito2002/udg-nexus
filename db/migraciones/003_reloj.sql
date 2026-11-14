-- ============================================================
-- UDG NEXUS - Migración 003: reloj del evento con avance automático
-- Agrega las claves nuevas a config_evento. Es seguro repetirla
-- (INSERT IGNORE no pisa valores que ya existan).
--
-- reloj_modo       real | fijo | avance
--   real   -> la app usa la hora real.
--   fijo   -> la app se queda en reloj_base.
--   avance -> arranca en reloj_base y corre solo (x reloj_velocidad).
-- reloj_base       hora simulada de arranque (AAAA-MM-DD HH:MM:SS)
-- reloj_ancla      hora real en que se fijó reloj_base (la escribe el panel admin)
-- reloj_velocidad  1 = tiempo real, 60 = un minuto simulado por segundo real
-- ============================================================

USE udg_nexus;

INSERT IGNORE INTO config_evento (clave, valor, descripcion) VALUES
('reloj_modo',      'fijo',                'Modo del reloj: real, fijo o avance'),
('reloj_base',      '2027-03-10 11:40:00', 'Hora simulada de arranque'),
('reloj_ancla',     '',                    'Hora real en que se fijó reloj_base (modo avance)'),
('reloj_velocidad', '1',                   'Velocidad del avance (1 = tiempo real)');
