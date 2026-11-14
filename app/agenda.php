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

/** Texto corto de estado respecto al reloj (los mismos textos que app.js al avanzar la hora). */
function estado_agenda(array $a, DateTimeImmutable $ahora): array
{
    [$clase, $etiqueta] = estado_actividad(new DateTimeImmutable($a['inicio']), new DateTimeImmutable($a['fin']), $ahora);
    return [$clase, $clase === 'luego' ? '' : $etiqueta];
}

/** Tarjeta de una actividad con su botón de agenda. */
function tarjeta_agenda(array $a, ?array $g, DateTimeImmutable $ahora, ?array $usuario, bool $tablaOk, array $miAgenda): string
{
    $ini = new DateTimeImmutable($a['inicio']);
    $fin = new DateTimeImmutable($a['fin']);
    [$clase, $etiqueta] = estado_agenda($a, $ahora);
    $enAgenda = $g !== null;
    $textoChoque = $usuario ? agenda_texto_choque(agenda_choca_con($a, $miAgenda), $enAgenda) : '';
    $alerta = $enAgenda && $g['alerta_minutos'] !== null ? (int) $g['alerta_minutos'] : null;

    $h  = '<article class="act-ag ' . h($clase) . '" data-actividad="' . (int) $a['id'] . '"'
        . ' data-inicio="' . h($a['inicio']) . '" data-fin="' . h($a['fin']) . '" data-titulo="' . h($a['titulo']) . '">';
    $h .= '<div class="act-hora"><strong>' . h(hora_corta($ini)) . '</strong><span>' . h(hora_corta($fin)) . '</span></div>';
    $h .= '<div class="act-cuerpo">';
    $h .= '<div class="chips"><span class="estado ' . h($clase) . '" data-estado' . ($etiqueta === '' ? ' hidden' : '') . '>' . h($etiqueta) . '</span>'
        . '<span class="chip ok" data-chip-agenda' . ($enAgenda ? '' : ' hidden') . '>En tu agenda</span></div>';
    $h .= '<h3>' . h($a['titulo']) . '</h3>';
    $zonaTxt = !empty($a['zona_id'])
        ? '<a class="enlace-mapa" href="mapa.php?zona=' . (int) $a['zona_id'] . '">' . h($a['zona']) . '</a>'
        : h($a['zona']);
    $h .= '<span class="meta">' . h($a['tipo']) . ' · ' . $zonaTxt . '</span>';
    if (!empty($a['ponente'])) {
        $h .= '<span class="meta">' . h($a['ponente']) . '</span>';
    }
    if ($usuario) {
        $h .= '<p class="choque" data-choque' . ($textoChoque === '' ? ' hidden' : '') . '>' . icono('aviso') . '<span>' . h($textoChoque) . '</span></p>';
    }

    if ($usuario && $tablaOk) {
        $h .= '<div class="act-acciones">';
        $h .= '<button type="button" class="btn-agenda' . ($enAgenda ? ' quitar' : '') . '" data-agenda-btn>'
            . ($enAgenda ? 'Quitar de mi agenda' : 'Agregar a mi agenda') . '</button>';
        $h .= '<label class="alerta-sel" data-alerta-wrap' . ($enAgenda ? '' : ' hidden') . '>' . icono('alerta') . '<span>Avisarme</span>'
            . '<select data-alerta-sel aria-label="Alerta para ' . h($a['titulo']) . '">';
        foreach ([['', 'Sin alerta'], [15, '15 min antes'], [30, '30 min antes'], [60, '60 min antes']] as [$v, $t]) {
            $h .= '<option value="' . h($v) . '"' . ((string) $v === (string) ($alerta ?? '') ? ' selected' : '') . '>' . h($t) . '</option>';
        }
        $h .= '</select></label></div>';
    } elseif (!$usuario) {
        $h .= '<div class="act-acciones"><a class="btn-agenda" href="login.php?volver=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '') . '">Entra para agregar a tu agenda</a></div>';
    }
    return $h . '</div></article>';
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
