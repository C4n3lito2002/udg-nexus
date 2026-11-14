<?php
/**
 * Cabecera de la app móvil.
 * Variables (opcionales) antes del include:
 *   $titulo   -> título de la pestaña
 *   $activa   -> inicio | catalogo | agenda | mapa | gafete
 *   $volver   -> URL del botón "volver" (si se omite, muestra el logo)
 */
$titulo = $titulo ?? 'UDG NEXUS App';
$activa = $activa ?? '';
$volver = $volver ?? null;
$_app  = prefijo_app();    // '' en /app, '../' en /app/admin
$_raiz = prefijo_raiz();   // hacia la raíz del sitio
$_usr  = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= h($titulo) ?> — UDG NEXUS</title>
<meta name="theme-color" content="#140f53">
<?php if ($_usr): ?><meta name="csrf" content="<?= h(csrf_token()) ?>">
<?php endif; ?>
<link rel="preload" href="<?= h($_raiz) ?>assets/fonts/poppins-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= h($_raiz) ?>assets/fonts/poppins-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= h($_raiz) ?>assets/css/tokens.css">
<link rel="stylesheet" href="<?= h($_raiz) ?>assets/css/app.css">
</head>
<body class="app" data-api="<?= h($_app) ?>api/" data-app="<?= h($_app) ?>" data-sesion="<?= $_usr ? '1' : '0' ?>">
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div class="app-shell">

<header class="app-bar">
  <?php if ($volver): ?>
    <a class="app-back" href="<?= h($volver) ?>" aria-label="Volver"><?= icono('volver') ?></a>
  <?php endif; ?>
  <a class="app-logo" href="<?= h($_app) ?>inicio.php" aria-label="UDG NEXUS — Inicio">
    <img src="<?= h($_raiz) ?>assets/img/logo-udg-nexus.png" width="505" height="283" alt="UDG NEXUS">
  </a>
  <a class="app-sitio" href="<?= h($_raiz) ?>index.php"><?= icono('sitio') ?><span>Sitio</span></a>
  <?php if ($_usr): ?>
    <a class="app-usuario" href="<?= h($_app) ?>perfil.php" aria-label="Mi cuenta: <?= h($_usr['nombre']) ?>"><?= h(iniciales($_usr['nombre'])) ?></a>
  <?php else: ?>
    <a class="app-entrar" href="<?= h($_app) ?>login.php"><?= icono('usuario') ?><span>Entrar</span></a>
  <?php endif; ?>
</header>

<main id="contenido" class="app-main">
