<?php
/**
 * Procesa la solicitud de cita B2B (registro.php#citas) e inserta
 * el registro en la tabla `citas_b2b`.
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit;
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

$valido = $nombre !== '' &&
          filter_var($correo, FILTER_VALIDATE_EMAIL) &&
          $institucion !== '' &&
          in_array($tipoAsist, $tiposValidos, true);

if (!$valido) {
    header('Location: registro.php?cita=error#citas');
    exit;
}

$conexion = obtenerConexion();

$stmt = $conexion->prepare(
    'INSERT INTO citas_b2b
        (nombre_completo, correo, institucion_empresa, tipo_asistente, tema_interes, horario_preferido, requiere_nda, mensaje)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('ssssssss', $nombre, $correo, $institucion, $tipoAsist, $tema, $horario, $ndaValido, $mensaje);

$exito = $stmt->execute();
$stmt->close();
$conexion->close();

header('Location: registro.php?cita=' . ($exito ? 'ok' : 'error') . '#citas');
exit;
