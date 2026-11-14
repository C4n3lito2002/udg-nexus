/* ============================================================
   UDG NEXUS — Registro: al cambiar el rol se sugiere el boleto que le corresponde
   (solo mientras la persona no haya elegido un boleto a mano).
   ============================================================ */
(function () {
  "use strict";
  var rol = document.getElementById("rol");
  var grupo = document.querySelector("[data-tipos]");
  if (!rol || !grupo) return;

  var tocado = false;
  grupo.addEventListener("change", function () { tocado = true; });
  rol.addEventListener("change", function () {
    if (tocado) return;
    var radios = grupo.querySelectorAll('input[type="radio"]');
    for (var i = 0; i < radios.length; i++) {
      var roles = (radios[i].getAttribute("data-roles") || "").split(",");
      if (!radios[i].disabled && roles.indexOf(rol.value) !== -1) {
        radios[i].checked = true;
        return;
      }
    }
  });
})();
