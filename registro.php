<?php
$pageTitle = 'Registro, acreditación y citas B2B — UDG NEXUS';
$activePage = 'registro.php';
require_once 'config.php';
require_once 'includes/forms.php';   // sesión + token de un solo uso (antes de imprimir HTML)
require_once 'includes/header.php';

$okAcred = isset($_GET['acred']) && $_GET['acred'] === 'ok';
$errAcred = isset($_GET['acred']) && $_GET['acred'] === 'error';
$okCita = isset($_GET['cita']) && $_GET['cita'] === 'ok';
$errCita = isset($_GET['cita']) && $_GET['cita'] === 'error';
$expAcred = isset($_GET['acred']) && $_GET['acred'] === 'expirado';
$expCita = isset($_GET['cita']) && $_GET['cita'] === 'expirado';
?>

<header class="hero" style="padding-bottom: 3rem;">
  <div class="wrap">
    <span class="hero-kicker">Participación</span>
    <h1 style="font-size: clamp(1.9rem, 4vw, 2.6rem);">Regístrate y agenda tu cita B2B</h1>
    <p class="lede">Elige tu tipo de gafete y, si vienes a negociar, reserva un espacio en el Patent Room o en el Due Diligence Desk.</p>
  </div>
</header>

<section id="acreditacion">
  <div class="wrap">
    <div class="section-head">
      <span class="idx">6.6</span>
      <h2>Acreditación</h2>
    </div>

    <?php if ($okAcred): ?>
      <div class="alert">Tu registro se guardó correctamente. Te contactaremos con los detalles de tu gafete antes del evento.</div>
    <?php elseif ($errAcred): ?>
      <div class="alert error">No pudimos guardar tu registro. Revisa que todos los campos obligatorios estén completos e inténtalo de nuevo.</div>
    <?php elseif ($expAcred): ?>
      <div class="alert error">El formulario expiró o ya se había enviado. Recarga la página e inténtalo de nuevo.</div>
    <?php endif; ?>

    <form action="procesar_acreditacion.php" method="post" data-validate novalidate>
      <?= form_campo('acreditacion') ?>
      <div class="form-grid">
        <div class="field">
          <label for="nombre_completo">Nombre completo *</label>
          <input type="text" id="nombre_completo" name="nombre_completo" required>
        </div>
        <div class="field">
          <label for="correo">Correo electrónico *</label>
          <input type="email" id="correo" name="correo" required>
        </div>
        <div class="field">
          <label for="telefono">Teléfono</label>
          <input type="tel" id="telefono" name="telefono">
        </div>
        <div class="field">
          <label for="institucion_empresa">Institución / Empresa</label>
          <input type="text" id="institucion_empresa" name="institucion_empresa">
        </div>
        <div class="field">
          <label for="tipo_gafete">Tipo de gafete *</label>
          <select id="tipo_gafete" name="tipo_gafete" required>
            <option value="">Selecciona una opción</option>
            <option value="Inversionista/Comprador">Inversionista / Comprador</option>
            <option value="Expositor/Investigador">Expositor / Investigador</option>
            <option value="Prensa">Prensa</option>
            <option value="VIP">VIP</option>
            <option value="Staff">Staff</option>
            <option value="Estudiante/Público general">Estudiante / Público general</option>
          </select>
        </div>
        <div class="field">
          <label for="eje_tematico">Eje temático de interés</label>
          <input type="text" id="eje_tematico" name="eje_tematico" placeholder="Salud, Agro, Manufactura, Energía...">
        </div>
        <div class="field full">
          <label for="comentarios">Comentarios</label>
          <textarea id="comentarios" name="comentarios"></textarea>
        </div>
      </div>
      <button type="submit" class="btn btn-brass mt-lg">Completar registro</button>
      <p class="form-note">Tus datos se usan únicamente para la acreditación y comunicación del evento.</p>
    </form>
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
