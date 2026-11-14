<?php
/**
 * Hora del evento y alertas pendientes (la app lo consulta cada pocos segundos).
 * GET api/ahora.php  ->  JSON
 *   ok, ahora ('AAAA-MM-DD HH:MM:SS'), hora ('HH:MM'), fecha ('miércoles 10 de marzo'),
 *   dia (1, 2, 3 o null), modo (real|fijo|avance), velocidad (0 = quieto, 1 = tiempo real, 60 = x60...),
 *   simulado (bool), logueado (bool), alertas [{id, titulo, zona, hora_inicio, minutos, mensaje}]
 * Las alertas solo se calculan si hay sesión y la migración 004 está importada.
 *
 * Avisos (Fase 5): con sesión y la migración 007, agrega  avisos { no_leidos, visibles[], nuevos[] }.
 * ?av=N  (id más alto que la página ya conoce) pide además los avisos con id > N en "nuevos";
 * sin ?av solo se devuelven el conteo y los ids visibles. Así el sondeo de la agenda sirve también
 * para los avisos y no hace falta un segundo temporizador.
 */
require_once __DIR__ . '/../../includes/app.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$ahora = ahora_evento();
$modo = reloj_modo();
$usuario = usuario_actual();

$alertas = [];
if ($usuario !== null && agenda_tabla_existe()) {
    $alertas = agenda_alertas_pendientes((int) $usuario['id'], $ahora);
}

$avisos = null;
if ($usuario !== null && avisos_tablas_existen()) {
    $av = $_GET['av'] ?? null;
    $desde = (is_string($av) && ctype_digit($av) && strlen($av) < 10) ? (int) $av : null;
    $avisos = avisos_para_sondeo($usuario, $desde);
}

// Esta página se consulta sin parar: se libera la sesión para no bloquear otras peticiones.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

echo json_encode([
    'ok'        => true,
    'ahora'     => $ahora->format('Y-m-d H:i:s'),
    'hora'      => hora_corta($ahora),
    'fecha'     => fecha_larga($ahora),
    'dia'       => dia_del_evento($ahora),
    'modo'      => $modo,
    'velocidad' => $modo === 'fijo' ? 0 : ($modo === 'avance' ? max(1, (int) config_evento('reloj_velocidad', '1')) : 1),
    'simulado'  => reloj_simulado_activo(),
    'logueado'  => $usuario !== null,
    'alertas'   => $alertas,
    'avisos'    => $avisos,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
