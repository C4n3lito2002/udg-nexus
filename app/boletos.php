<?php
/**
 * Boletos del evento.
 *   boletos.php               tipos de boleto (público)
 *   boletos.php?comprar=ID    confirmación y compra SIMULADA (requiere sesión)
 * No se pide ni se guarda ningún dato de tarjeta: el "pago" solo deja una referencia ficticia.
 */
require_once __DIR__ . '/../includes/app.php';

$tablasOk = boletos_tablas_existen();
$usuario  = usuario_actual();
$tipos    = $tablasOk ? tipos_boleto_todos() : [];
$propio   = ($usuario && $tablasOk) ? boleto_de_usuario((int) $usuario['id']) : null;
$tieneActivo = $propio !== null && $propio['estatus'] !== 'cancelado';
$sugerido = $usuario ? tipo_boleto_sugerido($tipos, $usuario['rol']) : null;

$comprarId = (int) ($_GET['comprar'] ?? ($_POST['tipo'] ?? 0));
$elegido = null;
if ($comprarId > 0) {
    requiere_login();
    foreach ($tipos as $t) {
        if ($t['id'] === $comprarId) {
            $elegido = $t;
        }
    }
}

$error = '';
if ($elegido !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $res = boleto_emitir((int) $usuario['id'], $elegido['id']);
    if ($res['ok']) {
        header('Location: gafete.php?nuevo=1');
        exit;
    }
    if ($res['codigo'] === 'ya_tiene') {          // doble clic o reenvío: ya quedó emitido
        header('Location: gafete.php');
        exit;
    }
    $error = $res['error'];
    $tipos = tipos_boleto_todos();                // el cupo pudo cambiar
    foreach ($tipos as $t) {
        if ($t['id'] === $elegido['id']) {
            $elegido = $t;
        }
    }
}

$titulo = $elegido ? 'Confirmar boleto' : 'Boletos';
$activa = 'gafete';
$volver = $elegido ? 'boletos.php' : null;
$scripts = [];
require __DIR__ . '/../includes/app_header.php';
?>

<?php if (!$tablasOk): ?>

  <h1>Boletos</h1>
  <div class="alerta error" role="alert">Los boletos todavía no están disponibles (falta importar <code>db/migraciones/005_boletos.sql</code>).</div>

<?php elseif ($elegido !== null): ?>

  <h1>Confirmar boleto</h1>

  <?php if ($tieneActivo): ?>
    <div class="alerta ok" role="status">Ya tienes el boleto «<?= h($propio['tipo']) ?>» (<?= h(boleto_folio((int) $propio['id'])) ?>). Solo se permite uno por persona.</div>
    <a class="btn-app ancho" href="gafete.php">Ver mi gafete</a>
  <?php else: ?>
    <?php if ($error): ?><div class="alerta error" role="alert"><?= h($error) ?></div><?php endif; ?>

    <section class="boleto-tipo sugerido">
      <div class="fila-precio">
        <h2><?= h($elegido['nombre']) ?></h2>
        <span class="precio"><?= h(precio_texto($elegido['precio'])) ?></span>
      </div>
      <p class="suave"><?= h($elegido['descripcion']) ?></p>
      <ul class="beneficios">
        <?php foreach ($elegido['beneficios'] as $b): ?><li><?= h($b) ?></li><?php endforeach; ?>
      </ul>
    </section>

    <dl class="datos" style="margin-top:1rem">
      <div><dt>A nombre de</dt><dd><?= h($usuario['nombre']) ?></dd></div>
      <div><dt>Evento</dt><dd><?= h(evento_rango_texto()) ?></dd></div>
      <div><dt>Total</dt><dd><?= h(precio_texto($elegido['precio'])) ?></dd></div>
    </dl>

    <?php if ((float) $elegido['precio'] > 0): ?>
      <p class="aviso-demo" style="margin-top:1rem"><strong>Pago simulado.</strong> No se cobra nada y no se pide ningún dato de tarjeta: al confirmar, el boleto se emite con una referencia ficticia.</p>
    <?php endif; ?>

    <?php if ($elegido['disponibles'] < 1): ?>
      <div class="alerta error" role="alert">Este boleto se agotó. <a href="boletos.php">Elige otro tipo</a>.</div>
    <?php else: ?>
      <form class="form-app" method="post" action="boletos.php?comprar=<?= (int) $elegido['id'] ?>">
        <?= csrf_campo() ?>
        <input type="hidden" name="tipo" value="<?= (int) $elegido['id'] ?>">
        <button class="btn-app ancho" type="submit">
          <?= (float) $elegido['precio'] > 0 ? 'Pagar ' . h(precio_texto($elegido['precio'])) . ' (simulado)' : 'Obtener mi boleto' ?>
        </button>
      </form>
    <?php endif; ?>
  <?php endif; ?>

<?php else: ?>

  <h1>Boletos</h1>
  <p class="suave">Elige tu boleto para recibir tu gafete con código QR. <?= h(evento_rango_texto()) ?> · Plaza Bicentenario, CCU.</p>

  <?php if ($tieneActivo): ?>
    <div class="alerta ok" role="status">Ya tienes el boleto «<?= h($propio['tipo']) ?>» (<?= h(boleto_folio((int) $propio['id'])) ?>), <?= $propio['estatus'] === 'usado' ? 'que ya se usó' : 'vigente' ?>.
      <a href="gafete.php">Ver mi gafete</a></div>
  <?php elseif (!$usuario): ?>
    <div class="alerta ok" role="status">El boleto queda ligado a tu cuenta. <a href="registro_app.php">Crea tu cuenta</a> o <a href="login.php?volver=<?= h(rawurlencode($_SERVER['REQUEST_URI'] ?? '')) ?>">inicia sesión</a> para elegirlo.</div>
  <?php endif; ?>

  <?php if (!$tipos): ?>
    <p class="vacio-lista">Todavía no hay boletos publicados.</p>
  <?php else: ?>
    <div class="lista">
      <?php foreach ($tipos as $t):
          $recomendado = $sugerido !== null && $sugerido['id'] === $t['id'];
      ?>
        <article class="boleto-tipo <?= $recomendado ? 'sugerido' : '' ?>">
          <?php if ($recomendado): ?><div class="chips"><span class="chip inv">Recomendado para tu rol</span></div><?php endif; ?>
          <div class="fila-precio">
            <h2><?= h($t['nombre']) ?></h2>
            <span class="precio"><?= h(precio_texto($t['precio'])) ?></span>
          </div>
          <p class="suave"><?= h($t['descripcion']) ?></p>
          <ul class="beneficios">
            <?php foreach ($t['beneficios'] as $b): ?><li><?= h($b) ?></li><?php endforeach; ?>
          </ul>
          <p class="cupo"><?= $t['disponibles'] > 0 ? 'Quedan ' . (int) $t['disponibles'] . ' de ' . (int) $t['cupo'] : 'Agotado' ?></p>
          <?php if ($tieneActivo): ?>
            <!-- ya tiene boleto: no se ofrece otro -->
          <?php elseif ($t['disponibles'] < 1): ?>
            <span class="btn-app sec ancho" aria-disabled="true">Agotado</span>
          <?php elseif ($usuario): ?>
            <a class="btn-app ancho" href="boletos.php?comprar=<?= (int) $t['id'] ?>"><?= (float) $t['precio'] > 0 ? 'Comprar (pago simulado)' : 'Obtener gratis' ?></a>
          <?php else: ?>
            <a class="btn-app ancho" href="login.php?volver=<?= h(rawurlencode(base_app() . '/boletos.php?comprar=' . (int) $t['id'])) ?>">Entrar para elegirlo</a>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p class="aviso-demo">Versión de demostración: los precios son ficticios y el pago es simulado. No se cobra nada.</p>

<?php endif; ?>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
