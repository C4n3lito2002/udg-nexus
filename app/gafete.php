<?php
/**
 * Gafete digital: boleto con código QR.
 * El QR contiene SOLO el token del boleto y se dibuja en el navegador con
 * assets/js/vendor/qrcode.js (copia local, funciona sin internet).
 */
require_once __DIR__ . '/../includes/app.php';
requiere_login();

$u        = usuario_actual();
$tablasOk = boletos_tablas_existen();
$boleto   = $tablasOk ? boleto_de_usuario((int) $u['id']) : null;
$activo   = $boleto !== null && $boleto['estatus'] !== 'cancelado';
$nuevo    = $activo && ($_GET['nuevo'] ?? '') === '1';

$titulo  = 'Mi gafete';
$activa  = 'gafete';
$scripts = $activo ? ['assets/js/vendor/qrcode.js', 'assets/js/gafete.js'] : [];
require __DIR__ . '/../includes/app_header.php';
?>

<h1>Mi gafete</h1>

<?php if (!$tablasOk): ?>

  <div class="alerta error" role="alert">Los boletos todavía no están disponibles (falta importar <code>db/migraciones/005_boletos.sql</code>).</div>

<?php elseif ($activo): ?>

  <?php if ($nuevo): ?>
    <div class="alerta ok" role="status">Tu boleto está listo. Guarda una captura de este gafete o vuelve a abrirlo desde la pestaña Gafete.</div>
  <?php endif; ?>

  <?php $est = $boleto['estatus']; ?>
  <article class="gafete <?= h($est) ?>" aria-label="Gafete de <?= h($u['nombre']) ?>">
    <div class="gafete-logo">
      <img src="../assets/img/logo-udg-nexus.png" width="505" height="283" alt="UDG NEXUS">
    </div>

    <div class="gafete-persona">
      <span class="tipo"><?= h($boleto['tipo']) ?></span>
      <h2><?= h($u['nombre']) ?></h2>
      <?php if ($u['institucion'] !== ''): ?><p><?= h($u['institucion']) ?></p><?php endif; ?>
      <p><?= h(ROLES[$u['rol']] ?? $u['rol']) ?></p>
    </div>

    <div class="gafete-qr-zona">
      <div class="gafete-qr" data-qr="<?= h($boleto['token_qr']) ?>" role="img" aria-label="Código QR de tu boleto. Muéstralo en la entrada.">
        <noscript><p class="suave pequeno">Activa JavaScript para ver el código QR.</p></noscript>
        <?php if ($est === 'usado'): ?><span class="gafete-sello" aria-hidden="true">Usado</span><?php endif; ?>
      </div>
      <p class="suave pequeno centrado">
        <?= $est === 'usado'
            ? 'Este boleto ya se usó para entrar el ' . h(fecha_hora_texto($boleto['usado_en'])) . '.'
            : 'Muestra este código en la entrada. Solo sirve para entrar una vez.' ?>
      </p>
    </div>

    <dl class="gafete-pie datos">
      <div><dt>Folio</dt><dd><?= h(boleto_folio((int) $boleto['id'])) ?></dd></div>
      <div><dt>Estado</dt><dd><?= chip_estatus_boleto($est) ?></dd></div>
      <div><dt>Fechas</dt><dd><?= h(evento_rango_texto()) ?></dd></div>
      <div><dt>Sede</dt><dd>Plaza Bicentenario, CCU</dd></div>
    </dl>
  </article>

  <?php if ($est === 'vigente'): ?>
    <button class="btn-app ancho" type="button" data-qr-ampliar style="margin-top:1rem">Ampliar código para escanear</button>
    <details class="demo-cuentas">
      <summary>Código de respaldo</summary>
      <p class="pequeno suave">Si la cámara del staff no lee el QR, pueden teclear este código. No lo compartas.</p>
      <p><strong class="codigo-respaldo"><?= h(boleto_token_agrupado($boleto['token_qr'])) ?></strong></p>
    </details>
  <?php endif; ?>

  <div class="qr-pantalla" data-qr-pantalla hidden role="dialog" aria-modal="true" aria-label="Código QR ampliado">
    <div class="gafete-qr" data-qr-grande="<?= h($boleto['token_qr']) ?>" role="img" aria-label="Código QR ampliado de tu boleto"></div>
    <button class="btn-app" type="button" data-qr-cerrar>Cerrar</button>
  </div>

<?php elseif ($boleto !== null): ?>

  <div class="alerta error" role="alert">Tu boleto <?= h(boleto_folio((int) $boleto['id'])) ?> (<?= h($boleto['tipo']) ?>) fue cancelado y ya no sirve para entrar.</div>
  <a class="btn-app ancho" href="boletos.php">Obtener otro boleto</a>

<?php elseif (tiene_rol('staff', 'admin')): ?>

  <div class="proximamente">
    <div class="circulo-grande"><?= icono('gafete') ?></div>
    <h2>El staff no necesita boleto</h2>
    <p class="suave">Tu cuenta (<?= h(ROLES[$u['rol']]) ?>) valida a las demás personas en la entrada.</p>
    <a class="btn-app" href="admin/escaner.php">Abrir el escáner de acceso</a>
  </div>

<?php else: ?>

  <div class="proximamente">
    <div class="circulo-grande"><?= icono('gafete') ?></div>
    <h2>Aún no tienes boleto</h2>
    <p class="suave">Elige tu boleto para recibir tu gafete con código QR. Los boletos con costo usan un pago simulado.</p>
    <a class="btn-app" href="boletos.php">Elegir mi boleto</a>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
