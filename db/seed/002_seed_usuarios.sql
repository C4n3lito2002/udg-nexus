-- ============================================================
-- UDG NEXUS - Usuarios de demostración (uno por rol)
-- Contraseña de todos: nexus2027
-- Es seguro repetirlo: actualiza las cuentas si ya existen.
-- ============================================================

USE udg_nexus;

INSERT INTO usuarios (nombre, correo, password_hash, rol, institucion, intereses) VALUES
('Administrador NEXUS',   'admin@udgnexus.test',         '$2y$10$2sFa4DDhRa0iwgDXdsaqb.LeBf32/qsZLP8oBGbj.EaBevo2.XoEK', 'admin',         'Comité organizador',          ''),
('Staff de acceso',       'staff@udgnexus.test',         '$2y$10$UXfkWHK8lATmLTzIHzj..OxaxotejLMyw6tfJ6sG8p0BRSRLawHKW', 'staff',         'Comité organizador',          ''),
('Valeria Montes',        'estudiante@udgnexus.test',    '$2y$10$tgHGPyREshAl1jbB9SrIQ..6NKY.qMAdYDWa3JTVlkObAEhi55/Sa', 'estudiante',    'CUCEA, Universidad de Guadalajara', 'salud,software'),
('Andrés Ibarra',         'inversionista@udgnexus.test', '$2y$10$JKjG3Od1WdssgJGdTe1/Bu1XrjY/fltMDW9hHdtjpAdPriU/w.v2C', 'inversionista', 'Fondo Occidente Capital',      'salud,manufactura,energia'),
('Camila Reyes',          'corporativo@udgnexus.test',   '$2y$10$O7oaHQ5L3qskN.VHmBy/U.GGfidqhTFWKiCG4VmqzBWS8mdPjzaoy', 'corporativo',   'Grupo Industrial del Bajío',  'manufactura,agro'),
('Dr. Mauricio Lara',     'expositor@udgnexus.test',     '$2y$10$v50BWt/p13y61Lvm3kvUpuGfjo5M5.1l7ODHWIPioE6j5PYqhLRj.', 'expositor',     'CUCEI, Universidad de Guadalajara', 'energia,ciudades')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), password_hash = VALUES(password_hash), rol = VALUES(rol),
    institucion = VALUES(institucion), intereses = VALUES(intereses);
