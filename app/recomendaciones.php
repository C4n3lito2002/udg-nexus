<?php
/**
 * Para ti: recomendaciones por rol e intereses (reglas simples, sin IA).
 * Detalle de las reglas en includes/recomendaciones.php y en docs/FASE5B.md.
 * Requiere sesión. Staff y admin ven un aviso: las recomendaciones no aplican a su rol.
 */
require_once __DIR__ . '/../includes/app.php';
requiere_login();

$usuario  = usuario_actual();
$ahora    = ahora_evento();
$tablaOk  = agenda_tabla_existe();
$aplica   = reco_aplica($usuario);
$listo    = reco_tablas_existen();

$reco = ($aplica && $listo) ? reco_para($usuario, $ahora) : ['ejes' => [], 'proyecto' => null, 'items' => []];
[$miAgenda, $porId] = $aplica ? reco_agenda_de((int) $usuario['id']) : [[], []];
$actividades = reco_filtrar($reco['items'], 'actividad', 6);
$proyectos   = reco_filtrar($reco['items'], 'proyecto', 6);

$titulo = 'Para ti';
$activa = 'inicio';
$volver = 'inicio.php';
$diaEvento = dia_del_evento($ahora);
require __DIR__ . '/../includes/app_header.php';
?>

<section class="saludo">
  <h1>Para ti</h1>
  <p>Según tu perfil (<?= h(ROLES[$usuario['rol']] ?? $usuario['rol']) ?>) y tus intereses.</p>
  <span class="reloj-demo"><?= icono('reloj') ?><?= reloj_simulado_activo() ? 'Hora de la demo: ' : 'Hora del evento: ' ?><span data-fecha-evento><?= h(fecha_larga($ahora)) ?></span>, <span data-hora-evento><?= h(hora_corta($ahora)) ?></span><span data-dia-evento><?= $diaEvento ? ' · Día ' . $diaEvento : '' ?></span></span>
</section>

<?php if (!$aplica): ?>

  <div class="vacio-lista">
    <p>Las recomendaciones son para estudiantes, inversionistas, corporativos y expositores.</p>
    <a class="btn-app" href="inicio.php">Ir al inicio</a>
  </div>

<?php elseif (!$listo): ?>

  <p class="alerta error" role="alert">Las recomendaciones aún no están activadas: falta importar <code>db/migraciones/008_recomendaciones.sql</code> y <code>db/seed/007_seed_recomendaciones.sql</code> en phpMyAdmin.</p>

<?php else: ?>

  <section class="reco-perfil" aria-label="Tu perfil para las recomendaciones">
    <?php if ($reco['ejes']): ?>
      <p class="reco-perfil-tit">Tus ejes de interés</p>
      <div class="chips">
        <?php foreach ($reco['ejes'] as $nombre): ?><span class="chip"><?= h($nombre) ?></span><?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="suave pequeno">No elegiste ejes de interés al registrarte, así que se muestran recomendaciones generales para tu rol.</p>
    <?php endif; ?>
    <?php if ($usuario['rol'] === 'expositor'): ?>
      <?php if ($reco['proyecto']): ?>
        <p class="suave pequeno">Tu proyecto: <strong><?= h($reco['proyecto']['nombre']) ?></strong> · Eje <?= h($reco['proyecto']['area']) ?><?= $reco['proyecto']['zona'] ? ' · ' . h($reco['proyecto']['zona']) : '' ?></p>
      <?php else: ?>
        <p class="suave pequeno">Tu cuenta aún no está ligada a un proyecto, por eso no se recomiendan actividades de tu zona.</p>
      <?php endif; ?>
    <?php endif; ?>
  </section>

  <p class="skip-link" style="position:absolute" role="status" aria-live="polite" data-agenda-estado></p>

  <div data-agenda>
    <?php if (!$actividades && !$proyectos): ?>
      <p class="vacio-lista">Por ahora no hay nada más que recomendarte: lo que quedaba ya terminó según la hora del evento.</p>
    <?php endif; ?>

    <?php if ($actividades): ?>
      <section class="seccion" aria-labelledby="h-reco-act">
        <div class="seccion-cabecera"><h2 id="h-reco-act">Actividades para ti</h2><a href="agenda.php?ver=mia">Mi agenda</a></div>
        <div class="agenda-lista">
          <?php foreach ($actividades as $it) { echo reco_html_item($it, $ahora, $usuario, $tablaOk, $miAgenda, $porId); } ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($proyectos): ?>
      <section class="seccion" aria-labelledby="h-reco-proy">
        <div class="seccion-cabecera"><h2 id="h-reco-proy">Proyectos para ti</h2><a href="catalogo.php">Ver catálogo</a></div>
        <div class="agenda-lista">
          <?php foreach ($proyectos as $it) { echo reco_html_item($it, $ahora, $usuario, $tablaOk, $miAgenda, $porId); } ?>
        </div>
      </section>
    <?php endif; ?>

    <?= reco_json_agenda($miAgenda) ?>
  </div>

  <details class="reco-reglas">
    <summary>¿Cómo se eligen?</summary>
    <p>Son reglas simples, sin inteligencia artificial. Para tu rol:</p>
    <ul>
      <?php foreach (reco_reglas($usuario['rol']) as $regla): ?><li><?= h($regla) ?></li><?php endforeach; ?>
    </ul>
    <p>Lo que ya terminó según la hora del evento no se recomienda. Lo que ya está en tu agenda aparece más abajo.</p>
  </details>

<?php endif; ?>

<p class="aviso-demo">Versión de demostración: los proyectos y actividades son ficticios.</p>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
