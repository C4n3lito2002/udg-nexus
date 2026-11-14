<?php
require_once __DIR__ . '/../includes/app.php';

$volver = ruta_segura($_GET['volver'] ?? $_POST['volver'] ?? null);

if (usuario_actual()) {
    header('Location: ' . ($volver ?? 'inicio.php'));
    exit;
}

$error = '';
$correo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $error = intentar_login($correo, (string) ($_POST['clave'] ?? '')) ?? '';
    if ($error === '') {
        $rol = consulta_uno('SELECT rol FROM usuarios WHERE id = ?', 'i', [(int) $_SESSION['uid']])['rol'] ?? '';
        header('Location: ' . ($volver ?? ($rol === 'admin' ? 'admin/index.php' : 'inicio.php')));
        exit;
    }
}

$cuentasDemo = consulta(
    "SELECT nombre, correo, rol FROM usuarios WHERE correo LIKE '%@udgnexus.test'
     ORDER BY FIELD(rol,'admin','staff','expositor','inversionista','corporativo','estudiante')"
);

$titulo = 'Iniciar sesión';
$activa = '';
require __DIR__ . '/../includes/app_header.php';
?>

<h1>Iniciar sesión</h1>
<p class="suave">Entra para armar tu agenda, ver tu gafete y recibir avisos.</p>

<?php if ($error): ?>
  <div class="alerta error" role="alert"><?= h($error) ?></div>
<?php endif; ?>

<form class="form-app" method="post" action="login.php" novalidate>
  <?= csrf_campo() ?>
  <?php if ($volver): ?><input type="hidden" name="volver" value="<?= h($volver) ?>"><?php endif; ?>
  <div class="campo">
    <label for="correo">Correo electrónico</label>
    <input type="email" id="correo" name="correo" value="<?= h($correo) ?>" autocomplete="email" required>
  </div>
  <div class="campo">
    <label for="clave">Contraseña</label>
    <input type="password" id="clave" name="clave" autocomplete="current-password" required>
  </div>
  <button class="btn-app ancho" type="submit">Entrar</button>
</form>

<p class="centrado">¿Aún no tienes cuenta? <a href="registro_app.php">Crear cuenta</a></p>

<?php if ($cuentasDemo): ?>
<details class="demo-cuentas">
  <summary>Cuentas de demostración</summary>
  <p class="pequeno suave">Contraseña de todas: <strong>nexus2027</strong>. Toca una para llenar el correo.</p>
  <ul>
    <?php foreach ($cuentasDemo as $c): ?>
      <li><button type="button" data-demo-correo="<?= h($c['correo']) ?>">
        <strong><?= h(ROLES[$c['rol']] ?? $c['rol']) ?></strong>
        <span><?= h($c['correo']) ?></span>
      </button></li>
    <?php endforeach; ?>
  </ul>
</details>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
