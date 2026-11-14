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
  <div class="tarjeta inactiva" aria-disabled="true">
    <div class="chips"><span class="chip">Próximamente</span></div>
    <h3>Avisos</h3>
    <p>Publicar avisos para todos o para un rol.</p>
  </div>
  <div class="tarjeta inactiva" aria-disabled="true">
    <div class="chips"><span class="chip">Próximamente</span></div>
    <h3>Escáner de acceso</h3>
    <p>Validar boletos con código QR en la entrada.</p>
  </div>
</div>

<?php require __DIR__ . '/../../includes/app_footer.php'; ?>
