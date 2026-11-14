<?php
require_once __DIR__ . '/../includes/app.php';

$activa = 'catalogo';
$id = (int) ($_GET['id'] ?? 0);

$p = consulta_uno(
    'SELECT p.*, a.nombre AS area, a.slug AS area_slug, z.nombre AS zona
     FROM proyectos p
     JOIN areas_tecnologicas a ON a.id = p.area_id
     LEFT JOIN zonas z ON z.id = p.zona_id
     WHERE p.id = ?',
    'i',
    [$id]
);

if (!$p) {
    http_response_code(404);
    $titulo = 'Proyecto no encontrado';
    $volver = 'catalogo.php';
    require __DIR__ . '/../includes/app_header.php';
    echo '<div class="proximamente"><h1>No encontramos ese proyecto</h1><p class="suave">Puede que el enlace sea incorrecto.</p><a class="btn-app" href="catalogo.php">Ir al catálogo</a></div>';
    require __DIR__ . '/../includes/app_footer.php';
    exit;
}

$equipo = consulta(
    "SELECT i.nombre, i.centro, i.especialidad, pi.rol
     FROM proyecto_investigador pi JOIN investigadores i ON i.id = pi.investigador_id
     WHERE pi.proyecto_id = ?
     ORDER BY FIELD(pi.rol,'Líder','Investigador','Asesor','Estudiante'), i.nombre",
    'i',
    [$id]
);

$relacionados = array_filter(
    proyectos_filtrados(['area' => $p['area_slug'], 'limite' => 4]),
    fn($r) => (int) $r['id'] !== $id
);
$relacionados = array_slice($relacionados, 0, 3);

$titulo = $p['nombre'];
$volver = 'catalogo.php';
require __DIR__ . '/../includes/app_header.php';
?>

<article>
  <header class="ficha-cab">
    <div class="chips">
      <a class="chip" href="catalogo.php?area=<?= h($p['area_slug']) ?>" style="text-decoration:none"><?= h($p['area']) ?></a>
      <?php if ($p['busca_inversion']): ?><span class="chip inv">Busca inversión</span><?php endif; ?>
      <?php if ($p['destacado']): ?><span class="chip oscuro">Destacado</span><?php endif; ?>
    </div>
    <h1><?= h($p['nombre']) ?></h1>
    <p><?= h($p['resumen']) ?></p>
  </header>

  <section class="bloque" aria-labelledby="h-trl">
    <h2 id="h-trl">Madurez tecnológica</h2>
    <div class="trl-fila trl-grande"><span class="trl-num">TRL <?= (int) $p['trl'] ?> de 9</span><?= trl_medidor((int) $p['trl']) ?></div>
    <p class="suave pequeno" style="margin-top:0.5rem"><?= h(trl_nivel((int) $p['trl'])) ?>.</p>
  </section>

  <?php if ($p['descripcion']): ?>
  <section class="bloque" aria-labelledby="h-desc">
    <h2 id="h-desc">Sobre el proyecto</h2>
    <p><?= h($p['descripcion']) ?></p>
  </section>
  <?php endif; ?>

  <section class="bloque" aria-labelledby="h-datos">
    <h2 id="h-datos">Datos para negociar</h2>
    <dl class="datos">
      <div><dt>Ruta de salida</dt><dd><?= h($p['ruta_salida']) ?></dd></div>
      <div><dt>Propiedad intelectual</dt><dd><?= h($p['estado_pi']) ?></dd></div>
      <?php if ($p['centro']): ?><div><dt>Centro</dt><dd><?= h($p['centro']) ?></dd></div><?php endif; ?>
    </dl>
  </section>

  <?php if ($p['zona'] || $p['stand']): ?>
  <section class="bloque" aria-labelledby="h-ubi">
    <h2 id="h-ubi">Dónde encontrarlo</h2>
    <div class="ubicacion">
      <?= icono('ubicacion') ?>
      <div><?= h($p['zona'] ?? '') ?><?= $p['stand'] ? ' · Stand ' . h($p['stand']) : '' ?><small>Plaza Bicentenario, CCU</small></div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($equipo): ?>
  <section class="bloque" aria-labelledby="h-eq">
    <h2 id="h-eq">Equipo</h2>
    <ul class="equipo">
      <?php foreach ($equipo as $m): ?>
        <li>
          <span class="avatar" aria-hidden="true"><?= h(iniciales($m['nombre'])) ?></span>
          <div>
            <div class="nom"><?= h($m['nombre']) ?></div>
            <div class="det"><?= h($m['rol']) ?> · <?= h($m['centro']) ?><?= $m['especialidad'] ? ' · ' . h($m['especialidad']) : '' ?></div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <?php if ($relacionados): ?>
  <section class="bloque" aria-labelledby="h-rel">
    <h2 id="h-rel">Más de <?= h($p['area']) ?></h2>
    <div class="lista">
      <?php foreach ($relacionados as $r): ?><?= tarjeta_proyecto($r) ?><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</article>

<p class="aviso-demo">Proyecto ficticio con fines de demostración.</p>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
