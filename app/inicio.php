<?php
require_once __DIR__ . '/../includes/app.php';

$titulo = 'Inicio';
$activa = 'inicio';

$ahora = ahora_evento();
$enCurso = actividades_ahora($ahora, 60);
$soloProximas = false;
if (!$enCurso) {
    $enCurso = actividades_proximas($ahora, 3);
    $soloProximas = true;
}
$vitrina = proyectos_vitrina(6);
$anuncios = consulta(
    "SELECT a.id, a.tipo, a.titulo, a.texto, a.enlace, a.empresa_id
     FROM anuncios a WHERE a.activo = 1
     ORDER BY FIELD(a.tipo,'comite','patrocinador'), a.orden, a.id"
);
$diaEvento = dia_del_evento($ahora);

require __DIR__ . '/../includes/app_header.php';
?>

<section class="saludo">
  <h1>Bienvenido a UDG NEXUS</h1>
  <p>Feria de transferencia tecnológica · Plaza Bicentenario, CCU</p>
  <?php if (reloj_simulado_activo()): ?>
    <span class="reloj-demo"><?= icono('reloj') ?>Hora de la demo: <?= h(fecha_larga($ahora)) ?>, <?= h(hora_corta($ahora)) ?><?= $diaEvento ? ' · Día ' . $diaEvento : '' ?></span>
  <?php elseif ($diaEvento): ?>
    <span class="reloj-demo"><?= icono('reloj') ?>Día <?= $diaEvento ?> del evento</span>
  <?php endif; ?>
</section>

<div class="buscador" data-buscador>
  <form action="buscar.php" method="get" role="search">
    <label for="q-inicio" class="skip-link" style="position:absolute">Buscar proyectos, áreas, empresas o investigadores</label>
    <?= icono('buscar') ?>
    <input type="search" id="q-inicio" name="q" placeholder="Busca proyectos, áreas, empresas…" autocomplete="off" minlength="2" aria-describedby="q-estado">
  </form>
  <div class="resultados" hidden></div>
  <p id="q-estado" data-estado class="skip-link" style="position:absolute" aria-live="polite"></p>
</div>

<nav class="atajos" aria-label="Accesos directos">
  <a class="atajo" href="proximamente.php?m=gafete"><span class="circulo"><?= icono('gafete') ?></span>Gafete QR</a>
  <a class="atajo" href="proximamente.php?m=mapa"><span class="circulo"><?= icono('mapa') ?></span>Mapa</a>
  <a class="atajo" href="catalogo.php"><span class="circulo"><?= icono('catalogo') ?></span>Catálogo</a>
  <a class="atajo" href="proximamente.php?m=agenda"><span class="circulo"><?= icono('agenda') ?></span>Mi agenda</a>
</nav>

<section class="seccion" aria-labelledby="h-ahora">
  <div class="seccion-cabecera">
    <h2 id="h-ahora"><?php if (!$soloProximas): ?><span class="punto-vivo" aria-hidden="true"></span><?php endif; ?><?= $soloProximas ? 'Próximamente' : 'En este momento' ?></h2>
  </div>
  <?php if ($enCurso): ?>
    <div data-carrusel>
      <div class="carrusel">
        <?php foreach ($enCurso as $a):
            $ini = new DateTimeImmutable($a['inicio']);
            $fin = new DateTimeImmutable($a['fin']);
            [$clase, $etiqueta, $detalle] = estado_actividad($ini, $fin, $ahora);
        ?>
          <article class="act <?= h($clase) ?>">
            <span class="estado <?= h($clase) ?>"><?= h($etiqueta) ?></span>
            <h3><?= h($a['titulo']) ?></h3>
            <span class="meta"><?= h($a['tipo']) ?> · <?= h($a['zona']) ?></span>
            <span class="meta"><?= h($detalle) ?></span>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="carrusel-puntos"></div>
    </div>
  <?php else: ?>
    <p class="vacio-lista">No hay actividades programadas por ahora.</p>
  <?php endif; ?>
</section>

<section class="seccion" aria-labelledby="h-vitrina">
  <div class="seccion-cabecera">
    <h2 id="h-vitrina">Vitrina de proyectos</h2>
    <a href="catalogo.php?trl=alta">Ver TRL 7 o más</a>
  </div>
  <p class="suave pequeno" style="margin-top:-0.3rem">Prototipos con alta madurez tecnológica o en búsqueda de inversión.</p>
  <div data-carrusel data-auto>
    <div class="carrusel">
      <?php foreach ($vitrina as $p): ?>
        <a class="tarjeta-vitrina" href="proyecto.php?id=<?= (int) $p['id'] ?>">
          <span class="area"><?= h($p['area']) ?></span>
          <h3><?= h($p['nombre']) ?></h3>
          <p><?= h($p['resumen']) ?></p>
          <span class="fila">
            <span class="chip-trl">TRL <?= (int) $p['trl'] ?></span>
            <?php if ($p['busca_inversion']): ?><span class="chip-inv">Busca inversión</span><?php endif; ?>
          </span>
          <span class="ver">Ver ficha →</span>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="carrusel-puntos"></div>
  </div>
</section>

<?php if ($anuncios): ?>
<section class="seccion" aria-labelledby="h-avisos">
  <div class="seccion-cabecera"><h2 id="h-avisos">Avisos y aliados</h2></div>
  <?php foreach ($anuncios as $an):
      $esComite = $an['tipo'] === 'comite';
      $href = $an['enlace'] ?: ($an['empresa_id'] ? 'empresa.php?id=' . (int) $an['empresa_id'] : '');
  ?>
    <<?= $href ? 'a href="' . h($href) . '"' : 'div' ?> class="anuncio <?= $esComite ? 'comite' : 'patro' ?>">
      <span class="etq"><?= $esComite ? 'Comité organizador' : 'Patrocinador' ?></span>
      <strong><?= h($an['titulo']) ?></strong>
      <span class="t"><?= h($an['texto']) ?></span>
    </<?= $href ? 'a' : 'div' ?>>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<p class="aviso-demo">Versión de demostración: los proyectos, investigadores, fondos y compradores son ficticios. Los patrocinadores y aliados corresponden a los publicados en el sitio del proyecto.</p>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
