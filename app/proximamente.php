<?php
require_once __DIR__ . '/../includes/app.php';

$modulos = [
    'agenda' => ['Mi agenda', 'Aquí armarás tu agenda personal y activarás alertas para no perderte conferencias, retos y citas.'],
    'mapa'   => ['Mapa del recinto', 'Plano de la Plaza Bicentenario y el CCU con la ubicación de stands, zonas y la Demo Arena.'],
    'gafete' => ['Gafete digital', 'Tu gafete con código QR para ingresar al evento y registrar tu asistencia.'],
];
$m = $_GET['m'] ?? '';
if (!isset($modulos[$m])) {
    $m = 'agenda';
}
[$nombre, $texto] = $modulos[$m];

$titulo = $nombre;
$activa = $m;
require __DIR__ . '/../includes/app_header.php';
?>

<div class="proximamente">
  <div class="circulo-grande"><?= icono($m) ?></div>
  <h1><?= h($nombre) ?></h1>
  <p class="suave"><?= h($texto) ?></p>
  <p><strong>Disponible muy pronto.</strong></p>
  <a class="btn-app" href="inicio.php">Volver al inicio</a>
</div>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
