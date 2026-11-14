<?php
require_once __DIR__ . '/../includes/app.php';

$titulo = 'Buscar';
$activa = 'inicio';
$volver = 'inicio.php';
$q = trim((string) ($_GET['q'] ?? ''));
$r = buscar_todo($q, 20);
$total = count($r['proyectos']) + count($r['areas']) + count($r['empresas']) + count($r['investigadores']);

require __DIR__ . '/../includes/app_header.php';
?>

<h1>Buscar</h1>
<form class="filtros" action="buscar.php" method="get" role="search">
  <label for="q-buscar" class="skip-link" style="position:absolute">Buscar proyectos, áreas, empresas o investigadores</label>
  <input type="search" id="q-buscar" name="q" value="<?= h($q) ?>" placeholder="Proyectos, áreas, empresas, investigadores…" minlength="2" autofocus>
  <div class="acciones"><button class="btn-app" type="submit">Buscar</button></div>
</form>

<?php if ($q === ''): ?>
  <p class="vacio-lista">Escribe al menos 2 letras para buscar.</p>
<?php elseif (mb_strlen($q) < 2): ?>
  <p class="vacio-lista">Escribe al menos 2 letras para buscar.</p>
<?php elseif ($total === 0): ?>
  <p class="vacio-lista">Sin resultados para «<?= h($q) ?>». Prueba con otra palabra.</p>
<?php else: ?>
  <p class="cuenta" aria-live="polite"><?= $total ?> resultados para «<?= h($q) ?>»</p>

  <?php if ($r['proyectos']): ?>
    <section class="bloque"><h2>Proyectos</h2>
      <div class="lista"><?php foreach ($r['proyectos'] as $p): ?><?= tarjeta_proyecto($p) ?><?php endforeach; ?></div>
    </section>
  <?php endif; ?>

  <?php if ($r['areas']): ?>
    <section class="bloque"><h2>Áreas tecnológicas</h2>
      <div class="lista">
        <?php foreach ($r['areas'] as $a): ?>
          <a class="tarjeta" href="catalogo.php?area=<?= h($a['slug']) ?>"><h3><?= h($a['nombre']) ?></h3><p><?= h($a['descripcion']) ?></p></a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($r['empresas']): ?>
    <section class="bloque"><h2>Empresas e inversionistas</h2>
      <div class="lista"><?php foreach ($r['empresas'] as $e): ?><?= tarjeta_empresa($e) ?><?php endforeach; ?></div>
    </section>
  <?php endif; ?>

  <?php if ($r['investigadores']): ?>
    <section class="bloque"><h2>Investigadores</h2>
      <ul class="equipo">
        <?php foreach ($r['investigadores'] as $i):
            $href = $i['proyecto_id'] ? 'proyecto.php?id=' . (int) $i['proyecto_id'] : '';
        ?>
          <li>
            <span class="avatar" aria-hidden="true"><?= h(iniciales($i['nombre'])) ?></span>
            <div>
              <div class="nom"><?= $href ? '<a href="' . h($href) . '">' . h($i['nombre']) . '</a>' : h($i['nombre']) ?></div>
              <div class="det"><?= h($i['centro']) ?><?= $i['especialidad'] ? ' · ' . h($i['especialidad']) : '' ?></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
