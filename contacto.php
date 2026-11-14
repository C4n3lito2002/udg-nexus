<?php
$pageTitle = 'Contacto y patrocinios — UDG NEXUS';
$activePage = 'contacto.php';
require_once 'config.php';
require_once 'includes/header.php';

$ok = isset($_GET['estado']) && $_GET['estado'] === 'ok';
$err = isset($_GET['estado']) && $_GET['estado'] === 'error';
?>

<header class="hero" style="padding-bottom: 3rem;">
  <div class="wrap">
    <span class="hero-kicker">Vinculación tecnológica</span>
    <h1 style="font-size: clamp(1.9rem, 4vw, 2.6rem);">Contacto y patrocinios</h1>
    <p class="lede">¿Tu empresa quiere patrocinar, cubrir el evento como prensa, o necesitas el dossier comercial? Escríbenos.</p>
  </div>
</header>

<section>
  <div class="wrap grid-2">
    <div>
      <div class="section-head" style="border-bottom:none; margin-bottom:1.25rem;">
        <span class="idx">7.6</span>
        <h2>Escríbenos</h2>
      </div>

      <?php if ($ok): ?>
        <div class="alert">Tu mensaje se envió correctamente. El equipo de vinculación tecnológica te contactará pronto.</div>
      <?php elseif ($err): ?>
        <div class="alert error">No pudimos enviar tu mensaje. Revisa los campos obligatorios e inténtalo de nuevo.</div>
      <?php endif; ?>

      <form action="procesar_contacto.php" method="post" data-validate novalidate>
        <div class="form-grid">
          <div class="field full">
            <label for="nombre_completo">Nombre completo *</label>
            <input type="text" id="nombre_completo" name="nombre_completo" required>
          </div>
          <div class="field">
            <label for="correo">Correo electrónico *</label>
            <input type="email" id="correo" name="correo" required>
          </div>
          <div class="field">
            <label for="empresa">Empresa / Institución</label>
            <input type="text" id="empresa" name="empresa">
          </div>
          <div class="field full">
            <label for="asunto">Asunto *</label>
            <select id="asunto" name="asunto" required>
              <option value="Interés en patrocinio">Interés en patrocinio</option>
              <option value="Solicitud de Dossier Comercial">Solicitud de Dossier Comercial</option>
              <option value="Prensa y medios">Prensa y medios</option>
              <option value="Otro">Otro</option>
            </select>
          </div>
          <div class="field full">
            <label for="mensaje">Mensaje *</label>
            <textarea id="mensaje" name="mensaje" required></textarea>
          </div>
        </div>
        <button type="submit" class="btn btn-brass mt-lg">Enviar mensaje</button>
      </form>
    </div>

    <div>
      <div class="section-head" style="border-bottom:none; margin-bottom:1.25rem;">
        <span class="idx">—</span>
        <h2>Dirección de Vinculación Tecnológica</h2>
      </div>
      <div class="panel" style="margin-bottom: 1.5rem;">
        <span class="tag">sede</span>
        <h3>Plaza Bicentenario, CCU</h3>
        <p>Centro Cultural Universitario, Zapopan, Jalisco. Acceso vía Mi Macro Periférico, estación CCU / Cineteca Nacional.</p>
      </div>
      <div class="panel">
        <span class="tag">comité organizador</span>
        <h3>UDG NEXUS</h3>
        <p>Dirección General del Evento, en coordinación con la Coordinación General de Investigación, Posgrado y Vinculación (CGIPV) de la UdeG.</p>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
