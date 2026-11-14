<?php
/**
 * Procesa el formulario de acreditación (registro.php) e inserta
 * el registro en la tabla `acreditaciones`.
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit;
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

$valido = $nombre !== '' &&
          filter_var($correo, FILTER_VALIDATE_EMAIL) &&
          in_array($tipoGafete, $gafetesValidos, true);

if (!$valido) {
    header('Location: registro.php?acred=error#acreditacion');
    exit;
}

$conexion = obtenerConexion();

$stmt = $conexion->prepare(
    'INSERT INTO acreditaciones
        (nombre_completo, correo, telefono, institucion_empresa, tipo_gafete, eje_tematico, comentarios)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('sssssss', $nombre, $correo, $telefono, $institucion, $tipoGafete, $eje, $comentarios);

$exito = $stmt->execute();
$stmt->close();
$conexion->close();

header('Location: registro.php?acred=' . ($exito ? 'ok' : 'error') . '#acreditacion');
exit;
