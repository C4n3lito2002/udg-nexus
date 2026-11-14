// Menú móvil
document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');

  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  // Validación simple en formularios: resalta campos vacíos requeridos
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var valido = true;
      form.querySelectorAll('[required]').forEach(function (campo) {
        if (!campo.value.trim()) {
          valido = false;
          campo.style.borderBottomColor = '#a33b3b';
        } else {
          campo.style.borderBottomColor = '';
        }
      });
      if (!valido) {
        e.preventDefault();
      }
    });
  });
});
