<?php
$pageTitle = 'Registro, boleto y citas B2B — UDG NEXUS';
$activePage = 'registro.php';
require_once 'config.php';
require_once 'includes/forms.php';   // sesión + token de un solo uso (antes de imprimir HTML)
require_once 'includes/header.php';

$okCita = isset($_GET['cita']) && $_GET['cita'] === 'ok';
$errCita = isset($_GET['cita']) && $_GET['cita'] === 'error';
$expCita = isset($_GET['cita']) && $_GET['cita'] === 'expirado';
?>

<header class="hero" style="padding-bottom: 3rem;">
  <div class="wrap">
    <span class="hero-kicker">Participación</span>
    <h1 style="font-size: clamp(1.9rem, 4vw, 2.6rem);">Regístrate y agenda tu cita B2B</h1>
    <p class="lede">Regístrate en la app para recibir tu boleto con gafete QR y, si vienes a negociar, reserva un espacio en el Patent Room o en el Due Diligence Desk.</p>
  </div>
</header>

<section id="acreditacion">
  <div class="wrap">
    <div class="section-head">
      <span class="idx">6.6</span>
      <h2>Registro y boleto con gafete QR</h2>
    </div>
    <p class="section-intro">El registro se hace en la app de UDG NEXUS: creas tu cuenta, eliges tu boleto (hay opciones sin costo y otras con pago simulado) y recibes tu gafete con código QR para entrar al evento y armar tu agenda.</p>
    <div class="cta-registro">
      <a class="btn btn-brass" href="app/registro_app.php">Crear cuenta y obtener mi boleto</a>
      <a class="btn btn-ink" href="app/boletos.php">Ver los tipos de boleto</a>
    </div>
    <p class="form-note">¿Ya tienes cuenta? <a href="app/login.php">Inicia sesión</a> para ver tu gafete.</p>
  </div>
</section>

<section class="bg-alt" id="citas">
  <div class="wrap">
    <div class="section-head">
      <span class="idx">5.1</span>
      <h2>Solicitar cita 1:1 (Patent Room / Due Diligence Desk)</h2>
    </div>
    <p class="section-intro">Reservada para expositores y compradores que ya tienen gafete. Las citas se confirman por correo con horario definitivo.</p>

    <?php if ($okCita): ?>
      <div class="alert">Tu solicitud de cita se envió correctamente. El comité de Experiencia del Asistente te confirmará el horario.</div>
    <?php elseif ($errCita): ?>
      <div class="alert error">No pudimos registrar tu solicitud de cita. Revisa los campos obligatorios e inténtalo de nuevo.</div>
    <?php elseif ($expCita): ?>
      <div class="alert error">El formulario expiró o ya se había enviado. Recarga la página e inténtalo de nuevo.</div>
    <?php endif; ?>

    <form action="procesar_cita.php" method="post" data-validate novalidate>
      <?= form_campo('cita_b2b') ?>
      <div class="form-grid">
        <div class="field">
          <label for="c_nombre">Nombre completo *</label>
          <input type="text" id="c_nombre" name="nombre_completo" required>
        </div>
        <div class="field">
          <label for="c_correo">Correo electrónico *</label>
          <input type="email" id="c_correo" name="correo" required>
        </div>
        <div class="field">
          <label for="c_institucion">Institución / Empresa *</label>
          <input type="text" id="c_institucion" name="institucion_empresa" required>
        </div>
        <div class="field">
          <label for="c_tipo">Vienes como *</label>
          <select id="c_tipo" name="tipo_asistente" required>
            <option value="">Selecciona una opción</option>
            <option value="Comprador/Inversionista">Comprador / Inversionista</option>
            <option value="Expositor/Prototipador">Expositor / Prototipador</option>
          </select>
        </div>
        <div class="field">
          <label for="c_tema">Tema de interés</label>
          <input type="text" id="c_tema" name="tema_interes" placeholder="Ej. licenciamiento, piloto, co-desarrollo">
        </div>
        <div class="field">
          <label for="c_horario">Horario preferido</label>
          <input type="text" id="c_horario" name="horario_preferido" placeholder="Ej. Día 1, bloque matutino">
        </div>
        <div class="field">
          <label for="c_nda">¿Requiere NDA previo?</label>
          <select id="c_nda" name="requiere_nda">
            <option value="No">No</option>
            <option value="Sí">Sí</option>
          </select>
        </div>
        <div class="field full">
          <label for="c_mensaje">Mensaje para el comité</label>
          <textarea id="c_mensaje" name="mensaje"></textarea>
        </div>
      </div>
      <button type="submit" class="btn btn-ink mt-lg">Solicitar cita</button>
    </form>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
