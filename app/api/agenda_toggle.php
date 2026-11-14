<?php
/**
 * Acciones de la agenda personal (solo POST, solo con sesión, con token CSRF).
 *   accion=agregar  actividad_id=N [alerta_minutos=15|30|60|'']   (por omisión 15 min)
 *   accion=quitar   actividad_id=N
 *   accion=alerta   actividad_id=N alerta_minutos=15|30|60|''     ('' = sin alerta)
 *   accion=visto    actividad_id=N                                (descarta la alerta mostrada)
 * Responde JSON: { ok, mensaje, agenda: [...], choques: [...] } o { ok:false, error }.
 */
require_once __DIR__ . '/../../includes/app.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responder(405, ['ok' => false, 'error' => 'Método no permitido.']);
}

$usuario = usuario_actual();
if ($usuario === null) {
    responder(401, ['ok' => false, 'error' => 'Inicia sesión para usar tu agenda.']);
}
if (!csrf_valido()) {
    responder(400, ['ok' => false, 'error' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.']);
}
if (!agenda_tabla_existe()) {
    error_log('UDG NEXUS: falta la tabla agenda_usuario (importa db/migraciones/004_agenda.sql).');
    responder(500, ['ok' => false, 'error' => 'La agenda no está disponible todavía (falta importar la migración 004).']);
}

$uid = (int) $usuario['id'];
$accion = (string) ($_POST['accion'] ?? '');
$actividadId = (int) ($_POST['actividad_id'] ?? 0);

$act = $actividadId > 0
    ? consulta_uno('SELECT a.id, a.titulo, a.inicio, a.fin FROM actividades a WHERE a.id = ?', 'i', [$actividadId])
    : null;
if ($act === null) {
    responder(404, ['ok' => false, 'error' => 'La actividad no existe.']);
}

/** Convierte el valor recibido en 15 / 30 / 60 o null (sin alerta). false si no es válido. */
function alerta_desde_post(string $valor)
{
    if ($valor === '') {
        return null;
    }
    $n = (int) $valor;
    return (string) $n === $valor && in_array($n, ALERTAS_VALIDAS, true) ? $n : false;
}

$mensaje = '';
$ok = false;

switch ($accion) {
    case 'agregar':
        $alerta = isset($_POST['alerta_minutos']) ? alerta_desde_post((string) $_POST['alerta_minutos']) : 15;
        if ($alerta === false) {
            responder(400, ['ok' => false, 'error' => 'Elige una alerta de 15, 30 o 60 minutos.']);
        }
        // INSERT IGNORE: si ya estaba en la agenda no pasa nada (doble toque).
        $ok = ejecutar(
            'INSERT IGNORE INTO agenda_usuario (usuario_id, actividad_id, alerta_minutos, alerta_vista) VALUES (?, ?, ?, 0)',
            'iii',
            [$uid, $actividadId, $alerta]
        );
        $mensaje = 'Agregaste «' . $act['titulo'] . '» a tu agenda'
                 . ($alerta !== null ? ' con alerta de ' . $alerta . ' min.' : '.');
        break;

    case 'quitar':
        $ok = ejecutar('DELETE FROM agenda_usuario WHERE usuario_id = ? AND actividad_id = ?', 'ii', [$uid, $actividadId]);
        $mensaje = 'Quitaste «' . $act['titulo'] . '» de tu agenda.';
        break;

    case 'alerta':
        $alerta = alerta_desde_post((string) ($_POST['alerta_minutos'] ?? ''));
        if ($alerta === false) {
            responder(400, ['ok' => false, 'error' => 'Elige una alerta de 15, 30 o 60 minutos.']);
        }
        // Al cambiar la alerta se vuelve a armar (alerta_vista = 0).
        $ok = ejecutar(
            'UPDATE agenda_usuario SET alerta_minutos = ?, alerta_vista = 0 WHERE usuario_id = ? AND actividad_id = ?',
            'iii',
            [$alerta, $uid, $actividadId]
        );
        $mensaje = $alerta !== null
            ? 'Te avisaremos ' . $alerta . ' min antes de «' . $act['titulo'] . '».'
            : 'Quitaste la alerta de «' . $act['titulo'] . '».';
        break;

    case 'visto':
        $ok = ejecutar('UPDATE agenda_usuario SET alerta_vista = 1 WHERE usuario_id = ? AND actividad_id = ?', 'ii', [$uid, $actividadId]);
        $mensaje = 'Alerta descartada.';
        break;

    default:
        responder(400, ['ok' => false, 'error' => 'Acción no válida.']);
}

if (!$ok) {
    responder(500, ['ok' => false, 'error' => 'No se pudo guardar el cambio. Inténtalo de nuevo.']);
}

$agenda = agenda_usuario($uid);
$choques = [];
if ($accion === 'agregar') {
    foreach (agenda_choca_con($act, $agenda) as $o) {
        $choques[] = ['id' => (int) $o['id'], 'titulo' => $o['titulo'], 'inicio' => $o['inicio'], 'fin' => $o['fin']];
    }
    if ($choques) {
        $mensaje .= ' Ojo: ' . lcfirst(agenda_texto_choque(agenda_choca_con($act, $agenda), true));
    }
}

responder(200, [
    'ok'      => true,
    'accion'  => $accion,
    'mensaje' => $mensaje,
    'agenda'  => agenda_para_js($agenda),
    'choques' => $choques,
]);
