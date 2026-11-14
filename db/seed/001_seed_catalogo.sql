-- ============================================================
-- UDG NEXUS - Datos de demostración (FICTICIOS)
-- Proyectos, investigadores, fondos y compradores son inventados.
-- Los patrocinadores y aliados son los publicados en patrocinadores.php.
-- Se puede volver a importar: borra y recrea estos datos.
-- ============================================================
USE udg_nexus;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE anuncios;
TRUNCATE TABLE actividades;
TRUNCATE TABLE proyecto_investigador;
TRUNCATE TABLE proyectos;
TRUNCATE TABLE investigadores;
TRUNCATE TABLE empresa_area;
TRUNCATE TABLE empresas;
TRUNCATE TABLE zonas;
TRUNCATE TABLE areas_tecnologicas;
TRUNCATE TABLE config_evento;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Configuración ----------
-- evento_inicio: primer día del evento (las actividades se calculan a partir de él).
-- reloj_simulado: hora que usa la app. Déjalo vacío para usar la hora real.
INSERT INTO config_evento (clave, valor, descripcion) VALUES
('evento_inicio',  '2027-03-10',          'Primer día del evento (fecha ficticia de demostración)'),
('evento_dias',    '3',                   'Número de días del evento'),
('reloj_simulado', '2027-03-10 11:40:00', 'Hora simulada de la app (vacío = hora real)');

-- ---------- Ejes tecnológicos ----------
INSERT INTO areas_tecnologicas (id, nombre, slug, descripcion, orden) VALUES
(1, 'Salud',               'salud',      'Dispositivos médicos, diagnóstico, rehabilitación y biomateriales.', 1),
(2, 'Agroindustria',       'agro',       'Agrotecnología, sanidad vegetal, alimentos y empaques sustentables.', 2),
(3, 'Manufactura',         'manufactura','Automatización, calidad, materiales y manufactura avanzada.', 3),
(4, 'Energía',             'energia',    'Generación limpia, almacenamiento y eficiencia energética.', 4),
(5, 'Ciudades',            'ciudades',   'Movilidad, agua, espacio público e infraestructura urbana.', 5),
(6, 'Industrias creativas','creativas',  'Diseño, medios interactivos, experiencias y producción cultural.', 6),
(7, 'Software',            'software',   'Inteligencia artificial, ciberseguridad y plataformas digitales.', 7);

-- ---------- Zonas ----------
INSERT INTO zonas (id, nombre, tipo, descripcion) VALUES
(1, 'Demo Arena',          'publica',     'Presentación de prototipos funcionales y lanzamientos frente al público y compradores.'),
(2, 'Patent Room',         'restringida', 'Sala privada de negociación B2B bajo confidencialidad.'),
(3, 'Reverse Pitch Area',  'retos',       'Las empresas presentan sus retos y los equipos universitarios proponen soluciones.'),
(4, 'Maker Gallery',       'exhibicion',  'Exhibición de diseño industrial, prototipado y software de la Red Universitaria.'),
(5, 'Due Diligence Desk',  'soporte',     'Apoyo legal, revisión de patentes y firma de convenios de confidencialidad.'),
(6, 'Conjunto Santander',  'escenario',   'Salas para conferencias magistrales y firma de contratos.');

-- ---------- Empresas ----------
-- 1-8: patrocinadores y aliados publicados en el sitio. 9-17: ficticias.
INSERT INTO empresas (id, nombre, tipo, nivel_patrocinio, sector, descripcion, busca, ticket, es_ficticia) VALUES
(1, 'Arca Continental / Coca-Cola', 'patrocinador', 'principal', 'Bebidas y consumo',
    'Patrocinio en especie: 15,000 latas de refresco variado (1.5 bebidas gratuitas por asistente).', NULL, NULL, 0),
(2, 'PiSA Farmacéutica / Electrolit', 'patrocinador', 'oficial', 'Salud y farmacéutica',
    'Patrocinador oficial de hidratación clínica. Pabellón temático HealthTech y 5,000 sueros Electrolit donados. Esquema de corporate venturing: busca absorber prototipos o talento.', 'Prototipos y talento en salud', NULL, 0),
(3, 'Mercado Pago', 'patrocinador', 'aliado', 'Tecnología financiera',
    'Patrocinador fintech: 15 terminales de pago para stands de alimentos y merchandising (entorno cashless).', NULL, NULL, 0),
(4, 'Jabil Guadalajara', 'patrocinador', 'oficial', 'Manufactura electrónica',
    'Equipo de comunicación para el staff logístico y 2,000 souvenirs corporativos para kits VIP. Esquema de corporate venturing.', 'Prototipos y talento en manufactura y electrónica', NULL, 0),
(5, 'Casa Cuervo', 'patrocinador', 'aliado', 'Bebidas',
    'Patrocinio de networking: 500 botellas, barra y 3 bartenders para el cóctel de clausura VIP.', NULL, NULL, 0),
(6, 'CANACO Guadalajara', 'aliado', NULL, 'Cámara empresarial',
    'Imagen y difusión entre las empresas comerciales de la región.', NULL, NULL, 0),
(7, 'COPARMEX Jalisco', 'aliado', NULL, 'Cámara empresarial',
    'Imagen y difusión entre el empresariado empleador y fondos de inversión.', NULL, NULL, 0),
(8, 'SICyT Jalisco', 'aliado', NULL, 'Gobierno estatal',
    'Validación académica, imagen y difusión en el ecosistema estatal de ciencia y tecnología.', NULL, NULL, 0),
(9, 'Altiplano Ventures', 'fondo', NULL, 'Capital de riesgo',
    'Fondo de capital de riesgo enfocado en empresas de base tecnológica surgidas de universidades.', 'Spin-offs en salud y manufactura avanzada con TRL 6 o superior', 'MXN 2 a 10 millones', 1),
(10, 'Nodo Capital Ángel', 'fondo', NULL, 'Red de ángeles inversionistas',
    'Red de ángeles inversionistas que acompaña a equipos fundadores en etapas tempranas.', 'Software, ciberseguridad y plataformas digitales', 'MXN 500 mil a 3 millones', 1),
(11, 'Cuenca Impulso', 'fondo', NULL, 'Fondo de impacto',
    'Fondo de impacto que invierte en soluciones de energía limpia, agua y ciudades.', 'Energía limpia, agua y movilidad', 'MXN 1 a 5 millones', 1),
(12, 'Halcón Venture Studio', 'fondo', NULL, 'Venture studio',
    'Estudio de empresas que co-crea compañías con equipos universitarios.', 'Spin-offs de inteligencia artificial y software', 'MXN 1 a 4 millones', 1),
(13, 'Manufacturas Occidente', 'comprador', NULL, 'Manufactura metalmecánica',
    'Grupo manufacturero con plantas en el occidente del país, interesado en modernizar sus líneas.', 'Automatización, control de calidad y materiales ligeros', NULL, 1),
(14, 'AgroSierra Alimentos', 'comprador', NULL, 'Agroindustria',
    'Productor y procesador de alimentos con operación en Jalisco y Michoacán.', 'Sensores de riego, empaques sustentables y cultivos probióticos', NULL, 1),
(15, 'Hospital Metropolitano del Pacífico', 'comprador', NULL, 'Salud privada',
    'Red hospitalaria privada que evalúa tecnología médica para pilotos clínicos.', 'Dispositivos de rehabilitación y diagnóstico', NULL, 1),
(16, 'Logística Tapatía Express', 'comprador', NULL, 'Logística y retail',
    'Operador logístico y comercial con centros de distribución en la zona metropolitana.', 'Analítica de demanda y visión artificial', NULL, 1),
(17, 'Consorcio Urbano Metropolitano', 'comprador', NULL, 'Infraestructura urbana',
    'Consorcio de servicios urbanos que busca pilotos de movilidad y gestión de agua.', 'Movilidad inteligente y monitoreo de redes hídricas', NULL, 1);

INSERT INTO empresa_area (empresa_id, area_id) VALUES
(2,1),(4,3),(4,7),
(9,1),(9,3),(10,7),(11,4),(11,5),(12,7),
(13,3),(13,4),(14,2),(15,1),(16,7),(16,3),(17,5),(17,4);

-- ---------- Investigadores y estudiantes (ficticios) ----------
INSERT INTO investigadores (id, nombre, centro, especialidad, bio) VALUES
(1,  'Dra. Mariana Ortega Villaseñor', 'CUCS',  'Biomateriales',                'Investigadora en materiales para curación de heridas y ingeniería de tejidos.'),
(2,  'Dr. Hugo Beltrán Cisneros',      'CUCEI', 'Electrónica médica',           'Diseña instrumentación biomédica de bajo costo y sistemas de adquisición de señales.'),
(3,  'M.C. Daniela Rosales Aguirre',   'CUCEI', 'Aprendizaje automático',       'Desarrolla modelos de visión por computadora para industria y salud.'),
(4,  'Dr. Emilio Navarro Zepeda',      'CUCBA', 'Agrobiotecnología',            'Trabaja en tecnología de precisión para cultivos de agave y hortalizas.'),
(5,  'Dra. Lucía Preciado Montes',     'CUCBA', 'Fitopatología',                'Estudia el diagnóstico temprano de plagas y enfermedades en cultivos.'),
(6,  'Ing. Sebastián Camacho Rangel',  'CUCEI', 'Robótica',                     'Construye robots colaborativos y sistemas de control para manufactura.'),
(7,  'Dra. Paulina Esquivel Ruvalcaba','CUCEI', 'Materiales para energía',      'Investiga celdas de almacenamiento con materiales abundantes y de bajo costo.'),
(8,  'Dr. Rodrigo Mendoza Alatorre',   'CUCEI', 'Sistemas fotovoltaicos',       'Desarrolla electrónica de potencia para generación solar distribuida.'),
(9,  'Mtra. Ximena Gutiérrez Lomelí',  'CUAAD', 'Diseño industrial',            'Diseña productos y mobiliario con materiales reciclados y producción local.'),
(10, 'Dr. Alan Sandoval Pedroza',      'CUCEA', 'Analítica de datos',           'Modela demanda y optimización de procesos con técnicas de ciencia de datos.'),
(11, 'Ing. Karla Barragán Ibarra',     'CUCEI', 'Ciberseguridad',               'Especialista en seguridad administrada para pequeñas y medianas empresas.'),
(12, 'Dr. Iván Zúñiga Orozco',         'CUCS',  'Rehabilitación',               'Investiga dispositivos de rehabilitación neuromotora.'),
(13, 'Mtra. Renata Covarrubias Ochoa', 'CUAAD', 'Medios interactivos',          'Produce experiencias inmersivas y contenidos audiovisuales educativos.'),
(14, 'Dr. Gael Arellano Contreras',    'CUCiénega', 'Procesamiento de alimentos','Estudia cultivos microbianos para alimentos fermentados regionales.'),
(15, 'Andrea Villalobos Núñez',        'CUCEI', 'Estudiante de Ingeniería Biomédica',     NULL),
(16, 'Jesús Palomera Cuevas',          'CUCEI', 'Estudiante de Ingeniería en Computación', NULL),
(17, 'Fernanda Lozano Reyes',          'CUAAD', 'Estudiante de Diseño Industrial',        NULL),
(18, 'Ángel Ruelas Montoya',           'CUCEI', 'Estudiante de Ingeniería Electrónica',   NULL);

-- ---------- Proyectos (ficticios) ----------
INSERT INTO proyectos (id, nombre, resumen, descripcion, area_id, trl, busca_inversion, destacado, estado_pi, ruta_salida, centro, zona_id, stand) VALUES
(1, 'NeuroBand', 'Diadema ligera que detecta patrones previos a una crisis epiléptica y avisa al paciente.',
 'Registra actividad cerebral con electrodos secos y un algoritmo que reconoce señales previas a una crisis. Ya completó pruebas con voluntarios y busca un piloto clínico con un hospital.',
 1, 7, 1, 1, 'Patente en trámite', 'Piloto pagado', 'CUCS', 1, 'A-01'),
(2, 'BioVenda', 'Apósito de quitosano que acelera la cicatrización de heridas crónicas.',
 'Combina quitosano obtenido de residuos pesqueros con un agente antimicrobiano natural. Se validó en laboratorio y en un estudio piloto con pacientes con úlceras por presión.',
 1, 6, 0, 0, 'Patente concedida', 'Licenciamiento', 'CUCS', 1, 'A-02'),
(3, 'RehabGlove', 'Guante robótico que asiste la rehabilitación de la mano tras un derrame cerebral.',
 'Motores blandos y sensores de fuerza guían ejercicios personalizados y registran el progreso del paciente. Opera en clínicas piloto y se prepara su constitución como empresa.',
 1, 8, 1, 1, 'Patente en trámite', 'Inversión en spin-off', 'CUCS', 1, 'A-03'),
(4, 'DiagnoLab', 'Lector portátil de glucosa que no requiere punción.',
 'Mide glucosa mediante espectroscopía óptica de bajo costo. El prototipo de banco ya reproduce lecturas de referencia y se busca un socio para integrarlo a un dispositivo de uso doméstico.',
 1, 4, 1, 0, 'Secreto industrial', 'Co-desarrollo', 'CUCEI', 4, 'M-01'),
(5, 'AgroPulse', 'Sensores de humedad de bajo costo para optimizar el riego de agave.',
 'Red de nodos inalámbricos con energía solar que reporta humedad y temperatura del suelo. Instalado en parcelas reales, reduce el consumo de agua según las mediciones del equipo.',
 2, 7, 1, 1, 'Patente en trámite', 'Piloto pagado', 'CUCBA', 1, 'B-01'),
(6, 'FitoAlerta', 'Detección temprana de plagas con imágenes tomadas por dron.',
 'Un modelo de visión analiza fotografías aéreas y marca zonas con probable infestación. Se entrenó con imágenes de cultivos de la región y se prueba en dos huertas.',
 2, 6, 0, 0, 'Sin protección aún', 'Co-desarrollo', 'CUCBA', 1, 'B-02'),
(7, 'Bioempaque de bagazo', 'Empaque compostable elaborado con bagazo de agave.',
 'Aprovecha el residuo de la industria del agave para producir charolas rígidas compostables. Se estudia el escalamiento de la producción con un fabricante.',
 2, 5, 1, 0, 'Patente en trámite', 'Licenciamiento', 'CUCBA', 4, 'M-02'),
(8, 'Fermentos regionales', 'Cultivos probióticos para lácteos y fermentados tradicionales.',
 'Selecciona cepas autóctonas con efecto probiótico para estandarizar la calidad de productos artesanales. Se encuentra en fase de validación en laboratorio.',
 2, 3, 0, 0, 'Secreto industrial', 'Co-desarrollo', 'CUCiénega', 4, 'M-03'),
(9, 'CobotPyme', 'Brazo colaborativo accesible para pequeñas manufactureras.',
 'Brazo robótico de seis ejes que se programa mostrándole el movimiento, sin escribir código. Opera en dos talleres piloto y su costo es una fracción del de un robot industrial.',
 3, 8, 1, 1, 'Patente concedida', 'Licenciamiento', 'CUCEI', 1, 'C-01'),
(10, 'VisiónQC', 'Inspección de calidad por visión artificial en líneas de producción.',
 'Detecta defectos en piezas en tiempo real con cámaras industriales y aprendizaje automático. Funciona en una planta con resultados comprobados durante un año.',
 3, 9, 0, 1, 'Secreto industrial', 'Licenciamiento', 'CUCEI', 1, 'C-02'),
(11, 'ImpreMetal', 'Impresión 3D de aleaciones ligeras para piezas especializadas.',
 'Proceso de fabricación aditiva con polvos de aluminio reciclado. Se han impreso piezas de prueba y se busca un socio industrial para certificar sus propiedades.',
 3, 4, 1, 0, 'Patente en trámite', 'Co-desarrollo', 'CUCEI', 4, 'M-04'),
(12, 'SolarTejado', 'Microinversor fotovoltaico modular para techos residenciales.',
 'Convierte la energía de cada panel de forma independiente y aumenta la producción en techos con sombra. Se instaló en viviendas piloto y cumple con las pruebas de seguridad eléctrica del laboratorio.',
 4, 7, 1, 1, 'Patente en trámite', 'Piloto pagado', 'CUCEI', 1, 'D-01'),
(13, 'BateríaVerde', 'Celdas de sodio para almacenamiento de energía estacionario.',
 'Alternativa a las baterías de litio con materiales más abundantes. Existen celdas de laboratorio con ciclos de carga estables y se busca financiamiento para un módulo piloto.',
 4, 3, 1, 0, 'Patente en trámite', 'Inversión en spin-off', 'CUCEI', 4, 'M-05'),
(14, 'EólicaUrbana', 'Microturbina vertical silenciosa para azoteas.',
 'Diseño de aspas helicoidales que opera con baja velocidad de viento y poco ruido. El prototipo se probó en una azotea universitaria.',
 4, 6, 0, 0, 'Sin protección aún', 'Co-desarrollo', 'CUAAD', 4, 'M-06'),
(15, 'MovilidadSmart', 'Optimización de semáforos con inteligencia artificial.',
 'Ajusta los tiempos de cada cruce según el flujo vehicular medido con cámaras. Se simuló sobre datos de corredores reales y se prepara un piloto en una avenida.',
 5, 7, 0, 1, 'Secreto industrial', 'Piloto pagado', 'CUCEA', 1, 'E-01'),
(16, 'AguaClara', 'Monitoreo de fugas en redes de distribución de agua.',
 'Sensores acústicos y un modelo de detección localizan fugas antes de que sean visibles. Se probó en un tramo de red de la universidad.',
 5, 5, 1, 0, 'Patente en trámite', 'Co-desarrollo', 'CUCEI', 4, 'M-07'),
(17, 'Mobiliario modular reciclado', 'Mobiliario urbano modular hecho con plástico reciclado.',
 'Sistema de piezas intercambiables para parques y plazas, fabricado con plástico recuperado. Se instaló un conjunto piloto en un espacio universitario.',
 5, 6, 0, 0, 'Sin protección aún', 'Licenciamiento', 'CUAAD', 4, 'M-08'),
(18, 'Estudio Latido', 'Experiencias inmersivas para museos y espacios culturales.',
 'Producción de recorridos interactivos con proyección y sonido espacial. Ya operó en dos exposiciones abiertas al público.',
 6, 8, 0, 0, 'Sin protección aún', 'Licenciamiento', 'CUAAD', 1, 'F-01'),
(19, 'Tipografía accesible', 'Sistema de tipos para señalética legible por personas con baja visión.',
 'Familia tipográfica y guía de uso para señalética en espacios públicos. Se evaluó con grupos de usuarios y se prepara su publicación.',
 6, 5, 0, 0, 'Sin protección aún', 'Licenciamiento', 'CUAAD', 4, 'M-09'),
(20, 'Narrativa asistida', 'Plataforma de apoyo a la producción de contenido audiovisual educativo.',
 'Herramienta que ayuda a guionizar y planear piezas audiovisuales para cursos. Es un prototipo funcional en pruebas con docentes.',
 6, 4, 1, 0, 'Sin protección aún', 'Co-desarrollo', 'CUAAD', 4, 'M-10'),
(21, 'SentinelaPyme', 'Ciberseguridad administrada para pequeñas y medianas empresas.',
 'Servicio que monitorea la red de una empresa y bloquea intrusiones con configuración mínima. Protege a clientes reales y busca capital para constituirse como empresa.',
 7, 8, 1, 1, 'Secreto industrial', 'Inversión en spin-off', 'CUCEI', 1, 'G-01'),
(22, 'Datanalítica', 'Tablero predictivo de demanda para comercio minorista.',
 'Integra ventas históricas y variables externas para anticipar la demanda por tienda. Funciona con datos de un comercio piloto.',
 7, 7, 0, 0, 'Secreto industrial', 'Licenciamiento', 'CUCEA', 1, 'G-02'),
(23, 'ClínicaFácil', 'Agenda y expediente clínico interoperable para consultorios.',
 'Plataforma web que unifica agenda, expediente y recordatorios para consultorios independientes. Está en pruebas con tres consultorios.',
 7, 6, 1, 0, 'Sin protección aún', 'Co-desarrollo', 'CUCEI', 4, 'M-11'),
(24, 'Reconocimiento de señas', 'Reconocimiento de lengua de señas mexicana con la cámara del celular.',
 'Modelo que traduce un vocabulario básico de señas a texto en tiempo real. Se está ampliando el conjunto de datos con apoyo de la comunidad sorda.',
 7, 5, 0, 0, 'Sin protección aún', 'Co-desarrollo', 'CUCEI', 4, 'M-12');

INSERT INTO proyecto_investigador (proyecto_id, investigador_id, rol) VALUES
(1,2,'Líder'),(1,12,'Investigador'),(1,15,'Estudiante'),
(2,1,'Líder'),(2,15,'Estudiante'),
(3,12,'Líder'),(3,6,'Asesor'),(3,18,'Estudiante'),
(4,2,'Líder'),(4,18,'Estudiante'),
(5,4,'Líder'),(5,8,'Asesor'),(5,16,'Estudiante'),
(6,5,'Líder'),(6,3,'Investigador'),
(7,4,'Líder'),(7,9,'Asesor'),
(8,14,'Líder'),(8,5,'Asesor'),
(9,6,'Líder'),(9,18,'Estudiante'),
(10,3,'Líder'),(10,6,'Investigador'),(10,16,'Estudiante'),
(11,7,'Líder'),(11,6,'Asesor'),
(12,8,'Líder'),(12,18,'Estudiante'),
(13,7,'Líder'),(13,8,'Investigador'),
(14,9,'Líder'),(14,17,'Estudiante'),(14,8,'Asesor'),
(15,10,'Líder'),(15,3,'Investigador'),(15,16,'Estudiante'),
(16,2,'Líder'),(16,7,'Investigador'),
(17,9,'Líder'),(17,17,'Estudiante'),
(18,13,'Líder'),(18,17,'Estudiante'),
(19,9,'Líder'),(19,17,'Estudiante'),
(20,13,'Líder'),(20,10,'Investigador'),
(21,11,'Líder'),(21,16,'Estudiante'),
(22,10,'Líder'),(22,3,'Investigador'),
(23,11,'Líder'),(23,16,'Estudiante'),
(24,3,'Líder'),(24,16,'Estudiante');

-- ---------- Actividades ----------
-- Las fechas se calculan desde evento_inicio (día 0, 1, 2).
SET @ini = (SELECT valor FROM config_evento WHERE clave = 'evento_inicio');
INSERT INTO actividades (id, titulo, tipo, descripcion, ponente, zona_id, inicio, fin, cupo) VALUES
(1,  'Inauguración institucional', 'Conferencia', 'Apertura oficial del evento con autoridades y comité organizador.', 'Comité Organizador y autoridades de la Red Universitaria', 6, TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'09:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'10:00:00'), 400),
(2,  'Panorama de la transferencia tecnológica en Jalisco', 'Conferencia', 'Cómo se convierten los desarrollos universitarios en licencias, inversión y soluciones para la industria.', 'Mesa de especialistas', 6, TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'10:30:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'11:30:00'), 400),
(3,  'Reverse Pitch: retos de manufactura', 'Reverse Pitch', 'Empresas manufactureras presentan retos y los equipos proponen soluciones.', 'Empresas del sector manufacturero', 3, TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'11:30:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'13:00:00'), 120),
(4,  'Demostración en vivo: RehabGlove y NeuroBand', 'Presentación', 'Dos prototipos de salud con TRL 7 y 8 funcionando frente al público.', 'Equipos CUCS y CUCEI', 1, TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'12:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'12:45:00'), NULL),
(5,  'Rueda de negocios: salud y biotecnología', 'Rueda de negocios', 'Citas 1:1 entre expositores del eje de salud, compradores e inversionistas.', 'Citas programadas', 2, TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'13:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'15:00:00'), 30),
(6,  'Taller: cómo proteger tu prototipo', 'Taller', 'Fundamentos de propiedad intelectual para equipos que van a negociar.', 'Asesores en propiedad intelectual', 5, TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'16:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 0 DAY),'17:00:00'), 40),
(7,  'Financiamiento para spin-offs universitarios', 'Conferencia', 'Rutas de capital para convertir un prototipo en empresa.', 'Fondos y ángeles inversionistas', 6, TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'10:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'11:00:00'), 400),
(8,  'Reverse Pitch: agroindustria y agua', 'Reverse Pitch', 'Retos de empresas agroindustriales y de gestión de agua.', 'Empresas del sector agroindustrial', 3, TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'11:30:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'13:00:00'), 120),
(9,  'Presentaciones TRL 7 o superior: energía y manufactura', 'Presentación', 'Prototipos de alta madurez tecnológica presentan sus resultados.', 'Equipos de energía y manufactura', 1, TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'12:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'13:30:00'), NULL),
(10, 'Rueda de negocios: software y ciberseguridad', 'Rueda de negocios', 'Citas 1:1 entre equipos de software, fondos y compradores.', 'Citas programadas', 2, TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'14:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'16:00:00'), 30),
(11, 'Due diligence: revisión de patentes con asesores', 'Taller', 'Revisión de titularidad, estado de patentes y firma de convenios de confidencialidad.', 'Comité Técnico y de Propiedad Intelectual', 5, TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'16:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 1 DAY),'17:30:00'), 40),
(12, 'Reverse Pitch: ciudades y movilidad', 'Reverse Pitch', 'Retos urbanos de movilidad, agua y espacio público.', 'Consorcio Urbano Metropolitano y otros', 3, TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'10:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'11:30:00'), 120),
(13, 'Presentaciones: industrias creativas y software', 'Presentación', 'Demostraciones de proyectos de diseño, medios interactivos y software.', 'Equipos CUAAD, CUCEA y CUCEI', 1, TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'11:30:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'13:00:00'), NULL),
(14, 'Rueda de negocios: energía y manufactura', 'Rueda de negocios', 'Citas 1:1 entre equipos de energía y manufactura, compradores e inversionistas.', 'Citas programadas', 2, TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'13:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'15:00:00'), 30),
(15, 'Clausura y anuncio de acuerdos', 'Conferencia', 'Cierre institucional y reconocimiento de los acuerdos alcanzados.', 'Comité Organizador', 6, TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'16:00:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'17:00:00'), 400),
(16, 'Cóctel de networking VIP', 'Networking', 'Encuentro de clausura para inversionistas, speakers y directores de investigación y desarrollo.', 'Con apoyo de Casa Cuervo', 1, TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'17:30:00'), TIMESTAMP(DATE_ADD(@ini, INTERVAL 2 DAY),'19:30:00'), 150);

-- ---------- Banners del Home ----------
INSERT INTO anuncios (id, tipo, titulo, texto, empresa_id, enlace, orden, activo) VALUES
(1, 'patrocinador', 'Arca Continental', 'Patrocinador principal en especie. Bebidas de cortesía para los asistentes.', 1, NULL, 1, 1),
(2, 'patrocinador', 'Mercado Pago', 'Entorno cashless: paga en los stands de alimentos y merchandising con terminales Mercado Pago.', 3, NULL, 2, 1),
(3, 'patrocinador', 'PiSA / Electrolit', 'Hidratación clínica oficial y pabellón temático HealthTech.', 2, NULL, 3, 1),
(4, 'comite', 'Citas B2B abiertas', 'Solicita tu cita 1:1 en el Patent Room o el Due Diligence Desk.', NULL, '../registro.php#citas', 1, 1),
(5, 'comite', 'Programa disponible', 'Consulta el catálogo de prototipos y encuentra los de mayor madurez tecnológica.', NULL, 'catalogo.php', 2, 1);
