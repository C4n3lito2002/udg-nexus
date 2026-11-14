<?php
/**
 * Centro de avisos: lista de avisos para el rol de la persona (y los de "todos"),
 * con los no leídos destacados. Sin JavaScript funciona con formularios;
 * con JavaScript (assets/js/app.js) marca por AJAX y los avisos nuevos aparecen solos.
 */
require_once __DIR__ . '/../includes/app.php';
requiere_login();

$u = usuario_actual();
$uid = (int) $u['id'];
$rol = (string) $u['rol'];
$tablasOk = avisos_tablas_existen();

if ($tablasOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'leer') {
        $idTexto = (string) ($_POST['id'] ?? '');
        if (ctype_digit($idTexto)) {
            aviso_marcar_leido($uid, $rol, (int) $idTexto);
        }
    } elseif ($accion === 'leer_todos') {
        aviso_marcar_todos($uid, $rol);
    }
    header('Location: notificaciones.php');
    exit;
}

$avisos = $tablasOk ? avisos_visibles($uid, $rol) : [];
$noLeidos = 0;
foreach ($avisos as $a) {
    $noLeidos += empty($a['leido']) ? 1 : 0;
}

$titulo = 'Avisos';
$activa = '';
$volver = 'inicio.php';
require __DIR__ . '/../includes/app_header.php';

/** Texto del resumen: "2 sin leer de 5 avisos". */
function avisos_resumen_texto(int $noLeidos, int $total): string
{
    if ($total === 0) {
        return 'No hay avisos por ahora.';
    }
    return ($noLeidos === 0 ? 'Todo al día' : $noLeidos . ' sin leer')
         . ' · ' . $total . ($total === 1 ? ' aviso' : ' avisos');
}
?>

<h1>Avisos</h1>

<?php if (!$tablasOk): ?>
  <div class="alerta error" role="alert">Faltan las tablas de avisos (importa <code>db/migraciones/007_notificaciones.sql</code>).</div>
<?php else: ?>

<p class="suave" data-avisos-resumen><?= h(avisos_resumen_texto($noLeidos, count($avisos))) ?></p>
<p class="skip-link" style="position:absolute" role="status" aria-live="polite" data-avisos-estado></p>

<form method="post" action="notificaciones.php" class="avisos-todos" data-avisos-todos <?= $noLeidos === 0 ? 'hidden' : '' ?>>
  <?= csrf_campo() ?>
  <input type="hidden" name="accion" value="leer_todos">
  <button class="btn-app sec ancho" type="submit"><?= icono('ok') ?> Marcar todos como leídos</button>
</form>

<div class="lista avisos-lista" data-avisos-lista>
  <?php foreach ($avisos as $a): $leido = !empty($a['leido']); ?>
    <article class="aviso <?= $leido ? 'leido' : 'no-leido' ?> <?= $a['nivel'] === 'importante' ? 'importante' : '' ?>" data-aviso="<?= (int) $a['id'] ?>">
      <div class="chips">
        <?php if (!$leido): ?><span class="chip inv" data-chip-nuevo>Sin leer</span><?php endif; ?>
        <?php if ($a['nivel'] === 'importante'): ?><span class="chip oscuro">Importante</span><?php endif; ?>
        <?php if ($a['audiencia'] !== 'todos'): ?><span class="chip">Para: <?= h(aviso_audiencia_texto($a['audiencia'])) ?></span><?php endif; ?>
      </div>
      <h2><?= h($a['titulo']) ?></h2>
      <p class="aviso-cuerpo"><?= h($a['cuerpo']) ?></p>
      <div class="aviso-pie">
        <small><?= h(aviso_hora_texto($a['publicada_en'])) ?></small>
        <?php if (!$leido): ?>
          <form method="post" action="notificaciones.php" data-form-leer>
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="leer">
            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <button type="submit" class="aviso-leer" data-avisos-leer>Marcar como leído</button>
          </form>
        <?php else: ?>
          <span class="aviso-ok"><?= icono('ok') ?> Leído</span>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  <p class="vacio-lista" data-avisos-vacio <?= $avisos ? 'hidden' : '' ?>>No hay avisos por ahora. Cuando el comité publique uno, aparecerá aquí sin recargar.</p>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
