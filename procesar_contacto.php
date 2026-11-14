<?php
/**
 * Procesa el formulario de contacto/patrocinio (contacto.php) e
 * inserta el registro en la tabla `contactos_patrocinio`.
 * Protegido con token de un solo uso (CSRF + doble envío) y registro de errores:
 * ver includes/forms.php.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/forms.php';

const FORM = 'contacto';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    form_redirigir('contacto.php');
}

if (($resultado = form_abrir(FORM)) !== null) {
    form_redirigir('contacto.php?estado=' . $resultado);
}

$nombre  = trim($_POST['nombre_completo'] ?? '');
$correo  = trim($_POST['correo'] ?? '');
$empresa = trim($_POST['empresa'] ?? '');
$asunto  = trim($_POST['asunto'] ?? 'Otro');
$mensaje = trim($_POST['mensaje'] ?? '');

$asuntosValidos = ['Interés en patrocinio', 'Solicitud de Dossier Comercial', 'Prensa y medios', 'Otro'];
if (!in_array($asunto, $asuntosValidos, true)) {
    $asunto = 'Otro';
}

$fallos = [];
if ($nombre === '') {
    $fallos[] = 'nombre_completo';
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $fallos[] = 'correo';
}
if ($mensaje === '') {
    $fallos[] = 'mensaje';
}

if ($fallos) {
    form_log(FORM, 'validacion', 'campos invalidos: ' . implode(', ', $fallos));
    form_cerrar(false);
    form_redirigir('contacto.php?estado=error');
}

$conexion = obtenerConexion();

$stmt = $conexion->prepare(
    'INSERT INTO contactos_patrocinio (nombre_completo, correo, empresa, asunto, mensaje)
     VALUES (?, ?, ?, ?, ?)'
);
if (!$stmt) {
    form_log(FORM, 'bd_error', 'prepare fallo (' . $conexion->errno . '): ' . $conexion->error);
    form_cerrar(false);
    form_redirigir('contacto.php?estado=error');
}
$stmt->bind_param('sssss', $nombre, $correo, $empresa, $asunto, $mensaje);

$exito = $stmt->execute();
if (!$exito) {
    form_log(FORM, 'bd_error', 'insert fallo (' . $stmt->errno . '): ' . $stmt->error);
}
$stmt->close();
$conexion->close();

form_cerrar($exito);
form_redirigir('contacto.php?estado=' . ($exito ? 'ok' : 'error'));
