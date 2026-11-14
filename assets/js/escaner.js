/* ============================================================
   UDG NEXUS — Escáner de acceso (staff)
   Lee el QR con la cámara (BarcodeDetector si el navegador lo trae; si no, jsQR local)
   o recibe el código tecleado, y lo valida contra api/checkin.php.
   Cada boleto entra una sola vez: la validación real la hace el servidor.
   La cámara solo existe en localhost o con HTTPS; el campo de código siempre funciona.
   ============================================================ */
(function () {
  "use strict";

  var raiz = document.querySelector("[data-escaner]");
  if (!raiz) return;

  var api = (document.body.getAttribute("data-api") || "../api/") + "checkin.php";
  var meta = document.querySelector('meta[name="csrf"]');
  var csrf = meta ? meta.getAttribute("content") : "";
  var visor = raiz.querySelector("[data-visor]");
  var video = raiz.querySelector("[data-video]");
  var btnCamara = raiz.querySelector("[data-camara]");
  var estadoCamara = raiz.querySelector("[data-camara-estado]");
  var form = raiz.querySelector("[data-manual]");
  var entrada = form.querySelector("input");
  var panel = raiz.querySelector("[data-resultado]");
  var lista = raiz.querySelector("[data-lecturas]");
  var vacio = raiz.querySelector("[data-vacio]");

  var TITULOS = {
    entrada: "Acceso permitido",
    repetido: "Boleto ya utilizado",
    cancelado: "Boleto cancelado",
    invalido: "Código no válido",
    error: "No se pudo validar"
  };
  var ICONOS = { entrada: "ok", repetido: "aviso", cancelado: "aviso", invalido: "aviso", error: "aviso" };

  function crear(tag, clase, texto) {
    var el = document.createElement(tag);
    if (clase) el.className = clase;
    if (texto !== undefined) el.textContent = texto;
    return el;
  }

  /** Icono SVG sencillo (mismos trazos que includes/app.php). */
  function icono(tipo) {
    var trazos = {
      ok: ["circle:12,12,9", "path:m8 12.3 2.8 2.8L16 9.5"],
      aviso: ["path:M12 4 2.8 19.5h18.4z", "path:M12 10v4.5M12 17.2v.1"]
    }[tipo];
    var NS = "http://www.w3.org/2000/svg";
    var svg = document.createElementNS(NS, "svg");
    svg.setAttribute("class", "ico");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("fill", "none");
    svg.setAttribute("stroke", "currentColor");
    svg.setAttribute("stroke-width", "1.8");
    svg.setAttribute("stroke-linecap", "round");
    svg.setAttribute("stroke-linejoin", "round");
    svg.setAttribute("aria-hidden", "true");
    trazos.forEach(function (t) {
      var partes = t.split(":");
      var el;
      if (partes[0] === "circle") {
        var v = partes[1].split(",");
        el = document.createElementNS(NS, "circle");
        el.setAttribute("cx", v[0]); el.setAttribute("cy", v[1]); el.setAttribute("r", v[2]);
      } else {
        el = document.createElementNS(NS, "path");
        el.setAttribute("d", partes[1]);
      }
      svg.appendChild(el);
    });
    return svg;
  }

  function mostrar(clase, datos) {
    panel.className = "resultado " + clase;
    panel.textContent = "";
    var tit = crear("div", "tit");
    tit.appendChild(icono(ICONOS[clase] || "aviso"));
    tit.appendChild(crear("span", "", TITULOS[clase] || ""));
    panel.appendChild(tit);
    if (datos.nombre) panel.appendChild(crear("div", "nombre", datos.nombre));
    panel.appendChild(crear("p", "", datos.mensaje || ""));
    var detalle = [datos.tipo, datos.rol, datos.institucion, datos.folio].filter(Boolean).join(" · ");
    if (detalle) panel.appendChild(crear("p", "", detalle));
    panel.hidden = false;
  }

  function registrar(clase, datos) {
    if (vacio) vacio.hidden = true;
    var hora = new Date().toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    var li = crear("li", clase);
    li.appendChild(crear("strong", "", TITULOS[clase] || ""));
    li.appendChild(crear("span", "", datos.nombre ? datos.nombre + (datos.folio ? " · " + datos.folio : "") : (datos.mensaje || "")));
    li.appendChild(crear("small", "", hora));
    lista.insertBefore(li, lista.firstChild);
    while (lista.children.length > 8) lista.removeChild(lista.lastChild);
  }

  var enCurso = false;
  function validar(texto) {
    if (enCurso) return Promise.resolve();
    enCurso = true;
    var datos = new FormData();
    datos.append("token", texto);
    datos.append("csrf", csrf);
    return fetch(api, { method: "POST", body: datos, credentials: "same-origin", headers: { "X-Requested-With": "fetch" } })
      .then(function (r) { return r.json().catch(function () { return { ok: false, error: "Respuesta inesperada del servidor." }; }); })
      .then(function (j) {
        if (!j.ok) {
          mostrar("error", { mensaje: j.error || "Error desconocido." });
          return;
        }
        mostrar(j.resultado, j);
        registrar(j.resultado, j);
        if (navigator.vibrate) navigator.vibrate(j.resultado === "entrada" ? 80 : [60, 40, 60]);
      })
      .catch(function () {
        mostrar("error", { mensaje: "No hay conexión con el servidor. Revisa el wifi e inténtalo de nuevo." });
      })
      .then(function () { enCurso = false; });
  }

  /* ---------- Código tecleado (respaldo) ---------- */
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var texto = entrada.value.replace(/\s+/g, "");
    if (!texto) {
      mostrar("error", { mensaje: "Escribe el código del boleto." });
      entrada.focus();
      return;
    }
    validar(texto).then(function () { entrada.select(); });
  });

  /* ---------- Cámara ---------- */
  var flujo = null;
  var detector = null;
  var lienzo = null;
  var contexto = null;
  var temporizador = null;
  var ultimo = { texto: "", visto: 0 };

  function estado(t) { estadoCamara.textContent = t; }

  function cargarJsQR() {
    return new Promise(function (resolver, rechazar) {
      if (typeof window.jsQR === "function") return resolver();
      var s = document.createElement("script");
      s.src = raiz.getAttribute("data-jsqr");
      s.onload = function () { resolver(); };
      s.onerror = function () { rechazar(new Error("jsQR")); };
      document.head.appendChild(s);
    });
  }

  function prepararDecodificador() {
    if ("BarcodeDetector" in window) {
      return BarcodeDetector.getSupportedFormats().then(function (f) {
        if (f.indexOf("qr_code") === -1) throw new Error("sin qr");
        detector = new BarcodeDetector({ formats: ["qr_code"] });
      }).catch(function () { detector = null; return cargarJsQR(); });
    }
    return cargarJsQR();
  }

  function leerCuadro() {
    if (!flujo) return Promise.resolve(null);
    if (detector) {
      return detector.detect(video).then(function (r) { return r.length ? r[0].rawValue : null; });
    }
    var w = video.videoWidth, h = video.videoHeight;
    if (!w || !h) return Promise.resolve(null);
    if (!lienzo) {
      lienzo = document.createElement("canvas");
      contexto = lienzo.getContext("2d", { willReadFrequently: true });
    }
    var escala = Math.min(1, 640 / w);              // se reduce para que decodifique rápido
    lienzo.width = Math.round(w * escala);
    lienzo.height = Math.round(h * escala);
    contexto.drawImage(video, 0, 0, lienzo.width, lienzo.height);
    var img = contexto.getImageData(0, 0, lienzo.width, lienzo.height);
    var r = window.jsQR(img.data, img.width, img.height, { inversionAttempts: "dontInvert" });
    return Promise.resolve(r ? r.data : null);
  }

  function ciclo() {
    if (!flujo) return;
    leerCuadro().then(function (texto) {
      var ahora = Date.now();
      if (!texto) return;
      // Mientras el mismo QR siga frente a la cámara (o reaparezca en menos de 1.5 s) no se vuelve a enviar:
      // el boleto ya quedó resuelto y repetirlo solo llenaría la bitácora de lecturas.
      var mismo = texto === ultimo.texto && ahora - ultimo.visto < 1500;
      ultimo = { texto: texto, visto: ahora };
      if (!mismo) return validar(texto);
    }).catch(function () { /* un cuadro que falla se ignora */ })
      .then(function () { if (flujo) temporizador = setTimeout(ciclo, 250); });
  }

  function detener() {
    clearTimeout(temporizador);
    if (flujo) flujo.getTracks().forEach(function (t) { t.stop(); });
    flujo = null;
    video.srcObject = null;
    visor.hidden = true;
    btnCamara.querySelector("span").textContent = "Activar cámara";
  }

  function iniciar() {
    if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      estado("La cámara solo funciona abriendo la app en localhost o con HTTPS. Mientras tanto escribe el código en el campo de abajo.");
      return;
    }
    estado("Abriendo la cámara…");
    navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: "environment" } }, audio: false })
      .then(function (s) {
        flujo = s;
        video.srcObject = s;
        return video.play();
      })
      .then(prepararDecodificador)
      .then(function () {
        visor.hidden = false;
        btnCamara.querySelector("span").textContent = "Detener cámara";
        estado("Apunta al QR del gafete.");
        ciclo();
      })
      .catch(function () {
        detener();
        estado("No se pudo abrir la cámara (permiso denegado, sin cámara o la lectura de QR no cargó). Usa el campo de código.");
      });
  }

  btnCamara.addEventListener("click", function () {
    if (flujo) { detener(); estado(""); } else { iniciar(); }
  });
  document.addEventListener("visibilitychange", function () { if (document.hidden && flujo) { detener(); estado("Cámara detenida al salir de la pantalla."); } });
  window.addEventListener("pagehide", detener);

  if (!window.isSecureContext) {
    estado("Estás en una conexión sin HTTPS: la cámara no estará disponible, pero puedes validar escribiendo el código.");
  }
})();
