/* ============================================================
   UDG NEXUS — Gafete: dibuja el QR del boleto en el navegador.
   Usa la copia local assets/js/vendor/qrcode.js (sin CDN: funciona sin internet).
   El código contiene SOLO el token del boleto, ningún dato personal.
   ============================================================ */
(function () {
  "use strict";

  var NS = "http://www.w3.org/2000/svg";

  /** Devuelve un <svg> con el QR de `texto` (módulos en currentColor sobre fondo blanco). */
  function crearQR(texto) {
    var qr = qrcode(0, "M");          // tamaño automático, corrección de errores media
    qr.addData(texto, "Byte");
    qr.make();

    var n = qr.getModuleCount();
    var margen = 4;                    // zona tranquila que pide el estándar
    var tam = n + margen * 2;
    var trazo = "";
    for (var f = 0; f < n; f++) {
      for (var c = 0; c < n; c++) {
        if (qr.isDark(f, c)) trazo += "M" + (c + margen) + " " + (f + margen) + "h1v1h-1z";
      }
    }

    var svg = document.createElementNS(NS, "svg");
    svg.setAttribute("viewBox", "0 0 " + tam + " " + tam);
    svg.setAttribute("shape-rendering", "crispEdges");
    svg.setAttribute("aria-hidden", "true");
    var fondo = document.createElementNS(NS, "rect");
    fondo.setAttribute("width", tam);
    fondo.setAttribute("height", tam);
    fondo.setAttribute("fill", "#ffffff");
    var modulos = document.createElementNS(NS, "path");
    modulos.setAttribute("d", trazo);
    modulos.setAttribute("fill", "currentColor");
    svg.appendChild(fondo);
    svg.appendChild(modulos);
    return svg;
  }

  function pintar(contenedor, texto) {
    if (!contenedor || !texto) return;
    try {
      contenedor.insertBefore(crearQR(texto), contenedor.firstChild);
    } catch (e) {
      var aviso = document.createElement("p");
      aviso.className = "suave pequeno";
      aviso.textContent = "No se pudo generar el código QR. Usa el código de respaldo.";
      contenedor.appendChild(aviso);
    }
  }

  if (typeof qrcode !== "function") {
    var faltante = document.querySelector("[data-qr]");
    if (faltante) {
      var p = document.createElement("p");
      p.className = "suave pequeno";
      p.textContent = "No se pudo cargar el generador de QR. Usa el código de respaldo.";
      faltante.appendChild(p);
    }
    return;
  }

  var normal = document.querySelector("[data-qr]");
  if (normal) pintar(normal, normal.getAttribute("data-qr"));

  /* ---------- Ampliar a pantalla completa para facilitar la lectura ---------- */
  var abrir = document.querySelector("[data-qr-ampliar]");
  var pantalla = document.querySelector("[data-qr-pantalla]");
  if (!abrir || !pantalla) return;
  var grande = pantalla.querySelector("[data-qr-grande]");
  var cerrar = pantalla.querySelector("[data-qr-cerrar]");
  var dibujado = false;

  function mostrar() {
    if (!dibujado) {
      pintar(grande, grande.getAttribute("data-qr-grande"));
      dibujado = true;
    }
    pantalla.hidden = false;
    cerrar.focus();
  }
  function ocultar() {
    pantalla.hidden = true;
    abrir.focus();
  }
  abrir.addEventListener("click", mostrar);
  cerrar.addEventListener("click", ocultar);
  pantalla.addEventListener("click", function (e) { if (e.target === pantalla) ocultar(); });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !pantalla.hidden) ocultar();
  });
})();
