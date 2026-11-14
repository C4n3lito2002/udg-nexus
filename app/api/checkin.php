<?php
/**
 * Validación de un boleto en la entrada (solo POST, solo staff o administrador, con token CSRF).
 *   token=<lo que leyó el QR o tecleó el staff>
 * Responde JSON: { ok, resultado: entrada|repetido|cancelado|invalido, mensaje, folio?, nombre?, tipo?, rol?, institucion?, usado_en? }
 * La primera lectura de un boleto vigente lo marca como usado; las siguientes responden "repetido".
 */
require_once __DIR__ . '/../../includes/app.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder_checkin(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responder_checkin(405, ['ok' => false, 'error' => 'Método no permitido.']);
}

$usuario = usuario_actual();
if ($usuario === null) {
    responder_checkin(401, ['ok' => false, 'error' => 'Inicia sesión como staff para validar boletos.']);
}
if (!tiene_rol('staff', 'admin')) {
    responder_checkin(403, ['ok' => false, 'error' => 'Solo el staff o el administrador pueden validar boletos.']);
}
if (!csrf_valido()) {
    responder_checkin(400, ['ok' => false, 'error' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.']);
}
if (!boletos_tablas_existen()) {
    error_log('UDG NEXUS: faltan las tablas de boletos (importa db/migraciones/005_boletos.sql).');
    responder_checkin(500, ['ok' => false, 'error' => 'Los boletos no están disponibles (falta importar la migración 005).']);
}

$leido = (string) ($_POST['token'] ?? '');
if ($leido === '' || mb_strlen($leido) > 200) {
    responder_checkin(400, ['ok' => false, 'error' => 'No se recibió ningún código.']);
}
// Quita espacios internos (el código de respaldo se muestra en grupos de 4).
$leido = preg_replace('/\s+/', '', $leido);

$res = boleto_validar_entrada($leido, (int) $usuario['id']);
responder_checkin(200, ['ok' => true] + $res);
