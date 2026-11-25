<?php
/** Cierre de la app móvil: barra de pestañas inferior + scripts. */
$activa = $activa ?? '';
$_app = prefijo_app();
$tabs = [
    'inicio'   => ['Inicio',   'inicio.php'],
    'catalogo' => ['Catálogo', 'catalogo.php'],
    'agenda'   => ['Agenda',   'agenda.php'],
    'mapa'     => ['Mapa',     'mapa.php'],
    'gafete'   => ['Gafete',   'gafete.php'],
];
?>
<p class="nota-simulacion">Proyecto académico de simulación. Las marcas se mencionan como propuesta de alianza y no implican patrocinio confirmado.</p>
</main>

<nav class="tabbar" aria-label="Navegación de la app">
  <?php foreach ($tabs as $clave => [$etiqueta, $url]): ?>
    <a href="<?= h($_app . $url) ?>" <?= $activa === $clave ? 'aria-current="page"' : '' ?>>
      <?= icono($clave) ?><span><?= h($etiqueta) ?></span>
      <?php if ($clave === 'agenda'): ?><b class="tab-badge" data-badge-agenda hidden></b><?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>

</div>
<script src="<?= h(prefijo_raiz()) ?>assets/js/app.js"></script>
<?php foreach (($scripts ?? []) as $_js): ?><script src="<?= h(prefijo_raiz() . $_js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
