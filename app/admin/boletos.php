<?php
/**
 * Panel de boletos y accesos (solo administrador): totales por tipo, últimas lecturas
 * del escáner y reinicio de la demostración (los boletos usados vuelven a "vigente").
 */
require_once __DIR__ . '/../../includes/app.php';
requiere_rol('admin');

$tablasOk = boletos_tablas_existen();
$mensaje = '';

if ($tablasOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    if (($_POST['accion'] ?? '') === 'reiniciar') {
        $a = ejecutar("UPDATE boletos SET estatus = 'vigente', usado_en = NULL WHERE estatus = 'usado'");
        $b = ejecutar('DELETE FROM accesos');
        $mensaje = ($a && $b)
            ? 'Listo: los boletos usados volvieron a vigentes y la bitácora de lecturas quedó vacía.'
            : 'No se pudo reiniciar. Revisa el log de PHP.';
    }
}

$resumen  = $tablasOk ? boletos_resumen_por_tipo() : [];
$accesos  = $tablasOk ? accesos_recientes(15) : [];
$etqRes   = ['entrada' => 'Entrada', 'repetido' => 'Ya usado', 'cancelado' => 'Cancelado', 'invalido' => 'Código no válido'];

$titulo = 'Boletos y accesos';
$activa = '';
$volver = 'index.php';
require __DIR__ . '/../../includes/app_header.php';
?>

<h1>Boletos y accesos</h1>

<?php if (!$tablasOk): ?>
  <div class="alerta error" role="alert">Faltan las tablas de boletos (importa <code>db/migraciones/005_boletos.sql</code>).</div>
<?php else: ?>

<?php if ($mensaje): ?><div class="alerta ok" role="status"><?= h($mensaje) ?></div><?php endif; ?>

<section class="bloque">
  <h2>Por tipo de boleto</h2>
  <div class="tabla-scroll">
    <table class="tabla-simple">
      <thead><tr><th scope="col">Tipo</th><th scope="col">Vigentes</th><th scope="col">Usados</th><th scope="col">Cancel.</th><th scope="col">Cupo</th></tr></thead>
      <tbody>
      <?php foreach ($resumen as $r): ?>
        <tr>
          <th scope="row"><?= h($r['nombre']) ?><small><?= h(precio_texto($r['precio'])) ?></small></th>
          <td><?= (int) $r['vigentes'] ?></td><td><?= (int) $r['usados'] ?></td><td><?= (int) $r['cancelados'] ?></td><td><?= (int) $r['cupo'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="bloque">
  <h2>Últimas lecturas del escáner</h2>
  <?php if (!$accesos): ?>
    <p class="vacio-lista">Todavía no hay lecturas.</p>
  <?php else: ?>
    <ol class="lecturas">
      <?php foreach ($accesos as $a): ?>
        <li class="<?= h($a['resultado']) ?>">
          <strong><?= h($etqRes[$a['resultado']] ?? $a['resultado']) ?></strong>
          <span><?= $a['persona'] ? h($a['persona']) . ' · ' . h(boleto_folio((int) $a['boleto_id'])) : 'Sin boleto' ?></span>
          <small><?= h(fecha_hora_texto($a['escaneado_en'])) ?><?= $a['staff'] ? ' · ' . h($a['staff']) : '' ?></small>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</section>

<section class="bloque">
  <h2>Reiniciar la demostración</h2>
  <p class="suave pequeno">Los boletos usados vuelven a «vigente» y se borra la bitácora de lecturas, para repetir la demo del escáner. No cambia los boletos cancelados ni los de las compras.</p>
  <details class="demo-cuentas">
    <summary>Reiniciar lecturas…</summary>
    <form method="post" action="boletos.php">
      <?= csrf_campo() ?>
      <input type="hidden" name="accion" value="reiniciar">
      <p class="pequeno">Se borrarán todas las lecturas registradas y los boletos usados quedarán vigentes. Esta acción no se puede deshacer.</p>
      <button class="btn-app sec ancho" type="submit">Sí, reiniciar lecturas</button>
    </form>
  </details>
</section>

<a class="btn-app ancho" href="escaner.php" style="margin-top:1rem">Abrir el escáner</a>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/app_footer.php'; ?>
