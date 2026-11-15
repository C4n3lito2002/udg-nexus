<?php
/**
 * UDG NEXUS — Núcleo de la app móvil.
 * Se incluye al inicio de cada página dentro de /app:
 *   require_once __DIR__ . '/../includes/app.php';
 *
 * Contiene: conexión, helpers de salida, reloj del evento,
 * consultas de catálogo y búsqueda, y componentes de interfaz.
 */

require_once __DIR__ . '/../config.php';

date_default_timezone_set('America/Mexico_City');

/* Sesiones y roles: se cargan al final de este archivo, cuando ya existen db() y consulta(). */

/* ============================================================
   Utilidades básicas
   ============================================================ */

/** Escapa texto para HTML. Úsalo en TODA salida de datos. */
if (!function_exists('h')) {
    function h($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Conexión única por petición. */
function db(): mysqli
{
    static $conexion = null;
    if ($conexion === null) {
        $conexion = obtenerConexion();
    }
    return $conexion;
}

/**
 * Ejecuta una consulta preparada y devuelve todas las filas.
 * $tipos usa la notación de bind_param (ej. 'si'); $params son los valores.
 */
function consulta(string $sql, string $tipos = '', array $params = []): array
{
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        error_log('UDG NEXUS SQL: ' . db()->error);
        return [];
    }
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $filas = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $filas;
}

/** Primera fila de una consulta, o null. */
function consulta_uno(string $sql, string $tipos = '', array $params = []): ?array
{
    $filas = consulta($sql, $tipos, $params);
    return $filas[0] ?? null;
}

/** Ejecuta INSERT / UPDATE / DELETE preparado. Devuelve true si salió bien. */
function ejecutar(string $sql, string $tipos = '', array $params = []): bool
{
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        error_log('UDG NEXUS SQL: ' . db()->error);
        return false;
    }
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $ok = $stmt->execute();
    if (!$ok) {
        error_log('UDG NEXUS SQL: ' . $stmt->error);
    }
    $stmt->close();
    return $ok;
}

/* ============================================================
   Reloj del evento
   Si config_evento.reloj_simulado tiene valor, toda la app usa esa
   hora (ideal para demostrar el feed "En este momento" en cualquier
   fecha). Si está vacío, usa la hora real.
   ============================================================ */

function config_evento(string $clave, ?string $defecto = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (consulta('SELECT clave, valor FROM config_evento') as $f) {
            $cache[$f['clave']] = $f['valor'];
        }
    }
    return $cache[$clave] ?? $defecto;
}

/** Convierte 'AAAA-MM-DD HH:MM:SS' en fecha, o null si está vacío o mal escrito. */
function parse_fecha_hora(?string $valor): ?DateTimeImmutable
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $valor);
    return $d !== false ? $d : null;
}

/**
 * Modo del reloj: 'real' (hora real), 'fijo' (se queda en reloj_base)
 * o 'avance' (arranca en reloj_base y corre solo, x reloj_velocidad).
 * Si la migración 003 no se ha importado, se usa el valor antiguo reloj_simulado.
 */
function reloj_modo(): string
{
    $m = config_evento('reloj_modo');
    if ($m === null) {
        return parse_fecha_hora(config_evento('reloj_simulado', '')) ? 'fijo' : 'real';
    }
    return in_array($m, ['real', 'fijo', 'avance'], true) ? $m : 'real';
}

function reloj_simulado_activo(): bool
{
    return reloj_modo() !== 'real';
}

function ahora_evento(): DateTimeImmutable
{
    $modo = reloj_modo();
    if ($modo === 'real') {
        return new DateTimeImmutable('now');
    }
    $base = parse_fecha_hora(config_evento('reloj_base')) ?? parse_fecha_hora(config_evento('reloj_simulado'));
    if ($base === null) {
        return new DateTimeImmutable('now');
    }
    if ($modo === 'fijo') {
        return $base;
    }
    $ancla = parse_fecha_hora(config_evento('reloj_ancla'));
    if ($ancla === null) {
        return $base;
    }
    $velocidad = max(1, (int) config_evento('reloj_velocidad', '1'));
    $transcurrido = max(0, time() - $ancla->getTimestamp());
    return $base->modify('+' . ($transcurrido * $velocidad) . ' seconds');
}

/* ============================================================
   Formato de fechas en español (sin depender de locales del sistema)
   ============================================================ */

const DIAS_ES  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const MESES_ES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

function fecha_larga(DateTimeInterface $d): string
{
    return DIAS_ES[(int) $d->format('w')] . ' ' . $d->format('j') . ' de ' . MESES_ES[(int) $d->format('n') - 1];
}

function hora_corta(DateTimeInterface $d): string
{
    return $d->format('H:i');
}

/** Estado de una actividad respecto al reloj: clase CSS y etiqueta. */
function estado_actividad(DateTimeInterface $inicio, DateTimeInterface $fin, DateTimeInterface $ahora): array
{
    if ($ahora >= $inicio && $ahora < $fin) {
        $resta = (int) ceil(($fin->getTimestamp() - $ahora->getTimestamp()) / 60);
        return ['en-curso', 'En curso', 'Termina en ' . $resta . ' min'];
    }
    if ($ahora < $inicio) {
        $min = (int) ceil(($inicio->getTimestamp() - $ahora->getTimestamp()) / 60);
        if ($min <= 60) {
            return ['pronto', 'Empieza en ' . $min . ' min', 'Hoy a las ' . hora_corta($inicio)];
        }
        return ['luego', fecha_larga($inicio), hora_corta($inicio) . ' a ' . hora_corta($fin)];
    }
    return ['pasada', 'Finalizada', hora_corta($inicio) . ' a ' . hora_corta($fin)];
}

/* ============================================================
   TRL (nivel de madurez tecnológica)
   ============================================================ */

function trl_nivel(int $trl): string
{
    if ($trl >= 7) {
        return 'Alta madurez: demostrado en entorno real';
    }
    if ($trl >= 4) {
        return 'Madurez media: validado en laboratorio o entorno relevante';
    }
    return 'Etapa temprana: concepto y pruebas iniciales';
}

/** Medidor de 9 segmentos. */
function trl_medidor(int $trl): string
{
    $html = '<div class="trl-medidor" role="img" aria-label="Nivel de madurez tecnológica TRL ' . $trl . ' de 9">';
    for ($i = 1; $i <= 9; $i++) {
        $html .= '<span class="' . ($i <= $trl ? 'on' : '') . '"></span>';
    }
    return $html . '</div>';
}

/* ============================================================
   Iconos (SVG en línea, heredan el color del texto)
   ============================================================ */

function icono(string $nombre): string
{
    $trazos = [
        'inicio'   => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/><path d="M10 19.5v-5h4v5"/>',
        'catalogo' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'agenda'   => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'mapa'     => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0C18.5 15.4 12 21 12 21z"/><circle cx="12" cy="10" r="2.4"/>',
        'gafete'   => '<rect x="4" y="3" width="16" height="18" rx="2"/><rect x="8" y="12" width="3" height="3"/><rect x="13" y="12" width="3" height="3"/><path d="M8 7h8M9.5 9.2h5"/>',
        'buscar'   => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'volver'   => '<path d="M15 5l-7 7 7 7"/>',
        'sitio'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.8 2.6 4 5.6 4 9s-1.2 6.4-4 9c-2.8-2.6-4-5.6-4-9s1.2-6.4 4-9z"/>',
        'ubicacion'=> '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0C18.5 15.4 12 21 12 21z"/><circle cx="12" cy="10" r="2.4"/>',
        'reloj'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'usuario'  => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20c.8-3.8 3.7-6 7.5-6s6.7 2.2 7.5 6"/>',
        'salir'    => '<path d="M9 4.5H5.5v15H9"/><path d="M14 8l4 4-4 4M18 12H9.5"/>',
        'alerta'   => '<path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 1.5h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
        'ok'       => '<circle cx="12" cy="12" r="9"/><path d="m8 12.3 2.8 2.8L16 9.5"/>',
        'aviso'    => '<path d="M12 4 2.8 19.5h18.4z"/><path d="M12 10v4.5M12 17.2v.1"/>',
        'estrella' => '<path d="m12 3.6 2.6 5.3 5.8.8-4.2 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.2-4.1 5.8-.8z"/>',
    ];
    $d = $trazos[$nombre] ?? '';
    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/* ============================================================
   Consultas del catálogo
   ============================================================ */

function etiqueta_tipo_empresa(string $tipo): string
{
    return [
        'patrocinador' => 'Patrocinador',
        'comprador'    => 'Comprador',
        'fondo'        => 'Fondo / inversionista',
        'aliado'       => 'Aliado institucional',
    ][$tipo] ?? ucfirst($tipo);
}

function areas_todas(): array
{
    return consulta('SELECT id, nombre, slug FROM areas_tecnologicas ORDER BY orden');
}

/**
 * Proyectos con filtros opcionales.
 * $f: q (texto), area (slug), trl ('alta'|'media'|'temprana'), inversion (bool), limite (int)
 */
function proyectos_filtrados(array $f = []): array
{
    $donde = [];
    $tipos = '';
    $params = [];

    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $donde[] = '(p.nombre LIKE ? OR p.resumen LIKE ? OR p.descripcion LIKE ? OR p.centro LIKE ? OR a.nombre LIKE ?
                     OR EXISTS (SELECT 1 FROM proyecto_investigador pi JOIN investigadores i ON i.id = pi.investigador_id
                                WHERE pi.proyecto_id = p.id AND i.nombre LIKE ?))';
        $tipos .= 'ssssss';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    if (!empty($f['area'])) {
        $donde[] = 'a.slug = ?';
        $tipos .= 's';
        $params[] = (string) $f['area'];
    }
    $rangos = ['alta' => [7, 9], 'media' => [4, 6], 'temprana' => [1, 3]];
    if (!empty($f['trl']) && isset($rangos[$f['trl']])) {
        $donde[] = 'p.trl BETWEEN ? AND ?';
        $tipos .= 'ii';
        array_push($params, $rangos[$f['trl']][0], $rangos[$f['trl']][1]);
    }
    if (!empty($f['inversion'])) {
        $donde[] = 'p.busca_inversion = 1';
    }

    $sql = 'SELECT p.id, p.nombre, p.resumen, p.trl, p.busca_inversion, p.destacado, p.stand,
                   a.nombre AS area, a.slug AS area_slug, z.nombre AS zona
            FROM proyectos p
            JOIN areas_tecnologicas a ON a.id = p.area_id
            LEFT JOIN zonas z ON z.id = p.zona_id'
         . ($donde ? ' WHERE ' . implode(' AND ', $donde) : '')
         . ' ORDER BY p.trl DESC, p.nombre';

    if (!empty($f['limite'])) {
        $sql .= ' LIMIT ' . (int) $f['limite'];
    }
    return consulta($sql, $tipos, $params);
}

/** Proyectos de alta madurez (TRL 7+) o que buscan inversión, para la vitrina. */
function proyectos_vitrina(int $limite = 6): array
{
    return consulta(
        'SELECT p.id, p.nombre, p.resumen, p.trl, p.busca_inversion, a.nombre AS area
         FROM proyectos p JOIN areas_tecnologicas a ON a.id = p.area_id
         WHERE p.trl >= 7 OR p.busca_inversion = 1
         ORDER BY p.destacado DESC, p.trl DESC, p.nombre
         LIMIT ' . (int) $limite
    );
}

function empresas_filtradas(array $f = []): array
{
    $donde = [];
    $tipos = '';
    $params = [];

    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $donde[] = '(e.nombre LIKE ? OR e.sector LIKE ? OR e.descripcion LIKE ? OR e.busca LIKE ?)';
        $tipos .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }
    $validos = ['patrocinador', 'comprador', 'fondo', 'aliado'];
    if (!empty($f['tipo']) && in_array($f['tipo'], $validos, true)) {
        $donde[] = 'e.tipo = ?';
        $tipos .= 's';
        $params[] = $f['tipo'];
    }

    $sql = 'SELECT e.id, e.nombre, e.tipo, e.nivel_patrocinio, e.sector, e.busca, e.ticket
            FROM empresas e'
         . ($donde ? ' WHERE ' . implode(' AND ', $donde) : '')
         . " ORDER BY FIELD(e.tipo,'fondo','comprador','patrocinador','aliado'), e.nombre";
    return consulta($sql, $tipos, $params);
}

/** Actividades en curso o que comienzan dentro de $ventanaMin minutos. */
function actividades_ahora(DateTimeInterface $ahora, int $ventanaMin = 60): array
{
    $desde = $ahora->format('Y-m-d H:i:s');
    $hasta = $ahora->modify('+' . $ventanaMin . ' minutes')->format('Y-m-d H:i:s');
    return consulta(
        'SELECT a.id, a.titulo, a.tipo, a.ponente, a.inicio, a.fin, z.id AS zona_id, z.nombre AS zona
         FROM actividades a JOIN zonas z ON z.id = a.zona_id
         WHERE (a.inicio <= ? AND a.fin > ?) OR (a.inicio > ? AND a.inicio <= ?)
         ORDER BY a.inicio, a.id',
        'ssss',
        [$desde, $desde, $desde, $hasta]
    );
}

/** Las próximas actividades (cuando no hay nada en curso). */
function actividades_proximas(DateTimeInterface $ahora, int $limite = 3): array
{
    return consulta(
        'SELECT a.id, a.titulo, a.tipo, a.ponente, a.inicio, a.fin, z.id AS zona_id, z.nombre AS zona
         FROM actividades a JOIN zonas z ON z.id = a.zona_id
         WHERE a.inicio > ? ORDER BY a.inicio, a.id LIMIT ' . (int) $limite,
        's',
        [$ahora->format('Y-m-d H:i:s')]
    );
}

/* ============================================================
   Búsqueda universal
   Devuelve grupos: proyectos, areas, empresas, investigadores.
   ============================================================ */

function buscar_todo(string $q, int $limite = 5): array
{
    $q = trim($q);
    if (mb_strlen($q) < 2) {
        return ['proyectos' => [], 'areas' => [], 'empresas' => [], 'investigadores' => []];
    }
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $lim = max(1, $limite);

    $proyectos = proyectos_filtrados(['q' => $q, 'limite' => $lim]);

    $areas = consulta(
        'SELECT nombre, slug, descripcion FROM areas_tecnologicas
         WHERE nombre LIKE ? OR descripcion LIKE ? ORDER BY orden LIMIT ' . $lim,
        'ss',
        [$like, $like]
    );

    $empresas = consulta(
        'SELECT id, nombre, tipo, sector FROM empresas
         WHERE nombre LIKE ? OR sector LIKE ? OR descripcion LIKE ? OR busca LIKE ?
         ORDER BY nombre LIMIT ' . $lim,
        'ssss',
        [$like, $like, $like, $like]
    );

    $investigadores = consulta(
        'SELECT i.id, i.nombre, i.centro, i.especialidad,
                (SELECT MIN(pi.proyecto_id) FROM proyecto_investigador pi WHERE pi.investigador_id = i.id) AS proyecto_id
         FROM investigadores i
         WHERE i.nombre LIKE ? OR i.especialidad LIKE ? OR i.centro LIKE ?
         ORDER BY i.nombre LIMIT ' . $lim,
        'sss',
        [$like, $like, $like]
    );

    return compact('proyectos', 'areas', 'empresas', 'investigadores');
}

/* ============================================================
   Componentes de interfaz reutilizables
   ============================================================ */

/** Iniciales para el avatar (ignora títulos como Dr., Mtra., Ing.). */
function iniciales(string $nombre): string
{
    $sinTitulo = preg_replace('/^(Dra?\.|Mtra?\.|M\.C\.|Ing\.|Lic\.)\s*/u', '', trim($nombre));
    $partes = preg_split('/\s+/u', trim((string) $sinTitulo));
    $ini = mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1);
    return mb_strtoupper($ini);
}

/** Día del evento (1, 2, 3...) para una fecha, o null si está fuera del evento. */
function dia_del_evento(DateTimeInterface $fecha): ?int
{
    $inicio = config_evento('evento_inicio');
    if (!$inicio) {
        return null;
    }
    $d0 = new DateTimeImmutable($inicio . ' 00:00:00');
    $dias = (int) $d0->diff(new DateTimeImmutable($fecha->format('Y-m-d') . ' 00:00:00'))->format('%r%a');
    $total = (int) config_evento('evento_dias', '3');
    return ($dias >= 0 && $dias < $total) ? $dias + 1 : null;
}

/** Tarjeta de un proyecto (catálogo, búsqueda, relacionados y recomendaciones; $motivo es opcional). */
function tarjeta_proyecto(array $p, ?string $motivo = null): string
{
    $html  = '<a class="tarjeta" href="proyecto.php?id=' . (int) $p['id'] . '">';
    if ($motivo !== null && $motivo !== '') {
        $html .= reco_motivo_html($motivo);
    }
    $html .= '<div class="chips"><span class="chip">' . h($p['area']) . '</span>';
    if (!empty($p['busca_inversion'])) {
        $html .= '<span class="chip inv">Busca inversión</span>';
    }
    $html .= '</div>';
    $html .= '<h3>' . h($p['nombre']) . '</h3>';
    $html .= '<p>' . h($p['resumen']) . '</p>';
    $html .= '<div class="trl-fila"><span class="trl-num">TRL ' . (int) $p['trl'] . '</span>' . trl_medidor((int) $p['trl']) . '</div>';
    if (!empty($p['zona']) || !empty($p['stand'])) {
        $html .= '<div class="pie" style="margin-top:0.5rem">' . icono('ubicacion')
               . '<span>' . h(trim(($p['zona'] ?? '') . (!empty($p['stand']) ? ' · Stand ' . $p['stand'] : ''))) . '</span></div>';
    }
    return $html . '</a>';
}

/** Tarjeta de una empresa. */
function tarjeta_empresa(array $e): string
{
    $html  = '<a class="tarjeta" href="empresa.php?id=' . (int) $e['id'] . '">';
    $html .= '<div class="chips"><span class="chip oscuro">' . h(etiqueta_tipo_empresa($e['tipo'])) . '</span>';
    if (!empty($e['nivel_patrocinio'])) {
        $html .= '<span class="chip">Nivel ' . h($e['nivel_patrocinio']) . '</span>';
    }
    $html .= '</div>';
    $html .= '<h3>' . h($e['nombre']) . '</h3>';
    if (!empty($e['sector'])) {
        $html .= '<p>' . h($e['sector']) . '</p>';
    }
    if (!empty($e['busca'])) {
        $html .= '<div class="pie"><span><strong>Busca:</strong> ' . h($e['busca']) . '</span></div>';
    }
    return $html . '</a>';
}

/* ============================================================
   Agenda personal con alertas (Fase 2, parte 2)
   Todo se calcula con el RELOJ DEL EVENTO (ahora_evento()), no con
   la hora real, para que la demo funcione en cualquier fecha.
   ============================================================ */

/** Minutos de aviso permitidos antes del inicio de una actividad. */
const ALERTAS_VALIDAS = [15, 30, 60];

/** True si ya se importó db/migraciones/004_agenda.sql. */
function agenda_tabla_existe(): bool
{
    static $existe = null;
    if ($existe === null) {
        $existe = consulta("SHOW TABLES LIKE 'agenda_usuario'") !== [];
    }
    return $existe;
}

/** Días del evento: [['n' => 1, 'fecha' => DateTimeImmutable], ...]. */
function agenda_fechas(): array
{
    $d0 = new DateTimeImmutable((string) config_evento('evento_inicio', '2027-03-10'));
    $total = max(1, (int) config_evento('evento_dias', '3'));
    $dias = [];
    for ($i = 0; $i < $total; $i++) {
        $dias[] = ['n' => $i + 1, 'fecha' => $d0->modify('+' . $i . ' days')];
    }
    return $dias;
}

/** Actividades que empiezan en un día, con su zona. */
function actividades_del_dia(DateTimeInterface $dia): array
{
    $d = $dia->format('Y-m-d');
    $sig = (new DateTimeImmutable($d))->modify('+1 day')->format('Y-m-d');
    return consulta(
        'SELECT a.id, a.titulo, a.tipo, a.ponente, a.inicio, a.fin, a.cupo, z.id AS zona_id, z.nombre AS zona
         FROM actividades a JOIN zonas z ON z.id = a.zona_id
         WHERE a.inicio >= ? AND a.inicio < ?
         ORDER BY a.inicio, a.id',
        'ss',
        [$d . ' 00:00:00', $sig . ' 00:00:00']
    );
}

/** Agenda de una persona, en orden cronológico. */
function agenda_usuario(int $uid): array
{
    return consulta(
        'SELECT g.actividad_id AS id, g.alerta_minutos, g.alerta_vista,
                a.titulo, a.tipo, a.ponente, a.inicio, a.fin, z.id AS zona_id, z.nombre AS zona
         FROM agenda_usuario g
         JOIN actividades a ON a.id = g.actividad_id
         JOIN zonas z ON z.id = a.zona_id
         WHERE g.usuario_id = ?
         ORDER BY a.inicio, a.id',
        'i',
        [$uid]
    );
}

/** Dos actividades se empalman si una empieza antes de que la otra termine. */
function actividades_se_empalman(array $a, array $b): bool
{
    return $a['inicio'] < $b['fin'] && $b['inicio'] < $a['fin'];
}

/** Elementos de $agenda que se empalman con la actividad $a (sin contarse a sí misma). */
function agenda_choca_con(array $a, array $agenda): array
{
    $res = [];
    foreach ($agenda as $o) {
        if ((int) $o['id'] !== (int) $a['id'] && actividades_se_empalman($a, $o)) {
            $res[] = $o;
        }
    }
    return $res;
}

/** Texto del aviso de empalme (el mismo que arma app.js al agregar o quitar). */
function agenda_texto_choque(array $choca, bool $enAgenda): string
{
    if (!$choca) {
        return '';
    }
    $partes = array_map(static function ($o) {
        return '«' . $o['titulo'] . '» (' . hora_corta(new DateTimeImmutable($o['inicio']))
             . ' a ' . hora_corta(new DateTimeImmutable($o['fin'])) . ')';
    }, $choca);
    return ($enAgenda ? 'Se empalma con ' : 'Si la agregas, se empalma con ') . implode(' y ', $partes) . '.';
}

/** Cuántos empalmes (pares) hay dentro de una agenda. */
function agenda_total_empalmes(array $agenda): int
{
    $n = 0;
    foreach ($agenda as $i => $a) {
        foreach ($agenda as $j => $b) {
            if ($j > $i && actividades_se_empalman($a, $b)) {
                $n++;
            }
        }
    }
    return $n;
}

/** Texto corto de estado respecto al reloj (los mismos textos que app.js al avanzar la hora). */
function estado_agenda(array $a, DateTimeImmutable $ahora): array
{
    [$clase, $etiqueta] = estado_actividad(new DateTimeImmutable($a['inicio']), new DateTimeImmutable($a['fin']), $ahora);
    return [$clase, $clase === 'luego' ? '' : $etiqueta];
}

/** Tarjeta de una actividad con su botón de agenda. $motivo (opcional): línea "Porque te interesa…" de las recomendaciones. */
function tarjeta_agenda(array $a, ?array $g, DateTimeImmutable $ahora, ?array $usuario, bool $tablaOk, array $miAgenda, ?string $motivo = null): string
{
    $ini = new DateTimeImmutable($a['inicio']);
    $fin = new DateTimeImmutable($a['fin']);
    [$clase, $etiqueta] = estado_agenda($a, $ahora);
    $enAgenda = $g !== null;
    $textoChoque = $usuario ? agenda_texto_choque(agenda_choca_con($a, $miAgenda), $enAgenda) : '';
    $alerta = $enAgenda && $g['alerta_minutos'] !== null ? (int) $g['alerta_minutos'] : null;

    $h  = '<article class="act-ag ' . h($clase) . '" data-actividad="' . (int) $a['id'] . '"'
        . ' data-inicio="' . h($a['inicio']) . '" data-fin="' . h($a['fin']) . '" data-titulo="' . h($a['titulo']) . '">';
    $h .= '<div class="act-hora"><strong>' . h(hora_corta($ini)) . '</strong><span>' . h(hora_corta($fin)) . '</span></div>';
    $h .= '<div class="act-cuerpo">';
    $h .= '<div class="chips"><span class="estado ' . h($clase) . '" data-estado' . ($etiqueta === '' ? ' hidden' : '') . '>' . h($etiqueta) . '</span>'
        . '<span class="chip ok" data-chip-agenda' . ($enAgenda ? '' : ' hidden') . '>En tu agenda</span></div>';
    if ($motivo !== null && $motivo !== '') {
        $h .= reco_motivo_html($motivo);
    }
    $h .= '<h3>' . h($a['titulo']) . '</h3>';
    $zonaTxt = !empty($a['zona_id'])
        ? '<a class="enlace-mapa" href="mapa.php?zona=' . (int) $a['zona_id'] . '">' . h($a['zona']) . '</a>'
        : h($a['zona']);
    $h .= '<span class="meta">' . h($a['tipo']) . ' · ' . $zonaTxt . '</span>';
    if (!empty($a['ponente'])) {
        $h .= '<span class="meta">' . h($a['ponente']) . '</span>';
    }
    if ($usuario) {
        $h .= '<p class="choque" data-choque' . ($textoChoque === '' ? ' hidden' : '') . '>' . icono('aviso') . '<span>' . h($textoChoque) . '</span></p>';
    }

    if ($usuario && $tablaOk) {
        $h .= '<div class="act-acciones">';
        $h .= '<button type="button" class="btn-agenda' . ($enAgenda ? ' quitar' : '') . '" data-agenda-btn>'
            . ($enAgenda ? 'Quitar de mi agenda' : 'Agregar a mi agenda') . '</button>';
        $h .= '<label class="alerta-sel" data-alerta-wrap' . ($enAgenda ? '' : ' hidden') . '>' . icono('alerta') . '<span>Avisarme</span>'
            . '<select data-alerta-sel aria-label="Alerta para ' . h($a['titulo']) . '">';
        foreach ([['', 'Sin alerta'], [15, '15 min antes'], [30, '30 min antes'], [60, '60 min antes']] as [$v, $t]) {
            $h .= '<option value="' . h($v) . '"' . ((string) $v === (string) ($alerta ?? '') ? ' selected' : '') . '>' . h($t) . '</option>';
        }
        $h .= '</select></label></div>';
    } elseif (!$usuario) {
        $h .= '<div class="act-acciones"><a class="btn-agenda" href="login.php?volver=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '') . '">Entra para agregar a tu agenda</a></div>';
    }
    return $h . '</div></article>';
}

/** Datos mínimos de la agenda para la página y la API (JSON). */
function agenda_para_js(array $agenda): array
{
    return array_map(static fn($g) => [
        'id'     => (int) $g['id'],
        'titulo' => $g['titulo'],
        'inicio' => $g['inicio'],
        'fin'    => $g['fin'],
        'alerta' => $g['alerta_minutos'] !== null ? (int) $g['alerta_minutos'] : null,
    ], $agenda);
}

/**
 * Alertas que ya "tocan" según el reloj del evento y que la persona no ha visto:
 * la alerta se abre (inicio - minutos) y se cierra cuando termina la actividad.
 */
function agenda_alertas_pendientes(int $uid, DateTimeInterface $ahora): array
{
    $t = $ahora->format('Y-m-d H:i:s');
    $filas = consulta(
        'SELECT g.actividad_id AS id, g.alerta_minutos, a.titulo, a.inicio, a.fin, z.nombre AS zona
         FROM agenda_usuario g
         JOIN actividades a ON a.id = g.actividad_id
         JOIN zonas z ON z.id = a.zona_id
         WHERE g.usuario_id = ? AND g.alerta_minutos IS NOT NULL AND g.alerta_vista = 0
           AND a.fin > ? AND DATE_SUB(a.inicio, INTERVAL g.alerta_minutos MINUTE) <= ?
         ORDER BY a.inicio, a.id',
        'iss',
        [$uid, $t, $t]
    );
    $alertas = [];
    foreach ($filas as $f) {
        $ini = new DateTimeImmutable($f['inicio']);
        $enCurso = $ahora >= $ini;
        $min = $enCurso ? 0 : (int) ceil(($ini->getTimestamp() - $ahora->getTimestamp()) / 60);
        $alertas[] = [
            'id'          => (int) $f['id'],
            'titulo'      => $f['titulo'],
            'zona'        => $f['zona'],
            'hora_inicio' => hora_corta($ini),
            'minutos'     => $min,
            'mensaje'     => $enCurso
                ? 'Ya empezó a las ' . hora_corta($ini) . '.'
                : 'Empieza en ' . $min . ' min, a las ' . hora_corta($ini) . '.',
        ];
    }
    return $alertas;
}

/* ============================================================
   Sesiones, roles y CSRF
   ============================================================ */

require_once __DIR__ . '/auth.php';
sesion_iniciar();
require_once __DIR__ . '/boletos.php';
require_once __DIR__ . '/avisos.php';
require_once __DIR__ . '/recomendaciones.php';
