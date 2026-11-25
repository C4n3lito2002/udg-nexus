<?php
require_once __DIR__ . '/../includes/app.php';

$titulo = 'Catálogo';
$activa = 'catalogo';

$vista = ($_GET['vista'] ?? 'proyectos') === 'empresas' ? 'empresas' : 'proyectos';
$q = trim((string) ($_GET['q'] ?? ''));
$areaSel = trim((string) ($_GET['area'] ?? ''));
$trlSel = (string) ($_GET['trl'] ?? '');
$inversion = !empty($_GET['inversion']);
$tipoSel = (string) ($_GET['tipo'] ?? '');

$areas = areas_todas();

if ($vista === 'proyectos') {
    $resultados = proyectos_filtrados(['q' => $q, 'area' => $areaSel, 'trl' => $trlSel, 'inversion' => $inversion]);
} else {
    $resultados = empresas_filtradas(['q' => $q, 'tipo' => $tipoSel]);
}
$hayFiltros = ($q !== '' || $areaSel !== '' || $trlSel !== '' || $inversion || $tipoSel !== '');

require __DIR__ . '/../includes/app_header.php';
?>

<h1>Catálogo</h1>
<p class="suave pequeno">Prototipos de la Red Universitaria y perfiles de empresas e inversionistas.</p>

<nav class="segmento" aria-label="Tipo de catálogo">
  <a href="catalogo.php?vista=proyectos" <?= $vista === 'proyectos' ? 'aria-current="page"' : '' ?>>Proyectos</a>
  <a href="catalogo.php?vista=empresas" <?= $vista === 'empresas' ? 'aria-current="page"' : '' ?>>Empresas</a>
</nav>

<form class="filtros" action="catalogo.php" method="get">
  <input type="hidden" name="vista" value="<?= h($vista) ?>">
  <label for="f-q" class="skip-link" style="position:absolute">Buscar en el catálogo</label>
  <input type="search" id="f-q" name="q" value="<?= h($q) ?>" placeholder="Buscar por nombre, tema o equipo…">

  <?php if ($vista === 'proyectos'): ?>
    <div class="fila">
      <div>
        <label for="f-area" class="skip-link" style="position:absolute">Área tecnológica</label>
        <select id="f-area" name="area">
          <option value="">Todas las áreas</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= h($a['slug']) ?>" <?= $areaSel === $a['slug'] ? 'selected' : '' ?>><?= h($a['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="f-trl" class="skip-link" style="position:absolute">Nivel de madurez TRL</label>
        <select id="f-trl" name="trl">
          <option value="">Cualquier TRL</option>
          <option value="alta" <?= $trlSel === 'alta' ? 'selected' : '' ?>>TRL 7 · Alta</option>
          <option value="media" <?= $trlSel === 'media' ? 'selected' : '' ?>>TRL 4 a 6 · Media</option>
        </select>
      </div>
    </div>
    <label class="check"><input type="checkbox" name="inversion" value="1" <?= $inversion ? 'checked' : '' ?>> Solo los que buscan inversión</label>
  <?php else: ?>
    <label for="f-tipo" class="skip-link" style="position:absolute">Tipo de empresa</label>
    <select id="f-tipo" name="tipo">
      <option value="">Todos los tipos</option>
      <option value="fondo" <?= $tipoSel === 'fondo' ? 'selected' : '' ?>>Fondos e inversionistas</option>
      <option value="comprador" <?= $tipoSel === 'comprador' ? 'selected' : '' ?>>Compradores</option>
      <option value="patrocinador" <?= $tipoSel === 'patrocinador' ? 'selected' : '' ?>>Patrocinadores</option>
      <option value="aliado" <?= $tipoSel === 'aliado' ? 'selected' : '' ?>>Aliados institucionales</option>
    </select>
  <?php endif; ?>

  <div class="acciones">
    <button class="btn-app" type="submit">Filtrar</button>
    <?php if ($hayFiltros): ?><a class="btn-app sec" href="catalogo.php?vista=<?= h($vista) ?>">Limpiar</a><?php endif; ?>
  </div>
</form>

<p class="cuenta" aria-live="polite"><?= count($resultados) ?> <?= $vista === 'proyectos' ? (count($resultados) === 1 ? 'proyecto' : 'proyectos') : (count($resultados) === 1 ? 'empresa' : 'empresas') ?></p>

<?php if ($resultados): ?>
  <div class="lista">
    <?php foreach ($resultados as $r): ?>
      <?= $vista === 'proyectos' ? tarjeta_proyecto($r) : tarjeta_empresa($r) ?>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p class="vacio-lista">No encontramos resultados con esos filtros. Prueba quitando alguno.</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
