<?php
/**
 * Header común: <head> + nav.
 * Variables esperadas (opcionales) antes del include:
 *   $pageTitle   -> título de la pestaña
 *   $pageDesc    -> meta description
 *   $activePage  -> nombre del archivo actual, para resaltar el nav
 *
 * Abre <main id="contenido">; includes/footer.php lo cierra.
 */
$pageTitle = $pageTitle ?? 'UDG NEXUS';
$pageDesc  = $pageDesc  ?? 'UDG NEXUS — Donde el prototipo universitario se convierte en licencia, inversión y solución real para la industria.';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta name="theme-color" content="#140f53">
<link rel="preload" href="assets/fonts/poppins-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/poppins-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/tokens.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<nav class="site-nav" aria-label="Principal">
  <div class="wrap">
    <a href="index.php" class="brand" aria-label="UDG NEXUS — Inicio">
      <img class="brand-logo" src="assets/img/logo-udg-nexus.png" width="505" height="283" alt="UDG NEXUS">
    </a>
    <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks">Menú</button>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php" <?= $activePage === 'index.php' ? 'aria-current="page"' : '' ?>>La idea</a></li>
      <li><a href="experiencia.php" <?= $activePage === 'experiencia.php' ? 'aria-current="page"' : '' ?>>Experiencia y comité</a></li>
      <li><a href="patrocinadores.php" <?= $activePage === 'patrocinadores.php' ? 'aria-current="page"' : '' ?>>Patrocinadores</a></li>
      <li><a href="registro.php" <?= $activePage === 'registro.php' ? 'aria-current="page"' : '' ?>>Registro y citas</a></li>
      <li><a href="contacto.php" <?= $activePage === 'contacto.php' ? 'aria-current="page"' : '' ?>>Contacto</a></li>
      <li><a href="app/inicio.php" class="nav-app">Ver la app</a></li>
    </ul>
  </div>
</nav>

<main id="contenido">
