<?php
/**
 * Marcar avisos como leídos (solo POST, solo con sesión, con token CSRF).
 *   accion=leer        id=N   marca un aviso
 *   accion=leer_todos         marca todos los visibles
 * Responde JSON: { ok, mensaje, no_leidos } o { ok:false, error }.
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
    responder(401, ['ok' => false, 'error' => 'Inicia sesión para ver tus avisos.']);
}
if (!csrf_valido()) {
    responder(400, ['ok' => false, 'error' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.']);
}
if (!avisos_tablas_existen()) {
    error_log('UDG NEXUS: faltan las tablas de avisos (importa db/migraciones/007_notificaciones.sql).');
    responder(500, ['ok' => false, 'error' => 'Los avisos no están disponibles todavía (falta importar la migración 007).']);
}

$uid = (int) $usuario['id'];
$rol = (string) $usuario['rol'];
$accion = (string) ($_POST['accion'] ?? '');

switch ($accion) {
    case 'leer':
        $idTexto = (string) ($_POST['id'] ?? '');
        $id = ctype_digit($idTexto) ? (int) $idTexto : 0;
        if ($id <= 0 || !aviso_marcar_leido($uid, $rol, $id)) {
            responder(404, ['ok' => false, 'error' => 'Ese aviso ya no está disponible.']);
        }
        $mensaje = 'Aviso marcado como leído.';
        break;

    case 'leer_todos':
        if (!aviso_marcar_todos($uid, $rol)) {
            responder(500, ['ok' => false, 'error' => 'No se pudo guardar el cambio. Inténtalo de nuevo.']);
        }
        $mensaje = 'Todos los avisos quedaron como leídos.';
        break;

    default:
        responder(400, ['ok' => false, 'error' => 'Acción no válida.']);
}

responder(200, [
    'ok'        => true,
    'accion'    => $accion,
    'mensaje'   => $mensaje,
    'no_leidos' => avisos_no_leidos($uid, $rol),
]);
