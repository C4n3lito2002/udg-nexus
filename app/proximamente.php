<?php
require_once __DIR__ . '/../includes/app.php';

$modulos = [
    'mapa'   => ['Mapa del recinto', 'Plano de la Plaza Bicentenario y el CCU con la ubicación de stands, zonas y la Demo Arena.'],
];
$m = $_GET['m'] ?? '';
if (!isset($modulos[$m])) {
    $m = 'mapa';
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
