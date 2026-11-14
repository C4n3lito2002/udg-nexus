<?php
/** Cierre de la app móvil: barra de pestañas inferior + scripts. */
$activa = $activa ?? '';
$_app = prefijo_app();
$tabs = [
    'inicio'   => ['Inicio',   'inicio.php'],
    'catalogo' => ['Catálogo', 'catalogo.php'],
    'agenda'   => ['Agenda',   'proximamente.php?m=agenda'],
    'mapa'     => ['Mapa',     'proximamente.php?m=mapa'],
    'gafete'   => ['Gafete',   'proximamente.php?m=gafete'],
];
?>
</main>

<nav class="tabbar" aria-label="Navegación de la app">
  <?php foreach ($tabs as $clave => [$etiqueta, $url]): ?>
    <a href="<?= h($_app . $url) ?>" <?= $activa === $clave ? 'aria-current="page"' : '' ?>>
      <?= icono($clave) ?><span><?= h($etiqueta) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

</div>
<script src="<?= h(prefijo_raiz()) ?>assets/js/app.js"></script>
</body>
</html>
