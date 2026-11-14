<?php
require_once __DIR__ . '/../includes/app.php';
requiere_login();

$u = usuario_actual();
$nombresAreas = [];
$porSlug = array_column(areas_todas(), 'nombre', 'slug');
foreach (array_filter(explode(',', (string) $u['intereses'])) as $slug) {
    if (isset($porSlug[$slug])) {
        $nombresAreas[] = $porSlug[$slug];
    }
}

$titulo = 'Mi cuenta';
$activa = '';
require __DIR__ . '/../includes/app_header.php';
?>

<section class="perfil-cab">
  <span class="avatar grande" aria-hidden="true"><?= h(iniciales($u['nombre'])) ?></span>
  <div>
    <h1><?= h($u['nombre']) ?></h1>
    <span class="chip oscuro"><?= h(ROLES[$u['rol']] ?? $u['rol']) ?></span>
  </div>
</section>

<section class="bloque">
  <h2>Mis datos</h2>
  <dl class="datos">
    <div><dt>Correo</dt><dd><?= h($u['correo']) ?></dd></div>
    <?php if ($u['institucion'] !== ''): ?>
      <div><dt>Institución</dt><dd><?= h($u['institucion']) ?></dd></div>
    <?php endif; ?>
    <div><dt>Intereses</dt><dd><?= $nombresAreas ? h(implode(', ', $nombresAreas)) : 'Sin ejes elegidos' ?></dd></div>
  </dl>
</section>

<?php if (boletos_tablas_existen()): $bol = boleto_de_usuario((int) $u['id']); ?>
<section class="bloque">
  <h2>Mi boleto</h2>
  <?php if ($bol): ?>
    <dl class="datos">
      <div><dt>Boleto</dt><dd><?= h($bol['tipo']) ?></dd></div>
      <div><dt>Folio</dt><dd><?= h(boleto_folio((int) $bol['id'])) ?></dd></div>
      <div><dt>Estado</dt><dd><?= chip_estatus_boleto($bol['estatus']) ?></dd></div>
    </dl>
    <a class="btn-app ancho" href="gafete.php" style="margin-top:0.8rem">Ver mi gafete</a>
  <?php elseif (tiene_rol('staff', 'admin')): ?>
    <p class="suave">El staff no necesita boleto.</p>
  <?php else: ?>
    <p class="suave">Aún no tienes boleto.</p>
    <a class="btn-app ancho" href="boletos.php">Elegir mi boleto</a>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if (tiene_rol('staff', 'admin')): ?>
<section class="bloque">
  <h2>Control de acceso</h2>
  <a class="btn-app ancho" href="admin/escaner.php">Abrir el escáner de acceso</a>
</section>
<?php endif; ?>

<?php if (tiene_rol('admin')): ?>
<section class="bloque">
  <h2>Administración</h2>
  <a class="btn-app ancho" href="admin/index.php">Abrir panel de administración</a>
</section>
<?php endif; ?>

<form class="bloque" method="post" action="logout.php">
  <?= csrf_campo() ?>
  <button class="btn-app sec ancho" type="submit"><?= icono('salir') ?> Cerrar sesión</button>
</form>

<?php require __DIR__ . '/../includes/app_footer.php'; ?>
