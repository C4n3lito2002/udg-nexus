<?php
/**
 * Procesa la solicitud de cita B2B (registro.php#citas) e inserta
 * el registro en la tabla `citas_b2b`.
 * Protegido con token de un solo uso (CSRF + doble envío) y registro de errores:
 * ver includes/forms.php.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/forms.php';

const FORM = 'cita_b2b';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    form_redirigir('registro.php');
}

if (($resultado = form_abrir(FORM)) !== null) {
    form_redirigir('registro.php?cita=' . $resultado . '#citas');
}

$nombre      = trim($_POST['nombre_completo'] ?? '');
$correo      = trim($_POST['correo'] ?? '');
$institucion = trim($_POST['institucion_empresa'] ?? '');
$tipoAsist   = trim($_POST['tipo_asistente'] ?? '');
$tema        = trim($_POST['tema_interes'] ?? '');
$horario     = trim($_POST['horario_preferido'] ?? '');
$nda         = trim($_POST['requiere_nda'] ?? 'No');
$mensaje     = trim($_POST['mensaje'] ?? '');

$tiposValidos = ['Comprador/Inversionista', 'Expositor/Prototipador'];
$ndaValido    = in_array($nda, ['Sí', 'No'], true) ? $nda : 'No';

$fallos = [];
if ($nombre === '') {
    $fallos[] = 'nombre_completo';
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $fallos[] = 'correo';
}
if ($institucion === '') {
    $fallos[] = 'institucion_empresa';
}
if (!in_array($tipoAsist, $tiposValidos, true)) {
    $fallos[] = 'tipo_asistente';
}

if ($fallos) {
    form_log(FORM, 'validacion', 'campos invalidos: ' . implode(', ', $fallos));
    form_cerrar(false);
    form_redirigir('registro.php?cita=error#citas');
}

$conexion = obtenerConexion();

$stmt = $conexion->prepare(
    'INSERT INTO citas_b2b
        (nombre_completo, correo, institucion_empresa, tipo_asistente, tema_interes, horario_preferido, requiere_nda, mensaje)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    form_log(FORM, 'bd_error', 'prepare fallo (' . $conexion->errno . '): ' . $conexion->error);
    form_cerrar(false);
    form_redirigir('registro.php?cita=error#citas');
}
$stmt->bind_param('ssssssss', $nombre, $correo, $institucion, $tipoAsist, $tema, $horario, $ndaValido, $mensaje);

$exito = $stmt->execute();
if (!$exito) {
    form_log(FORM, 'bd_error', 'insert fallo (' . $stmt->errno . '): ' . $stmt->error);
}
$stmt->close();
$conexion->close();

form_cerrar($exito);
form_redirigir('registro.php?cita=' . ($exito ? 'ok' : 'error') . '#citas');
