<?php
/**
 * Procesa el formulario de acreditación (registro.php) e inserta
 * el registro en la tabla `acreditaciones`.
 * Protegido con token de un solo uso (CSRF + doble envío) y registro de errores:
 * ver includes/forms.php.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/forms.php';

const FORM = 'acreditacion';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    form_redirigir('registro.php');
}

if (($resultado = form_abrir(FORM)) !== null) {
    form_redirigir('registro.php?acred=' . $resultado . '#acreditacion');
}

$nombre      = trim($_POST['nombre_completo'] ?? '');
$correo      = trim($_POST['correo'] ?? '');
$telefono    = trim($_POST['telefono'] ?? '');
$institucion = trim($_POST['institucion_empresa'] ?? '');
$tipoGafete  = trim($_POST['tipo_gafete'] ?? '');
$eje         = trim($_POST['eje_tematico'] ?? '');
$comentarios = trim($_POST['comentarios'] ?? '');

$gafetesValidos = [
    'Inversionista/Comprador', 'Expositor/Investigador', 'Prensa',
    'VIP', 'Staff', 'Estudiante/Público general',
];

$fallos = [];
if ($nombre === '') {
    $fallos[] = 'nombre_completo';
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $fallos[] = 'correo';
}
if (!in_array($tipoGafete, $gafetesValidos, true)) {
    $fallos[] = 'tipo_gafete';
}

if ($fallos) {
    form_log(FORM, 'validacion', 'campos invalidos: ' . implode(', ', $fallos));
    form_cerrar(false);
    form_redirigir('registro.php?acred=error#acreditacion');
}

$conexion = obtenerConexion();

$stmt = $conexion->prepare(
    'INSERT INTO acreditaciones
        (nombre_completo, correo, telefono, institucion_empresa, tipo_gafete, eje_tematico, comentarios)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    form_log(FORM, 'bd_error', 'prepare fallo (' . $conexion->errno . '): ' . $conexion->error);
    form_cerrar(false);
    form_redirigir('registro.php?acred=error#acreditacion');
}
$stmt->bind_param('sssssss', $nombre, $correo, $telefono, $institucion, $tipoGafete, $eje, $comentarios);

$exito = $stmt->execute();
if (!$exito) {
    form_log(FORM, 'bd_error', 'insert fallo (' . $stmt->errno . '): ' . $stmt->error);
}
$stmt->close();
$conexion->close();

form_cerrar($exito);
form_redirigir('registro.php?acred=' . ($exito ? 'ok' : 'error') . '#acreditacion');
