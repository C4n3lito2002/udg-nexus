<?php
require_once __DIR__ . '/../includes/app.php';

$activa = 'catalogo';
$id = (int) ($_GET['id'] ?? 0);

$e = consulta_uno('SELECT * FROM empresas WHERE id = ?', 'i', [$id]);

if (!$e) {
    http_response_code(404);
    $titulo = 'Empresa no encontrada';
    $volver = 'catalogo.php?vista=empresas';
    require __DIR__ . '/../includes/app_header.php';
    echo '<div class="proximamente"><h1>No encontramos ese perfil</h1><p class="suave">Puede que el enlace sea incorrecto.</p><a class="btn-app" href="catalogo.php?vista=empresas">Ir a empresas</a></div>';
    require __DIR__ . '/../includes/app_footer.php';
    exit;
}

$areas = consulta(
    'SELECT a.nombre, a.slug FROM empresa_area ea JOIN areas_tecnologicas a ON a.id = ea.area_id
     WHERE ea.empresa_id = ? ORDER BY a.orden',
    'i',
    [$id]
);

$afines = [];
if ($areas) {
    $afines = consulta(
        'SELECT DISTINCT p.id, p.nombre, p.resumen, p.trl, p.busca_inversion, p.stand, a.nombre AS area, z.nombre AS zona
         FROM proyectos p
         JOIN empresa_area ea ON ea.area_id = p.area_id AND ea.empresa_id = ?
         JOIN areas_tecnologicas a ON a.id = p.area_id
         LEFT JOIN zonas z ON z.id = p.zona_id
         ORDER BY p.busca_inversion DESC, p.trl DESC, p.nombre
         LIMIT 4',
        'i',
        [$id]
    );
}

$titulo = $e['nombre'];
$volver = 'catalogo.php?vista=empresas';
require __DIR__ . '/../includes/app_header.php';
?>

<article>
  <header class="ficha-cab">
    <div class="chips">
      <span class="chip oscuro"><?= h(etiqueta_tipo_empresa($e['tipo'])) ?></span>
      <?php if ($e['nivel_patrocinio']): ?><span class="chip">Nivel <?= h($e['nivel_patrocinio']) ?></span><?php endif; ?>
    </div>
    <h1><?= h($e['nombre']) ?></h1>
    <?php if ($e['sector']): ?><p class="suave"><?= h($e['sector']) ?></p><?php endif; ?>
  </header>

  <?php if ($e['descripcion']): ?>
  <section class="bloque" aria-labelledby="h-acerca">
    <h2 id="h-acerca">Acerca de</h2>
    <p><?= h($e['descripcion']) ?></p>
  </section>
  <?php endif; ?>

  <?php if ($e['busca'] || $e['ticket'] || $areas): ?>
  <section class="bloque" aria-labelledby="h-interes">
    <h2 id="h-interes">Qué busca</h2>
    <dl class="datos">
      <?php if ($e['busca']): ?><div><dt>Interés</dt><dd><?= h($e['busca']) ?></dd></div><?php endif; ?>
      <?php if ($e['ticket']): ?><div><dt>Monto típico</dt><dd><?= h($e['ticket']) ?></dd></div><?php endif; ?>
    </dl>
    <?php if ($areas): ?>
      <div class="chips" style="margin-top:0.8rem">
        <?php foreach ($areas as $a): ?>
          <a class="chip" style="text-decoration:none" href="catalogo.php?area=<?= h($a['slug']) ?>"><?= h($a['nombre']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($afines): ?>
  <section class="bloque" aria-labelledby="h-afines">
    <h2 id="h-afines">Proyectos que podrían interesarle</h2>
    <div class="lista">
      <?php foreach ($afines as $p): ?><?= tarjeta_proyecto($p) ?><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</article>

<?php if ($e['es_ficticia']): ?>
  <p class="aviso-demo">Perfil ficticio con fines de demostración.</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
