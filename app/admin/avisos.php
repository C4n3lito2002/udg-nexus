<?php
/**
 * Panel de avisos (solo administrador): publicar un aviso para todos o para un rol,
 * ver los publicados con cuántas personas lo leyeron y retirar uno.
 *
 * Seguridad:
 *  - rol admin en la página y en cada envío (requiere_rol);
 *  - token CSRF de la sesión (csrf_campo / csrf_validar) en todos los formularios;
 *  - token de un solo uso (includes/forms.php) contra el doble envío: reenviar el mismo
 *    formulario (doble clic, "atrás", recarga del POST) no duplica el aviso;
 *  - retirar pide DOS pasos en el servidor: primero una pantalla de confirmación (GET
 *    ?retirar=ID) que emite el token, y solo ese segundo envío retira el aviso.
 * Retirar no borra: el aviso deja de verse y se conserva su conteo de lecturas.
 */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/forms.php';
requiere_rol('admin');

$tablasOk = avisos_tablas_existen();
$admin = usuario_actual();
$valores = ['titulo' => '', 'cuerpo' => '', 'audiencia' => 'todos', 'nivel' => 'info'];
$errores = [];
$error = '';
$retirar = null;   // aviso en pantalla de confirmación

/** Redirige al panel con un mensaje (evita reenviar el POST al recargar). */
function volver_al_panel(string $ok): void
{
    header('Location: avisos.php?ok=' . rawurlencode($ok));
    exit;
}

if ($tablasOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'publicar') {
        $previo = form_abrir('aviso_publicar');
        if ($previo === 'ok') {
            volver_al_panel('repetido');   // mismo formulario reenviado: ya estaba publicado
        } elseif ($previo !== null) {
            [$valores] = aviso_validar($_POST);   // conserva lo escrito para no perderlo
            $error = $previo === 'expirado'
                ? 'El formulario expiró o ya se había enviado. Revisa la lista de abajo y, si hace falta, vuelve a publicar.'
                : 'Ese envío ya se había procesado y no se pudo publicar. Inténtalo de nuevo.';
        } else {
            [$valores, $errores] = aviso_validar($_POST);
            if ($errores) {
                form_cerrar(false);
                form_log('aviso_publicar', 'validacion', 'campos invalidos: ' . implode(', ', array_keys($errores)));
            } else {
                $id = aviso_publicar($valores, (int) $admin['id']);
                form_cerrar($id > 0);
                if ($id > 0) {
                    volver_al_panel('publicado');
                }
                form_log('aviso_publicar', 'db_error', 'no se pudo insertar el aviso');
                $error = 'No se pudo guardar el aviso. Inténtalo de nuevo.';
            }
        }
    } elseif ($accion === 'retirar') {
        $idTexto = (string) ($_POST['id'] ?? '');
        $id = ctype_digit($idTexto) ? (int) $idTexto : 0;
        $previo = form_abrir('aviso_retirar_' . $id);
        if ($previo === 'ok') {
            volver_al_panel('retirado');
        } elseif ($previo !== null) {
            $error = 'La confirmación expiró o ya se había usado. Si quieres retirar un aviso, vuelve a pulsar «Retirar» en la lista.';
        } elseif ($id > 0 && aviso_retirar($id)) {
            form_cerrar(true);
            volver_al_panel('retirado');
        } else {
            form_cerrar(false);
            form_log('aviso_retirar', 'no_retirado', 'id=' . $id . ' no existe o ya estaba retirado');
            $error = 'Ese aviso no existe o ya estaba retirado.';
        }
    }
}

// Pantalla de confirmación (paso 1 de retirar): solo se muestra, no cambia nada.
if ($tablasOk && isset($_GET['retirar']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $idTexto = (string) $_GET['retirar'];
    $candidato = ctype_digit($idTexto) && strlen($idTexto) < 10 ? aviso_admin_uno((int) $idTexto) : null;
    if ($candidato !== null && $candidato['retirada_en'] === null) {
        $retirar = $candidato;
    } else {
        $error = 'Ese aviso no existe o ya estaba retirado.';
    }
}

$todos = $tablasOk ? avisos_admin_lista() : [];
$publicados = array_values(array_filter($todos, static fn($a) => $a['retirada_en'] === null));
$retirados = array_values(array_filter($todos, static fn($a) => $a['retirada_en'] !== null));
$mensajes = [
    'publicado' => 'Aviso publicado. Quienes sean parte de la audiencia lo verán aparecer en su celular en unos segundos, sin recargar.',
    'repetido'  => 'Ese aviso ya se había publicado: no se duplicó.',
    'retirado'  => 'Aviso retirado. Ya no se muestra ni cuenta como no leído; su conteo de lecturas se conserva.',
];
$ok = $mensajes[(string) ($_GET['ok'] ?? '')] ?? '';

/** Tarjeta de un aviso con su avance de lectura. */
function aviso_admin_tarjeta(array $a, bool $conAcciones): string
{
    $leyeron = (int) $a['leyeron'];
    $dest = (int) $a['destinatarios'];
    $pct = $dest > 0 ? (int) round($leyeron * 100 / $dest) : 0;
    $h  = '<article class="aviso-adm' . ($a['retirada_en'] !== null ? ' retirado' : '') . '" data-aviso-admin="' . (int) $a['id'] . '">';
    $h .= '<div class="chips">';
    if ($a['nivel'] === 'importante') {
        $h .= '<span class="chip oscuro">Importante</span>';
    }
    $h .= '<span class="chip">Para: ' . h(aviso_audiencia_texto($a['audiencia'])) . '</span></div>';
    $h .= '<h3>' . h($a['titulo']) . '</h3><p>' . h($a['cuerpo']) . '</p>';
    $h .= '<div class="lectura"><strong data-leyeron>' . $leyeron . ' de ' . $dest . ($dest === 1 ? ' persona lo leyó' : ' personas lo leyeron') . ' (' . $pct . '%)</strong>'
        . '<div class="lectura-barra" role="img" aria-label="' . $pct . '% de la audiencia lo leyó"><span style="width:' . $pct . '%"></span></div></div>';
    $h .= '<span class="meta">Publicado: ' . h(aviso_hora_texto($a['publicada_en'])) . ' (hora del evento)'
        . ($a['autor'] ? ' · ' . h($a['autor']) : '') . '</span>';
    if ($conAcciones) {
        $h .= '<div class="acciones"><a class="btn-app sec peligro pequeno-btn" href="avisos.php?retirar=' . (int) $a['id'] . '">Retirar…</a></div>';
    }
    return $h . '</article>';
}

$titulo = 'Avisos';
$activa = '';
$volver = 'index.php';
require __DIR__ . '/../../includes/app_header.php';
?>

<h1>Avisos y notificaciones</h1>

<?php if (!$tablasOk): ?>
  <div class="alerta error" role="alert">Faltan las tablas de avisos (importa <code>db/migraciones/007_notificaciones.sql</code>).</div>
<?php else: ?>

<?php if ($ok): ?><div class="alerta ok" role="status"><?= h($ok) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta error" role="alert"><?= h($error) ?></div><?php endif; ?>

<?php if ($retirar !== null): ?>

  <section class="confirmar-retiro" aria-labelledby="h-retirar">
    <h2 id="h-retirar">¿Retirar este aviso?</h2>
    <p class="pequeno">Dejará de mostrarse a todas las personas y ya no contará como no leído. No se borra: se conserva cuántas personas lo leyeron.</p>
    <?= aviso_admin_tarjeta($retirar, false) ?>
    <form method="post" action="avisos.php" class="acciones" data-una-vez>
      <?= csrf_campo() ?>
      <?= form_campo('aviso_retirar_' . (int) $retirar['id']) ?>
      <input type="hidden" name="accion" value="retirar">
      <input type="hidden" name="id" value="<?= (int) $retirar['id'] ?>">
      <button class="btn-app ancho peligro" type="submit">Sí, retirar el aviso</button>
      <a class="btn-app sec ancho" href="avisos.php">Cancelar</a>
    </form>
  </section>

<?php else: ?>

  <section class="bloque" aria-labelledby="h-publicar">
    <h2 id="h-publicar">Publicar un aviso</h2>
    <form class="form-app" method="post" action="avisos.php" novalidate data-una-vez>
      <?= csrf_campo() ?>
      <?= form_campo('aviso_publicar') ?>
      <input type="hidden" name="accion" value="publicar">

      <div class="campo">
        <label for="titulo">Título</label>
        <input type="text" id="titulo" name="titulo" maxlength="<?= AVISO_TITULO_MAX ?>" value="<?= h($valores['titulo']) ?>"
               data-contador="<?= AVISO_TITULO_MAX ?>" aria-describedby="titulo-cont<?= isset($errores['titulo']) ? ' titulo-error' : '' ?>"
               <?= isset($errores['titulo']) ? 'aria-invalid="true"' : '' ?> autocomplete="off">
        <p class="contador" id="titulo-cont"><?= mb_strlen($valores['titulo']) ?>/<?= AVISO_TITULO_MAX ?></p>
        <?php if (isset($errores['titulo'])): ?><p class="campo-error" id="titulo-error"><?= h($errores['titulo']) ?></p><?php endif; ?>
      </div>

      <div class="campo">
        <label for="cuerpo">Mensaje</label>
        <textarea id="cuerpo" name="cuerpo" maxlength="<?= AVISO_CUERPO_MAX ?>" data-contador="<?= AVISO_CUERPO_MAX ?>"
                  aria-describedby="cuerpo-cont<?= isset($errores['cuerpo']) ? ' cuerpo-error' : '' ?>"
                  <?= isset($errores['cuerpo']) ? 'aria-invalid="true"' : '' ?>><?= h($valores['cuerpo']) ?></textarea>
        <p class="contador" id="cuerpo-cont"><?= mb_strlen($valores['cuerpo']) ?>/<?= AVISO_CUERPO_MAX ?></p>
        <?php if (isset($errores['cuerpo'])): ?><p class="campo-error" id="cuerpo-error"><?= h($errores['cuerpo']) ?></p><?php endif; ?>
      </div>

      <div class="fila-campos">
        <div class="campo">
          <label for="audiencia">Dirigido a</label>
          <select id="audiencia" name="audiencia" <?= isset($errores['audiencia']) ? 'aria-invalid="true" aria-describedby="audiencia-error"' : '' ?>>
            <?php foreach (avisos_audiencias() as $clave => $texto): ?>
              <option value="<?= h($clave) ?>" <?= $valores['audiencia'] === $clave ? 'selected' : '' ?>><?= h($texto) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errores['audiencia'])): ?><p class="campo-error" id="audiencia-error"><?= h($errores['audiencia']) ?></p><?php endif; ?>
        </div>
        <div class="campo">
          <label for="nivel">Nivel</label>
          <select id="nivel" name="nivel" <?= isset($errores['nivel']) ? 'aria-invalid="true" aria-describedby="nivel-error"' : '' ?>>
            <option value="info" <?= $valores['nivel'] === 'info' ? 'selected' : '' ?>>Informativo</option>
            <option value="importante" <?= $valores['nivel'] === 'importante' ? 'selected' : '' ?>>Importante</option>
          </select>
          <?php if (isset($errores['nivel'])): ?><p class="campo-error" id="nivel-error"><?= h($errores['nivel']) ?></p><?php endif; ?>
        </div>
      </div>

      <button class="btn-app ancho" type="submit">Publicar aviso</button>
      <p class="pequeno suave">Se publica con la hora del evento (<?= h(fecha_larga(ahora_evento())) ?>, <?= h(hora_corta(ahora_evento())) ?>). Aparece en la app de cada persona en unos segundos, sin recargar.</p>
    </form>
  </section>

  <section class="bloque" aria-labelledby="h-publicados">
    <h2 id="h-publicados">Publicados (<?= count($publicados) ?>)</h2>
    <?php if (!$publicados): ?>
      <p class="vacio-lista">Todavía no hay avisos publicados.</p>
    <?php else: ?>
      <div class="lista">
        <?php foreach ($publicados as $a): ?><?= aviso_admin_tarjeta($a, true) ?><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($retirados): ?>
  <section class="bloque">
    <details class="demo-cuentas">
      <summary>Retirados (<?= count($retirados) ?>)</summary>
      <div class="lista" style="margin-top:0.7rem">
        <?php foreach ($retirados as $a): ?><?= aviso_admin_tarjeta($a, false) ?><?php endforeach; ?>
      </div>
    </details>
  </section>
  <?php endif; ?>

<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/app_footer.php'; ?>
