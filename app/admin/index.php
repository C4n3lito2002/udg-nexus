<?php
require_once __DIR__ . '/../../includes/app.php';
requiere_rol('admin');

$ahora = ahora_evento();
$modos = ['real' => 'Hora real', 'fijo' => 'Hora fija', 'avance' => 'Avanza sola'];

$titulo = 'Administración';
$activa = '';
$volver = '../perfil.php';
require __DIR__ . '/../../includes/app_header.php';
?>

<h1>Administración</h1>
<p class="suave">Herramientas para preparar y controlar la demostración.</p>

<div class="lista">
  <a class="tarjeta" href="reloj.php">
    <div class="chips"><span class="chip oscuro"><?= h($modos[reloj_modo()]) ?></span></div>
    <h3>Reloj del evento</h3>
    <p>Hora que usa toda la app: <strong><?= h(fecha_larga($ahora)) ?>, <?= h(hora_corta($ahora)) ?></strong>. Cámbiala para mostrar cualquier momento del evento.</p>
  </a>
  <a class="tarjeta" href="avisos.php">
    <div class="chips"><span class="chip oscuro">Avisos</span></div>
    <h3>Avisos y notificaciones</h3>
    <p>Publica avisos para todos o para un rol y mira cuántas personas los leyeron.</p>
  </a>
  <a class="tarjeta" href="escaner.php">
    <div class="chips"><span class="chip oscuro">Staff</span></div>
    <h3>Escáner de acceso</h3>
    <p>Validar boletos con código QR en la entrada: cada uno entra una sola vez.</p>
  </a>
  <a class="tarjeta" href="boletos.php">
    <div class="chips"><span class="chip oscuro">Boletos</span></div>
    <h3>Boletos y accesos</h3>
    <p>Boletos emitidos, usados y últimas lecturas del escáner. Reinicia la demo.</p>
  </a>
</div>

<?php require __DIR__ . '/../../includes/app_footer.php'; ?>
