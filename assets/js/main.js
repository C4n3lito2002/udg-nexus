/* ============================================================
   UDG NEXUS — Comportamiento común
   1. Menú móvil
   2. Validación de formularios [data-validate]
   ============================================================ */
(function () {
  "use strict";

  /* ---------- 1. Menú móvil ---------- */
  var toggle = document.getElementById("navToggle");
  var links = document.getElementById("navLinks");

  if (toggle && links) {
    toggle.addEventListener("click", function () {
      var abierto = links.classList.toggle("open");
      toggle.setAttribute("aria-expanded", abierto ? "true" : "false");
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && links.classList.contains("open")) {
        links.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.focus();
      }
    });
  }

  /* ---------- 2. Validación de formularios ---------- */
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function mensajeDe(campo) {
    var valor = (campo.value || "").trim();
    if (campo.hasAttribute("required") && valor === "") {
      return "Este campo es obligatorio.";
    }
    if (campo.type === "email" && valor !== "" && !EMAIL.test(valor)) {
      return "Escribe un correo válido (ej. nombre@empresa.com).";
    }
    return "";
  }

  function marcar(campo, mensaje) {
    var contenedor = campo.closest(".field");
    if (!contenedor) return;
    var previo = contenedor.querySelector(".field-error");
    if (previo) previo.remove();
    contenedor.classList.remove("invalid");
    campo.removeAttribute("aria-invalid");
    campo.removeAttribute("aria-describedby");

    if (mensaje) {
      var id = (campo.id || campo.name) + "-error";
      var p = document.createElement("p");
      p.className = "field-error";
      p.id = id;
      p.textContent = mensaje;
      contenedor.appendChild(p);
      contenedor.classList.add("invalid");
      campo.setAttribute("aria-invalid", "true");
      campo.setAttribute("aria-describedby", id);
    }
  }

  document.querySelectorAll("form[data-validate]").forEach(function (form) {
    var campos = form.querySelectorAll("input, select, textarea");

    campos.forEach(function (campo) {
      campo.addEventListener("blur", function () {
        if (campo.value.trim() !== "" || campo.classList.contains("tocado")) {
          marcar(campo, mensajeDe(campo));
        }
        campo.classList.add("tocado");
      });
      campo.addEventListener("input", function () {
        if (campo.closest(".field") && campo.closest(".field").classList.contains("invalid")) {
          marcar(campo, mensajeDe(campo));
        }
      });
    });

    form.addEventListener("submit", function (e) {
      var primero = null;
      campos.forEach(function (campo) {
        var msg = mensajeDe(campo);
        marcar(campo, msg);
        if (msg && !primero) primero = campo;
      });

      if (primero) {
        e.preventDefault();
        primero.focus();
        return;
      }

      /* Evita envíos duplicados por doble clic. */
      var boton = form.querySelector('button[type="submit"]');
      if (boton) {
        boton.disabled = true;
        boton.textContent = "Enviando…";
      }
    });
  });
})();
