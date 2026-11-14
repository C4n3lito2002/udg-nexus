<?php
/**
 * Procesa el formulario de contacto/patrocinio (contacto.php) e
 * inserta el registro en la tabla `contactos_patrocinio`.
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contacto.php');
    exit;
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

$valido = $nombre !== '' &&
          filter_var($correo, FILTER_VALIDATE_EMAIL) &&
          $mensaje !== '';

if (!$valido) {
    header('Location: contacto.php?estado=error');
    exit;
}

$conexion = obtenerConexion();

$stmt = $conexion->prepare(
    'INSERT INTO contactos_patrocinio (nombre_completo, correo, empresa, asunto, mensaje)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->bind_param('sssss', $nombre, $correo, $empresa, $asunto, $mensaje);

$exito = $stmt->execute();
$stmt->close();
$conexion->close();

header('Location: contacto.php?estado=' . ($exito ? 'ok' : 'error'));
exit;
