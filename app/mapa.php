<?php
/**
 * Mapa del recinto (Fase 4): plano SVG esquemático con zonas, stands y pines.
 *
 *   mapa.php                  plano completo
 *   mapa.php?proyecto=ID      pin sobre el stand del proyecto (enlace desde su ficha)
 *   mapa.php?zona=ID          zona resaltada (enlace desde la agenda y el inicio)
 *
 * El plano se dibuja desde la base: zonas.mapa_x/mapa_y/mapa_ancho/mapa_alto y
 * proyectos.mapa_x/mapa_y (migración 006 y seed 005). Lo único fijo en el código es la
 * decoración (explanada, acceso, escenario). Sin JavaScript cada stand enlaza a su ficha;
 * con JavaScript (assets/js/mapa.js) se agregan zoom, arrastre, filtro por eje y hoja de detalle.
 */
require_once __DIR__ . '/../includes/app.php';

const MAPA_W  = 720;   // tamaño del plano en unidades (viewBox)
const MAPA_H  = 980;
const STAND_W = 88;    // tamaño fijo de un stand
const STAND_H = 48;

const ZONA_TIPOS = [
    'publica'     => 'Zona pública',
    'restringida' => 'Acceso restringido',
    'retos'       => 'Retos empresariales',
    'exhibicion'  => 'Exhibición',
    'soporte'     => 'Apoyo y asesoría',
    'escenario'   => 'Salas y escenario',
];

$activa = 'mapa';
$titulo = 'Mapa del recinto';

/** Pin en forma de gota con la punta en (x, y). Con $vivo late para marcar actividad en curso. */
function mapa_pin(float $x, float $y, string $clase = '', float $escala = 1.0): string
{
    $pulso = str_contains($clase, 'vivo') ? '<circle class="pulso" cx="0" cy="-27" r="14"/>' : '';
    return '<g class="pin ' . h($clase) . '" transform="translate(' . $x . ' ' . $y . ') scale(' . $escala . ')">'
         . '<g class="pin-in">' . $pulso
         . '<path d="M0 0C-4-9-14-17-14-27a14 14 0 0 1 28 0C14-17 4-9 0 0z"/><circle cx="0" cy="-27" r="5"/>'
         . '</g></g>';
}

/** Mensaje cuando falta importar algo (mismo estilo que la agenda). */
function mapa_aviso(string $texto): void
{
    global $titulo, $activa;
    require __DIR__ . '/../includes/app_header.php';
    echo '<div class="proximamente"><div class="circulo-grande">' . icono('mapa') . '</div>'
       . '<h1>Mapa del recinto</h1><p class="suave">' . h($texto) . '</p>'
       . '<a class="btn-app" href="inicio.php">Volver al inicio</a></div>';
    require __DIR__ . '/../includes/app_footer.php';
    exit;
}

/* ---------- Datos ---------- */

$migrada = consulta("SHOW COLUMNS FROM zonas LIKE 'mapa_x'") !== []
        && consulta("SHOW COLUMNS FROM proyectos LIKE 'mapa_x'") !== [];
if (!$migrada) {
    mapa_aviso('Falta importar db/migraciones/006_mapa.sql y db/seed/005_seed_mapa.sql en phpMyAdmin (ver docs/FASE4.md).');
}

$zonas = consulta(
    'SELECT id, nombre, tipo, descripcion, mapa_x, mapa_y, mapa_ancho, mapa_alto
     FROM zonas WHERE mapa_x IS NOT NULL ORDER BY id'
);
$proyectos = consulta(
    'SELECT p.id, p.nombre, p.resumen, p.trl, p.busca_inversion, p.stand, p.zona_id, p.mapa_x, p.mapa_y,
            a.nombre AS area, a.slug AS area_slug
     FROM proyectos p JOIN areas_tecnologicas a ON a.id = p.area_id
     WHERE p.mapa_x IS NOT NULL ORDER BY p.zona_id, p.stand'
);
if (!$zonas) {
    mapa_aviso('Todavía no hay coordenadas cargadas: importa db/seed/005_seed_mapa.sql en phpMyAdmin (ver docs/FASE4.md).');
}
$areas = areas_todas();

/* Actividades del día del evento (según el reloj) agrupadas por zona, para el detalle y los pines "en curso". */
$ahora = ahora_evento();
$diaN  = dia_del_evento($ahora) ?? 1;
$dias  = agenda_fechas();
$fechaDia = $dias[$diaN - 1]['fecha'] ?? $dias[0]['fecha'];
$actPorZona = [];
$vivas = [];   // zona_id => true si tiene una actividad en curso
foreach (actividades_del_dia($fechaDia) as $a) {
    $ini = new DateTimeImmutable($a['inicio']);
    $fin = new DateTimeImmutable($a['fin']);
    [$clase] = estado_actividad($ini, $fin, $ahora);
    $zid = (int) $a['zona_id'];
    $actPorZona[$zid][] = ['h' => hora_corta($ini) . ' a ' . hora_corta($fin), 't' => $a['titulo'], 'e' => $clase];
    if ($clase === 'en-curso') {
        $vivas[$zid] = true;
    }
}

$standsPorZona = [];
foreach ($proyectos as $p) {
    $standsPorZona[(int) $p['zona_id']][] = $p;
}

/* Datos para el JavaScript (todo se escribe en el DOM con textContent, nunca como HTML). */
$zJs = [];
foreach ($zonas as $z) {
    $id = (int) $z['id'];
    $zJs[$id] = [
        'nombre' => $z['nombre'],
        'tipo'   => ZONA_TIPOS[$z['tipo']] ?? ucfirst((string) $z['tipo']),
        'desc'   => (string) $z['descripcion'],
        'n'      => count($standsPorZona[$id] ?? []),
        'acts'   => $actPorZona[$id] ?? [],
        'vivo'   => isset($vivas[$id]),
        'x' => (int) $z['mapa_x'], 'y' => (int) $z['mapa_y'], 'w' => (int) $z['mapa_ancho'], 'h' => (int) $z['mapa_alto'],
    ];
}
$pJs = [];
foreach ($proyectos as $p) {
    $pJs[(int) $p['id']] = [
        'nombre' => $p['nombre'], 'resumen' => $p['resumen'], 'area' => $p['area'], 'areaSlug' => $p['area_slug'],
        'trl' => (int) $p['trl'], 'inv' => (bool) $p['busca_inversion'], 'stand' => $p['stand'],
        'zonaId' => (int) $p['zona_id'], 'x' => (int) $p['mapa_x'], 'y' => (int) $p['mapa_y'],
        'url' => 'proyecto.php?id=' . (int) $p['id'],
    ];
}

/* Selección inicial que llega por la URL. */
$sel = null;
if (isset($_GET['proyecto']) && isset($pJs[(int) $_GET['proyecto']])) {
    $sel = ['tipo' => 'stand', 'id' => (int) $_GET['proyecto']];
} elseif (isset($_GET['zona']) && isset($zJs[(int) $_GET['zona']])) {
    $sel = ['tipo' => 'zona', 'id' => (int) $_GET['zona']];
}

$datos = [
    'zonas' => $zJs, 'proyectos' => $pJs, 'inicial' => $sel, 'dia' => $diaN,
    'agenda' => 'agenda.php?dia=' . $diaN . '&vista=zona',
];

$scripts = ['assets/js/mapa.js'];
require __DIR__ . '/../includes/app_header.php';
?>

<section aria-labelledby="h-mapa">
  <h1 id="h-mapa">Mapa del recinto</h1>
  <p class="suave">Plaza Bicentenario y Conjunto Santander, CCU. Toca una zona o un stand para ver su detalle.</p>
</section>

<div class="mapa" data-mapa>
  <div class="mapa-filtros" role="group" aria-label="Filtrar stands por eje">
    <button type="button" data-filtro="" aria-pressed="true">Todos</button>
    <?php foreach ($areas as $a): ?>
      <button type="button" data-filtro="<?= h($a['slug']) ?>" aria-pressed="false"><?= h($a['nombre']) ?></button>
    <?php endforeach; ?>
  </div>
  <p class="skip-link" style="position:absolute" aria-live="polite" data-filtro-estado></p>

  <div class="mapa-lienzo">
    <svg class="plano" viewBox="0 0 <?= MAPA_W ?> <?= MAPA_H ?>" role="group" aria-label="Plano del recinto: zonas y stands">
      <rect class="fondo" x="0" y="0" width="<?= MAPA_W ?>" height="<?= MAPA_H ?>"/>
      <text class="plano-tit" x="24" y="48">Plaza Bicentenario</text>
      <rect class="plaza" x="8" y="76" width="704" height="648" rx="24"/>

      <?php /* ---- Zonas ---- */ foreach ($zonas as $z):
          $id = (int) $z['id'];
          $x = (int) $z['mapa_x']; $y = (int) $z['mapa_y']; $w = (int) $z['mapa_ancho']; $hh = (int) $z['mapa_alto'];
          $vivo = isset($vivas[$id]);
          $grande = $w >= 400;
          $banda = $hh < 80;   // franja baja: pin a la izquierda y título en una línea
      ?>
      <a class="zona <?= h($z['tipo']) ?>" href="mapa.php?zona=<?= $id ?>" data-tipo="zona" data-id="<?= $id ?>"
         aria-label="Zona <?= h($z['nombre']) ?><?= $vivo ? ', con actividad en curso' : '' ?>">
        <rect class="zona-rect" x="<?= $x ?>" y="<?= $y ?>" width="<?= $w ?>" height="<?= $hh ?>" rx="16"/>
        <?php if ($z['tipo'] === 'publica'): ?>
          <rect class="escenario" x="<?= $x + 186 ?>" y="<?= $y + 188 ?>" width="300" height="56" rx="10"/>
          <text class="deco-txt" x="<?= $x + 336 ?>" y="<?= $y + 216 ?>">Escenario</text>
        <?php elseif ($z['tipo'] === 'escenario'): ?>
          <rect class="sala" x="<?= $x + 24 ?>" y="<?= $y + 72 ?>" width="300" height="72" rx="10"/>
          <text class="deco-txt" x="<?= $x + 174 ?>" y="<?= $y + 108 ?>">Sala de conferencias</text>
          <rect class="sala" x="<?= $x + 348 ?>" y="<?= $y + 72 ?>" width="300" height="72" rx="10"/>
          <text class="deco-txt" x="<?= $x + 498 ?>" y="<?= $y + 108 ?>">Firma de contratos</text>
        <?php endif; ?>
        <?php if ($banda): ?>
          <?= mapa_pin($x + 26, $y + $hh - 10, $vivo ? 'vivo' : '', 0.7) ?>
          <text class="zona-tit banda" x="<?= $x + 52 ?>" y="<?= $y + intdiv($hh, 2) ?>" dominant-baseline="central"><?= h($z['nombre']) ?></text>
        <?php elseif ($grande): ?>
          <text class="zona-tit" x="<?= $x + 18 ?>" y="<?= $y + 36 ?>"><?= h($z['nombre']) ?></text>
          <?= mapa_pin($x + $w - 28, $y + 50, $vivo ? 'vivo' : '') ?>
        <?php else: ?>
          <?php /* Nombre en una o dos líneas, según quepa en la zona */
              $palabras = explode(' ', $z['nombre']);
              $lineas = (mb_strlen($z['nombre']) * 11.5 > $w - 24 && count($palabras) > 1)
                  ? [implode(' ', array_slice($palabras, 0, (int) ceil(count($palabras) / 2))), implode(' ', array_slice($palabras, (int) ceil(count($palabras) / 2)))]
                  : [$z['nombre']];
              $cxz = $x + intdiv($w, 2);
          ?>
          <text class="zona-tit chica" x="<?= $cxz ?>" y="<?= $y + 30 ?>" text-anchor="middle"><?php foreach ($lineas as $i => $ln): ?><tspan x="<?= $cxz ?>" dy="<?= $i === 0 ? 0 : 24 ?>"><?= h($ln) ?></tspan><?php endforeach; ?></text>
          <?= mapa_pin($cxz, $y + $hh - 8, $vivo ? 'vivo' : '', 0.85) ?>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>

      <?php /* ---- Stands (después de las zonas para quedar encima) ---- */ foreach ($proyectos as $p):
          $cx = (int) $p['mapa_x']; $cy = (int) $p['mapa_y'];
      ?>
      <a class="stand<?= $p['busca_inversion'] ? ' inv' : '' ?>" href="proyecto.php?id=<?= (int) $p['id'] ?>"
         data-tipo="stand" data-id="<?= (int) $p['id'] ?>" data-area="<?= h($p['area_slug']) ?>"
         aria-label="Stand <?= h($p['stand']) ?>: <?= h($p['nombre']) ?>">
        <rect x="<?= $cx - intdiv(STAND_W, 2) ?>" y="<?= $cy - intdiv(STAND_H, 2) ?>" width="<?= STAND_W ?>" height="<?= STAND_H ?>" rx="8"/>
        <text x="<?= $cx ?>" y="<?= $cy ?>"><?= h($p['stand']) ?></text>
      </a>
      <?php endforeach; ?>

      <?= mapa_pin(0, 0, 'sel oculto', 1.25) ?>
    </svg>

    <div class="mapa-ctrl">
      <button type="button" data-zoom="mas" aria-label="Acercar el plano">+</button>
      <button type="button" data-zoom="menos" aria-label="Alejar el plano">&minus;</button>
      <button type="button" data-zoom="todo" aria-label="Ver todo el plano" class="chico">Todo</button>
    </div>

    <div class="mapa-hoja" data-hoja hidden aria-live="polite"></div>
  </div>

  <ul class="mapa-leyenda" aria-label="Leyenda del plano">
    <li><i class="sw sw-stand"></i>Proyecto</li>
    <li><i class="sw sw-inv"></i>Busca inversión</li>
    <li><i class="sw sw-pin"></i>Zona con actividad en curso</li>
    <li><i class="sw sw-restr"></i>Acceso restringido</li>
  </ul>
  <p class="suave pequeno">Plano esquemático, sin escala. Pellizca o usa + y − para acercar; arrastra para moverte.</p>
</div>

<?php if ($sel): /* Texto equivalente (sin JavaScript o con lector de pantalla) */
    if ($sel['tipo'] === 'stand') { $s = $pJs[$sel['id']]; $zn = $zJs[$s['zonaId']]['nombre'] ?? ''; }
    else { $s = null; $zn = $zJs[$sel['id']]['nombre']; }
?>
<section class="bloque" aria-labelledby="h-ubic">
  <h2 id="h-ubic">Ubicación seleccionada</h2>
  <div class="ubicacion">
    <?= icono('ubicacion') ?>
    <div>
      <?php if ($s): ?>
        <?= h($s['nombre']) ?>
        <small>Stand <?= h($s['stand']) ?> · <?= h($zn) ?></small>
      <?php else: ?>
        <?= h($zn) ?>
        <small><?= h($zJs[$sel['id']]['tipo']) ?></small>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($s): ?><p><a class="btn-app" href="<?= h($s['url']) ?>">Ver ficha del proyecto</a></p><?php endif; ?>
</section>
<?php endif; ?>

<section class="bloque" aria-labelledby="h-lista">
  <h2 id="h-lista">Zonas y stands</h2>
  <details class="mapa-lista">
    <summary>Ver el recinto como lista</summary>
    <?php foreach ($zonas as $z): $id = (int) $z['id']; ?>
      <div class="mapa-lista-zona">
        <h3><a href="mapa.php?zona=<?= $id ?>"><?= h($z['nombre']) ?></a></h3>
        <p class="suave pequeno"><?= h($z['descripcion']) ?></p>
        <?php if (!empty($standsPorZona[$id])): ?>
          <ul>
            <?php foreach ($standsPorZona[$id] as $p): ?>
              <li><a href="mapa.php?proyecto=<?= (int) $p['id'] ?>"><strong><?= h($p['stand']) ?></strong> · <?= h($p['nombre']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </details>
</section>

<script type="application/json" id="mapa-datos"><?= json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
