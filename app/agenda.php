<?php
/**
 * Agenda del evento: programa por día y por zona + "Mi agenda" con alertas.
 *   agenda.php?dia=1&vista=hora|zona      programa (público; para agregar se necesita sesión)
 *   agenda.php?ver=mia                    mi agenda (requiere sesión)
 * Todo el tiempo se calcula con el reloj del evento. La interacción (agregar,
 * quitar, alerta, empalmes) la hace assets/js/app.js contra api/agenda_toggle.php.
 */
require_once __DIR__ . '/../includes/app.php';

$ver = (($_GET['ver'] ?? '') === 'mia') ? 'mia' : 'programa';
if ($ver === 'mia') {
    requiere_login();
}

$ahora    = ahora_evento();
$usuario  = usuario_actual();
$tablaOk  = agenda_tabla_existe();
$puede    = $usuario !== null && $tablaOk;          // puede agendar
$fechas   = agenda_fechas();
$vista    = (($_GET['vista'] ?? '') === 'zona') ? 'zona' : 'hora';
$dia      = (int) ($_GET['dia'] ?? (dia_del_evento($ahora) ?? 1));
$dia      = max(1, min(count($fechas), $dia));

$miAgenda = $puede ? agenda_usuario((int) $usuario['id']) : [];
$porId = [];
foreach ($miAgenda as $g) {
    $porId[(int) $g['id']] = $g;
}

/** Etiqueta corta del día: 'mié 10 mar'. */
function dia_corto(DateTimeInterface $f): string
{
    return mb_substr(DIAS_ES[(int) $f->format('w')], 0, 3) . ' ' . $f->format('j') . ' ' . mb_substr(MESES_ES[(int) $f->format('n') - 1], 0, 3);
}

function url_agenda(array $p): string
{
    return 'agenda.php?' . http_build_query($p);
}

$titulo = $ver === 'mia' ? 'Mi agenda' : 'Agenda';
$activa = 'agenda';
$diaEvento = dia_del_evento($ahora);
require __DIR__ . '/../includes/app_header.php';
?>

<section class="saludo">
  <h1><?= $ver === 'mia' ? 'Mi agenda' : 'Agenda del evento' ?></h1>
  <span class="reloj-demo"><?= icono('reloj') ?><?= reloj_simulado_activo() ? 'Hora de la demo: ' : 'Hora del evento: ' ?><span data-fecha-evento><?= h(fecha_larga($ahora)) ?></span>, <span data-hora-evento><?= h(hora_corta($ahora)) ?></span><span data-dia-evento><?= $diaEvento ? ' · Día ' . $diaEvento : '' ?></span></span>
</section>

<?php if ($usuario && !$tablaOk): ?>
  <p class="alerta error" role="alert">La agenda personal aún no está activada: falta importar <code>db/migraciones/004_agenda.sql</code> en phpMyAdmin.</p>
<?php endif; ?>

<nav class="segmento" aria-label="Vista de la agenda">
  <a href="<?= h(url_agenda(['dia' => $dia, 'vista' => $vista])) ?>" <?= $ver === 'programa' ? 'aria-current="page"' : '' ?>>Programa</a>
  <a href="<?= h(url_agenda(['ver' => 'mia'])) ?>" <?= $ver === 'mia' ? 'aria-current="page"' : '' ?>>Mi agenda<?php if ($usuario): ?> (<span data-conteo-agenda><?= count($miAgenda) ?></span>)<?php endif; ?></a>
</nav>

<p class="skip-link" style="position:absolute" role="status" aria-live="polite" data-agenda-estado></p>

<?php if ($ver === 'programa'): ?>

  <nav class="dias" aria-label="Días del evento">
    <?php foreach ($fechas as $f): ?>
      <a href="<?= h(url_agenda(['dia' => $f['n'], 'vista' => $vista])) ?>" <?= $f['n'] === $dia ? 'aria-current="page"' : '' ?>>
        Día <?= (int) $f['n'] ?><small><?= h(dia_corto($f['fecha'])) ?></small>
      </a>
    <?php endforeach; ?>
  </nav>

  <nav class="segmento" aria-label="Agrupar actividades">
    <a href="<?= h(url_agenda(['dia' => $dia, 'vista' => 'hora'])) ?>" <?= $vista === 'hora' ? 'aria-current="page"' : '' ?>>Por hora</a>
    <a href="<?= h(url_agenda(['dia' => $dia, 'vista' => 'zona'])) ?>" <?= $vista === 'zona' ? 'aria-current="page"' : '' ?>>Por zona</a>
  </nav>

  <?php
  $actividades = actividades_del_dia($fechas[$dia - 1]['fecha']);
  ?>
  <p class="cuenta"><?= h(fecha_larga($fechas[$dia - 1]['fecha'])) ?> · <?= count($actividades) ?> actividades</p>

  <div data-agenda>
    <?php if (!$actividades): ?>
      <p class="vacio-lista">No hay actividades programadas este día.</p>
    <?php elseif ($vista === 'zona'): ?>
      <?php
      $porZona = [];
      foreach ($actividades as $a) {
          $porZona[(int) $a['zona_id']]['nombre'] = $a['zona'];
          $porZona[(int) $a['zona_id']]['items'][] = $a;
      }
      ksort($porZona);
      foreach ($porZona as $z): ?>
        <section class="zona-grupo">
          <h2 class="zona-tit"><?= icono('ubicacion') ?><?= h($z['nombre']) ?></h2>
          <div class="agenda-lista">
            <?php foreach ($z['items'] as $a) { echo tarjeta_agenda($a, $porId[(int) $a['id']] ?? null, $ahora, $usuario, $tablaOk, $miAgenda); } ?>
          </div>
        </section>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="agenda-lista">
        <?php foreach ($actividades as $a) { echo tarjeta_agenda($a, $porId[(int) $a['id']] ?? null, $ahora, $usuario, $tablaOk, $miAgenda); } ?>
      </div>
    <?php endif; ?>
    <script type="application/json" id="agenda-datos"><?= json_encode(agenda_para_js($miAgenda), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
  </div>

<?php else: ?>

  <?php
  $total = count($miAgenda);
  $empalmes = agenda_total_empalmes($miAgenda);
  $porDia = [];
  foreach ($miAgenda as $g) {
      $porDia[substr($g['inicio'], 0, 10)][] = $g;
  }
  ?>
  <p class="cuenta" data-resumen><?= $total === 1 ? '1 actividad' : $total . ' actividades' ?> · <?= $empalmes === 0 ? 'sin empalmes' : ($empalmes === 1 ? '1 empalme de horario' : $empalmes . ' empalmes de horario') ?></p>

  <div data-agenda data-solo-mia>
    <div class="vacio-lista" data-vacio<?= $total ? ' hidden' : '' ?>>
      <p>Todavía no agregas actividades.</p>
      <a class="btn-app" href="<?= h(url_agenda(['dia' => $dia])) ?>">Ver el programa</a>
    </div>
    <?php foreach ($porDia as $fecha => $items):
        $f = new DateTimeImmutable($fecha);
        $n = dia_del_evento($f);
    ?>
      <section class="dia-grupo" data-dia-grupo>
        <h2 class="zona-tit"><?= icono('agenda') ?><?= $n ? 'Día ' . (int) $n . ' · ' : '' ?><?= h(fecha_larga($f)) ?></h2>
        <div class="agenda-lista">
          <?php foreach ($items as $g) { echo tarjeta_agenda($g, $g, $ahora, $usuario, $tablaOk, $miAgenda); } ?>
        </div>
      </section>
    <?php endforeach; ?>
    <script type="application/json" id="agenda-datos"><?= json_encode(agenda_para_js($miAgenda), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
  </div>

<?php endif; ?>

<p class="aviso-demo">Las alertas se calculan con la hora del evento (simulada en esta demostración), no con la hora real. Aparecen dentro de la app, en cualquier pantalla.</p>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
