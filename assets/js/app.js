/* ============================================================
   UDG NEXUS — Comportamiento de la app móvil
   1. Buscador universal con sugerencias en vivo
   2. Carruseles con puntos (la vitrina rota sola)
   ============================================================ */
(function () {
  "use strict";

  /* ---------- 1. Buscador universal ---------- */
  var caja = document.querySelector("[data-buscador]");
  if (caja) {
    var input = caja.querySelector('input[type="search"]');
    var panel = caja.querySelector(".resultados");
    var estado = caja.querySelector("[data-estado]");
    var temporizador = null;
    var peticion = null;

    var cerrar = function () {
      panel.hidden = true;
      panel.textContent = "";
    };

    var crear = function (tag, clase, texto) {
      var el = document.createElement(tag);
      if (clase) el.className = clase;
      if (texto !== undefined) el.textContent = texto;
      return el;
    };

    var pintar = function (datos) {
      panel.textContent = "";
      if (!datos.total) {
        panel.appendChild(crear("p", "vacio", "Sin resultados para “" + datos.q + "”."));
        panel.hidden = false;
        if (estado) estado.textContent = "Sin resultados.";
        return;
      }
      datos.grupos.forEach(function (g) {
        panel.appendChild(crear("h3", "", g.titulo));
        g.items.forEach(function (it) {
          var a = crear("a");
          a.href = it.url;
          a.appendChild(crear("strong", "", it.titulo));
          if (it.detalle) a.appendChild(crear("span", "", it.detalle));
          panel.appendChild(a);
        });
      });
      var todo = crear("a", "ver-todo", "Ver todos los resultados");
      todo.href = "buscar.php?q=" + encodeURIComponent(datos.q);
      panel.appendChild(todo);
      panel.hidden = false;
      if (estado) estado.textContent = datos.total + " resultados sugeridos.";
    };

    var buscar = function () {
      var q = input.value.trim();
      if (q.length < 2) {
        cerrar();
        return;
      }
      if (peticion) peticion.abort();
      peticion = new AbortController();
      fetch("api/buscar.php?q=" + encodeURIComponent(q), { signal: peticion.signal, headers: { Accept: "application/json" } })
        .then(function (r) { return r.json(); })
        .then(pintar)
        .catch(function (e) { if (e.name !== "AbortError") cerrar(); });
    };

    input.addEventListener("input", function () {
      clearTimeout(temporizador);
      temporizador = setTimeout(buscar, 250);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") cerrar();
    });
    document.addEventListener("click", function (e) {
      if (!caja.contains(e.target)) cerrar();
    });
  }

  /* ---------- 2. Carruseles ---------- */
  var reducido = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  document.querySelectorAll("[data-carrusel]").forEach(function (carrusel) {
    var pista = carrusel.querySelector(".carrusel");
    var puntos = carrusel.querySelector(".carrusel-puntos");
    var items = pista ? Array.prototype.slice.call(pista.children) : [];
    if (!pista || items.length < 2) return;

    var actual = 0;
    var botones = [];

    var marcar = function (i) {
      actual = i;
      botones.forEach(function (b, k) {
        b.setAttribute("aria-current", k === i ? "true" : "false");
      });
    };

    var ir = function (i) {
      var destino = items[i];
      pista.scrollTo({
        left: destino.offsetLeft - (pista.clientWidth - destino.clientWidth) / 2,
        behavior: reducido ? "auto" : "smooth"
      });
      marcar(i);
    };

    if (puntos) {
      items.forEach(function (_, i) {
        var b = document.createElement("button");
        b.type = "button";
        b.setAttribute("aria-label", "Ir a la tarjeta " + (i + 1));
        b.addEventListener("click", function () { ir(i); });
        puntos.appendChild(b);
        botones.push(b);
      });
      marcar(0);
    }

    /* Mantiene los puntos sincronizados cuando la persona desliza. */
    var pendiente = null;
    pista.addEventListener("scroll", function () {
      clearTimeout(pendiente);
      pendiente = setTimeout(function () {
        var centro = pista.scrollLeft + pista.clientWidth / 2;
        var mejor = 0, dist = Infinity;
        items.forEach(function (it, i) {
          var d = Math.abs(it.offsetLeft + it.clientWidth / 2 - centro);
          if (d < dist) { dist = d; mejor = i; }
        });
        marcar(mejor);
      }, 80);
    }, { passive: true });

    /* Rotación automática solo si se pide y la persona no pidió menos movimiento. */
    if (carrusel.hasAttribute("data-auto") && !reducido) {
      var pausado = false;
      var detener = function () { pausado = true; };
      ["pointerdown", "focusin", "mouseenter", "touchstart"].forEach(function (ev) {
        carrusel.addEventListener(ev, detener, { passive: true });
      });
      setInterval(function () {
        if (!pausado && !document.hidden) ir((actual + 1) % items.length);
      }, 5000);
    }
  });
})();

/* ============================================================
   Fase 2 — cuentas y reloj
   ============================================================ */
(function () {
  "use strict";

  /* Cuentas de demostración: al tocar una se llena el correo. */
  document.querySelectorAll("[data-demo-correo]").forEach(function (b) {
    b.addEventListener("click", function () {
      var campo = document.getElementById("correo");
      var clave = document.getElementById("clave");
      if (campo) campo.value = b.getAttribute("data-demo-correo");
      if (clave) clave.focus();
    });
  });

  /* Reloj grande del panel admin: se mueve con la hora simulada. */
  var reloj = document.querySelector("[data-reloj-evento]");
  if (reloj) {
    var modo = document.querySelector('input[name="modo"]:checked');
    var vel = document.getElementById("velocidad");
    var base = new Date(reloj.getAttribute("data-reloj-evento")).getTime();
    var t0 = Date.now();
    var pintar = function () {
      var factor = modo && modo.value === "avance" ? parseInt(vel.value, 10) || 1 : modo && modo.value === "fijo" ? 0 : 1;
      var d = new Date(base + (Date.now() - t0) * factor);
      reloj.textContent = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
    };
    setInterval(pintar, 1000);
  }

  /* Botones "Saltar a un momento": llenan fecha y hora del formulario. */
  document.querySelectorAll("[data-salto-fecha]").forEach(function (b) {
    b.addEventListener("click", function () {
      document.getElementById("fecha").value = b.getAttribute("data-salto-fecha");
      document.getElementById("hora").value = b.getAttribute("data-salto-hora");
      document.getElementById("hora").focus();
    });
  });
})();
