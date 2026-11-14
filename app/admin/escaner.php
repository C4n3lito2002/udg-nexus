<?php
/**
 * Escáner de acceso para el staff: lee el QR del gafete con la cámara (o se teclea el
 * código de respaldo) y valida el boleto una sola vez. La lógica vive en api/checkin.php.
 */
require_once __DIR__ . '/../../includes/app.php';
requiere_rol('staff', 'admin');

$titulo  = 'Escáner de acceso';
$activa  = '';
$volver  = '../perfil.php';
$scripts = ['assets/js/escaner.js'];
require __DIR__ . '/../../includes/app_header.php';
?>

<h1>Escáner de acceso</h1>
<p class="suave">Apunta la cámara al QR del gafete o escribe el código de respaldo. Cada boleto entra una sola vez.</p>

<?php if (!boletos_tablas_existen()): ?>
  <div class="alerta error" role="alert">Faltan las tablas de boletos (importa <code>db/migraciones/005_boletos.sql</code>).</div>
<?php else: ?>

<section class="escaner" data-escaner data-jsqr="<?= h(prefijo_raiz()) ?>assets/js/vendor/jsQR.js">
  <div class="visor" data-visor hidden>
    <video data-video playsinline muted></video>
    <div class="mira" aria-hidden="true"></div>
  </div>
  <p class="suave pequeno" data-camara-estado role="status"></p>
  <button class="btn-app ancho" type="button" data-camara><?= icono('gafete') ?> <span>Activar cámara</span></button>

  <form class="form-app" data-manual novalidate>
    <div class="campo">
      <label for="codigo">Código del boleto <span class="suave">(respaldo)</span></label>
      <input type="text" id="codigo" name="codigo" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" maxlength="80" placeholder="a4bf 9a42 3c9a …">
    </div>
    <button class="btn-app sec ancho" type="submit">Validar código</button>
  </form>

  <div class="resultado" data-resultado hidden role="alert" aria-live="assertive"></div>

  <section class="bloque">
    <h2>Lecturas de esta sesión</h2>
    <ol class="lecturas" data-lecturas>
      <li class="vacio" data-vacio>Aún no hay lecturas.</li>
    </ol>
  </section>
  <noscript><div class="alerta error">El escáner necesita JavaScript.</div></noscript>
</section>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/app_footer.php'; ?>
