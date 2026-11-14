-- ============================================================
-- UDG NEXUS - Agenda de demostración (FICTICIA)
-- Deja lista la cuenta estudiante@udgnexus.test con 4 actividades:
--   * actividades 3 y 4 del día 1 se EMPALMAN (11:30-13:00 y 12:00-12:45)
--   * con el reloj en 10 mar 2027 11:40, la 4 tiene alerta de 30 min
--     (empieza 12:00, la alerta se abre a las 11:30): salta al abrir la app.
-- Es seguro repetirlo: reemplaza la agenda de esa cuenta.
-- Requiere 002_seed_usuarios.sql, 001_seed_catalogo.sql y la migración 004.
-- ============================================================

USE udg_nexus;

SET @u = (SELECT id FROM usuarios WHERE correo = 'estudiante@udgnexus.test');

DELETE FROM agenda_usuario WHERE usuario_id = @u;

INSERT INTO agenda_usuario (usuario_id, actividad_id, alerta_minutos, alerta_vista)
SELECT @u, a.id,
       CASE a.id WHEN 3 THEN NULL WHEN 4 THEN 30 WHEN 7 THEN 60 WHEN 10 THEN 15 END,
       0
FROM actividades a
WHERE a.id IN (3, 4, 7, 10) AND @u IS NOT NULL;
