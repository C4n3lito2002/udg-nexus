<?php
require_once __DIR__ . '/../includes/app.php';

if (usuario_actual()) {
    header('Location: perfil.php');
    exit;
}

$areas = areas_todas();
$slugsValidos = array_column($areas, 'slug');
$errores = [];
$v = ['nombre' => '', 'correo' => '', 'rol' => 'estudiante', 'institucion' => '', 'intereses' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $v['nombre']      = trim((string) ($_POST['nombre'] ?? ''));
    $v['correo']      = trim((string) ($_POST['correo'] ?? ''));
    $v['rol']         = (string) ($_POST['rol'] ?? '');
    $v['institucion'] = trim((string) ($_POST['institucion'] ?? ''));
    $v['intereses']   = array_values(array_intersect($slugsValidos, (array) ($_POST['intereses'] ?? [])));
    $clave  = (string) ($_POST['clave'] ?? '');
    $clave2 = (string) ($_POST['clave2'] ?? '');

    if ($v['nombre'] === '' || mb_strlen($v['nombre']) > 120) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 120 caracteres).';
    }
    if (!filter_var($v['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($v['correo']) > 150) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (!in_array($v['rol'], ROLES_PUBLICOS, true)) {
        $errores['rol'] = 'Elige cómo vas a participar.';
    }
    if (mb_strlen($v['institucion']) > 150) {
        $errores['institucion'] = 'Máximo 150 caracteres.';
    }
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }
    if (!isset($errores['correo']) && consulta_uno('SELECT id FROM usuarios WHERE correo = ?', 's', [$v['correo']])) {
        $errores['correo'] = 'Ese correo ya tiene una cuenta. Inicia sesión.';
    }

    if (!$errores) {
        $ok = ejecutar(
            'INSERT INTO usuarios (nombre, correo, password_hash, rol, institucion, intereses) VALUES (?, ?, ?, ?, ?, ?)',
            'ssssss',
            [$v['nombre'], $v['correo'], password_hash($clave, PASSWORD_DEFAULT), $v['rol'], $v['institucion'], implode(',', $v['intereses'])]
        );
        if ($ok) {
            iniciar_sesion_usuario((int) db()->insert_id);
            header('Location: inicio.php');
            exit;
        }
        $errores['general'] = 'No pudimos crear tu cuenta. Inténtalo de nuevo en un momento.';
    }
}

/** Mensaje de error de un campo (con id para aria-describedby). */
function error_campo(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? '<p class="campo-error" id="e-' . h($campo) . '">' . h($errores[$campo]) . '</p>' : '';
}
function desc(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' aria-invalid="true" aria-describedby="e-' . h($campo) . '"' : '';
}

$titulo = 'Crear cuenta';
$activa = '';
$volver = 'login.php';
require __DIR__ . '/../includes/app_header.php';
?>

<h1>Crear cuenta</h1>
<p class="suave">Con tu cuenta guardas tu agenda y recibes recomendaciones según tus intereses.</p>

<?php if (isset($errores['general'])): ?>
  <div class="alerta error" role="alert"><?= h($errores['general']) ?></div>
<?php elseif ($errores): ?>
  <div class="alerta error" role="alert">Revisa los campos marcados.</div>
<?php endif; ?>

<form class="form-app" method="post" action="registro_app.php" novalidate>
  <?= csrf_campo() ?>

  <div class="campo">
    <label for="nombre">Nombre completo</label>
    <input type="text" id="nombre" name="nombre" value="<?= h($v['nombre']) ?>" autocomplete="name" required<?= desc($errores, 'nombre') ?>>
    <?= error_campo($errores, 'nombre') ?>
  </div>

  <div class="campo">
    <label for="correo">Correo electrónico</label>
    <input type="email" id="correo" name="correo" value="<?= h($v['correo']) ?>" autocomplete="email" required<?= desc($errores, 'correo') ?>>
    <?= error_campo($errores, 'correo') ?>
  </div>

  <div class="campo">
    <label for="rol">¿Cómo vas a participar?</label>
    <select id="rol" name="rol" required<?= desc($errores, 'rol') ?>>
      <?php foreach (ROLES_PUBLICOS as $r): ?>
        <option value="<?= h($r) ?>" <?= $v['rol'] === $r ? 'selected' : '' ?>><?= h(ROLES[$r]) ?></option>
      <?php endforeach; ?>
    </select>
    <?= error_campo($errores, 'rol') ?>
  </div>

  <div class="campo">
    <label for="institucion">Institución o empresa <span class="suave">(opcional)</span></label>
    <input type="text" id="institucion" name="institucion" value="<?= h($v['institucion']) ?>" autocomplete="organization"<?= desc($errores, 'institucion') ?>>
    <?= error_campo($errores, 'institucion') ?>
  </div>

  <fieldset class="campo grupo-checks">
    <legend>Ejes de tu interés <span class="suave">(opcional)</span></legend>
    <?php foreach ($areas as $a): ?>
      <label class="check-chip">
        <input type="checkbox" name="intereses[]" value="<?= h($a['slug']) ?>" <?= in_array($a['slug'], $v['intereses'], true) ? 'checked' : '' ?>>
        <span><?= h($a['nombre']) ?></span>
      </label>
    <?php endforeach; ?>
  </fieldset>

  <div class="campo">
    <label for="clave">Contraseña <span class="suave">(mínimo 8 caracteres)</span></label>
    <input type="password" id="clave" name="clave" autocomplete="new-password" required<?= desc($errores, 'clave') ?>>
    <?= error_campo($errores, 'clave') ?>
  </div>

  <div class="campo">
    <label for="clave2">Repite la contraseña</label>
    <input type="password" id="clave2" name="clave2" autocomplete="new-password" required<?= desc($errores, 'clave2') ?>>
    <?= error_campo($errores, 'clave2') ?>
  </div>

  <button class="btn-app ancho" type="submit">Crear cuenta</button>
</form>

<p class="centrado">¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
