<?php
require_once __DIR__ . '/../../includes/app.php';
requiere_rol('admin');

$aviso = '';
$error = '';

/** Guarda un valor de configuración (lo crea si no existe). */
function guardar_config(string $clave, string $valor): bool
{
    return ejecutar(
        'INSERT INTO config_evento (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)',
        'ss',
        [$clave, $valor]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $modo = (string) ($_POST['modo'] ?? '');
    $velocidades = [1, 10, 60, 300];
    $velocidad = (int) ($_POST['velocidad'] ?? 1);
    $base = parse_fecha_hora(trim((string) ($_POST['fecha'] ?? '')) . ' ' . trim((string) ($_POST['hora'] ?? '')) . ':00');

    if (!in_array($modo, ['real', 'fijo', 'avance'], true)) {
        $error = 'Elige un modo de reloj.';
    } elseif ($modo !== 'real' && $base === null) {
        $error = 'Escribe una fecha y una hora válidas.';
    } elseif (!in_array($velocidad, $velocidades, true)) {
        $error = 'Elige una velocidad válida.';
    } else {
        $ok = guardar_config('reloj_modo', $modo);
        if ($modo !== 'real') {
            $ok = $ok && guardar_config('reloj_base', $base->format('Y-m-d H:i:s'))
                      && guardar_config('reloj_ancla', date('Y-m-d H:i:s'))
                      && guardar_config('reloj_velocidad', (string) $velocidad);
        }
        if ($ok) {
            header('Location: reloj.php?ok=1');
            exit;
        }
        $error = 'No se pudo guardar. ¿Importaste db/migraciones/003_reloj.sql?';
    }
}

$ahora = ahora_evento();
$modo = reloj_modo();
$velActual = max(1, (int) config_evento('reloj_velocidad', '1'));
$inicioEvento = config_evento('evento_inicio', '2027-03-10');

/** Momentos clave para saltar rápido durante la demo. */
$atajos = [];
$etiquetas = [[0, '09:00', 'Día 1, inauguración'], [0, '11:40', 'Día 1, a media mañana'], [0, '15:50', 'Día 1, por la tarde'],
              [1, '10:00', 'Día 2, mañana'], [2, '10:00', 'Día 3, mañana']];
foreach ($etiquetas as [$dia, $hora, $texto]) {
    $f = (new DateTimeImmutable($inicioEvento))->modify('+' . $dia . ' days');
    $atajos[] = ['texto' => $texto, 'fecha' => $f->format('Y-m-d'), 'hora' => $hora];
}

$titulo = 'Reloj del evento';
$activa = '';
$volver = 'index.php';
require __DIR__ . '/../../includes/app_header.php';
?>

<h1>Reloj del evento</h1>
<p class="suave">Toda la app (el “En este momento”, la agenda y las alertas) usa esta hora.</p>

<?php if (isset($_GET['ok'])): ?>
  <div class="alerta ok" role="status">Reloj actualizado.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alerta error" role="alert"><?= h($error) ?></div>
<?php endif; ?>

<div class="reloj-grande" aria-live="off">
  <?= icono('reloj') ?>
  <div>
    <strong data-reloj-evento="<?= h($ahora->format('c')) ?>"><?= h(hora_corta($ahora)) ?></strong>
    <span><?= h(fecha_larga($ahora)) ?></span>
  </div>
</div>

<form class="form-app" method="post" action="reloj.php">
  <?= csrf_campo() ?>

  <fieldset class="campo">
    <legend>Modo</legend>
    <label class="opcion"><input type="radio" name="modo" value="avance" <?= $modo === 'avance' ? 'checked' : '' ?>>
      <span><strong>Avanza sola</strong> Arranca en la hora que elijas y corre. Ideal para que las alertas salten en vivo.</span></label>
    <label class="opcion"><input type="radio" name="modo" value="fijo" <?= $modo === 'fijo' ? 'checked' : '' ?>>
      <span><strong>Hora fija</strong> Se queda quieta en la hora que elijas.</span></label>
    <label class="opcion"><input type="radio" name="modo" value="real" <?= $modo === 'real' ? 'checked' : '' ?>>
      <span><strong>Hora real</strong> Usa la hora de tu computadora.</span></label>
  </fieldset>

  <div class="fila-campos">
    <div class="campo">
      <label for="fecha">Fecha</label>
      <input type="date" id="fecha" name="fecha" value="<?= h($ahora->format('Y-m-d')) ?>">
    </div>
    <div class="campo">
      <label for="hora">Hora</label>
      <input type="time" id="hora" name="hora" value="<?= h($ahora->format('H:i')) ?>">
    </div>
  </div>

  <div class="campo">
    <label for="velocidad">Velocidad (solo en “Avanza sola”)</label>
    <select id="velocidad" name="velocidad">
      <?php foreach ([1 => 'Tiempo real (x1)', 10 => 'x10: 1 s = 10 s', 60 => 'x60: 1 s = 1 min', 300 => 'x300: 1 s = 5 min'] as $v => $t): ?>
        <option value="<?= $v ?>" <?= $velActual === $v ? 'selected' : '' ?>><?= h($t) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <button class="btn-app ancho" type="submit">Guardar reloj</button>
</form>

<section class="bloque">
  <h2>Saltar a un momento</h2>
  <div class="atajos-reloj">
    <?php foreach ($atajos as $a): ?>
      <button type="button" class="btn-app sec" data-salto-fecha="<?= h($a['fecha']) ?>" data-salto-hora="<?= h($a['hora']) ?>"><?= h($a['texto']) ?></button>
    <?php endforeach; ?>
  </div>
  <p class="pequeno suave">Llena la fecha y la hora de arriba; después toca “Guardar reloj”.</p>
</section>

<?php require __DIR__ . '/../../includes/app_footer.php'; ?>
