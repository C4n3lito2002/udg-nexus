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

/* ============================================================
   Fase 2, parte 2 — reloj vivo, alertas y agenda personal
   1. Consulta api/ahora.php cada 5 s (hora del evento + alertas).
   2. Muestra las alertas dentro de la app, en cualquier pantalla.
   3. En agenda.php: agregar, quitar, alerta y empalmes por AJAX.
   Todo el texto dinámico se escribe con textContent (sin innerHTML).
   ============================================================ */
(function () {
  "use strict";

  var cuerpo = document.body;
  if (!cuerpo || !cuerpo.classList.contains("app")) return;

  var API = cuerpo.getAttribute("data-api") || "api/";
  var APP = cuerpo.getAttribute("data-app") || "";
  var LOGUEADO = cuerpo.getAttribute("data-sesion") === "1";
  var metaCsrf = document.querySelector('meta[name="csrf"]');
  var CSRF = metaCsrf ? metaCsrf.getAttribute("content") : "";

  var pad = function (n) { return (n < 10 ? "0" : "") + n; };

  /* 'AAAA-MM-DD HH:MM:SS' -> milisegundos "UTC" (solo sirve para comparar y sumar). */
  var leerFecha = function (s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/.exec(s || "");
    return m ? Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]) : NaN;
  };
  var formatoHora = function (ms) {
    var d = new Date(ms);
    return pad(d.getUTCHours()) + ":" + pad(d.getUTCMinutes());
  };
  var poner = function (selector, texto) {
    document.querySelectorAll(selector).forEach(function (el) { el.textContent = texto; });
  };

  /* ---------- Reloj del evento (se interpola entre consultas) ---------- */
  var reloj = { base: NaN, recibido: 0, velocidad: 0 };
  var ahoraEvento = function () {
    return isNaN(reloj.base) ? NaN : reloj.base + (Date.now() - reloj.recibido) * reloj.velocidad;
  };

  /* Mismos textos que estado_actividad() en PHP. */
  var estadoActividad = function (ini, fin, ahora) {
    if (ahora >= ini && ahora < fin) return ["en-curso", "En curso"];
    if (ahora < ini) {
      var min = Math.ceil((ini - ahora) / 60000);
      return min <= 60 ? ["pronto", "Empieza en " + min + " min"] : ["luego", ""];
    }
    return ["pasada", "Finalizada"];
  };

  var actualizarEstados = function () {
    var ahora = ahoraEvento();
    if (isNaN(ahora)) return;
    document.querySelectorAll(".act-ag").forEach(function (card) {
      var ini = leerFecha(card.getAttribute("data-inicio"));
      var fin = leerFecha(card.getAttribute("data-fin"));
      var e = estadoActividad(ini, fin, ahora);
      ["en-curso", "pronto", "luego", "pasada"].forEach(function (c) { card.classList.remove(c); });
      card.classList.add(e[0]);
      var chip = card.querySelector("[data-estado]");
      if (chip) {
        chip.className = "estado " + e[0];
        chip.textContent = e[1];
        chip.hidden = e[1] === "";
      }
    });
  };

  var tick = function () {
    var ahora = ahoraEvento();
    if (isNaN(ahora)) return;
    poner("[data-hora-evento]", formatoHora(ahora));
    actualizarEstados();
  };

  /* ---------- Alertas dentro de la app ---------- */
  var contenedor = null;
  var toasts = {};        // id -> elemento mostrado
  var descartadas = {};   // id -> true mientras el servidor aún las lista

  var crear = function (tag, clase, texto) {
    var el = document.createElement(tag);
    if (clase) el.className = clase;
    if (texto !== undefined) el.textContent = texto;
    return el;
  };

  var post = function (params) {
    var datos = new URLSearchParams();
    Object.keys(params).forEach(function (k) { datos.append(k, params[k]); });
    datos.append("csrf", CSRF);
    return fetch(API + "agenda_toggle.php", {
      method: "POST", credentials: "same-origin", headers: { Accept: "application/json" }, body: datos
    }).then(function (r) {
      return r.json().then(function (d) { d.estadoHttp = r.status; return d; });
    });
  };

  var zonaAlertas = function () {
    if (!contenedor) {
      contenedor = crear("div", "alertas-app");
      contenedor.setAttribute("role", "region");
      contenedor.setAttribute("aria-label", "Alertas de tu agenda");
      (document.querySelector(".app-shell") || cuerpo).appendChild(contenedor);
    }
    return contenedor;
  };

  var quitarToast = function (id) {
    if (toasts[id]) {
      toasts[id].remove();
      delete toasts[id];
    }
  };

  var crearToast = function (a) {
    var t = crear("div", "alerta-toast");
    t.setAttribute("role", "alert");
    var etq = crear("span", "etq");
    etq.innerHTML = '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 1.5h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/></svg>';
    etq.appendChild(document.createTextNode("Alerta de tu agenda"));
    t.appendChild(etq);
    t.appendChild(crear("strong", "", a.titulo));
    t.appendChild(crear("p", "", a.mensaje + " " + a.zona + "."));
    t.setAttribute("data-alerta", String(a.id));
    var botones = crear("div", "botones");
    var ver = crear("a", "", "Ver mi agenda");
    ver.href = APP + "agenda.php?ver=mia";
    var ok = crear("button", "", "Entendido");
    ok.type = "button";
    ok.addEventListener("click", function () {
      descartadas[a.id] = true;
      quitarToast(a.id);
      post({ accion: "visto", actividad_id: a.id }).then(consultar).catch(function () {});
    });
    botones.appendChild(ver);
    botones.appendChild(ok);
    t.appendChild(botones);
    zonaAlertas().appendChild(t);
    toasts[a.id] = t;
  };

  var pintarAlertas = function (lista) {
    var vigentes = {};
    lista.forEach(function (a) {
      vigentes[a.id] = true;
      if (toasts[a.id]) {
        /* Los minutos cambian con el reloj: se actualiza el texto de la alerta ya mostrada. */
        toasts[a.id].querySelector("p").textContent = a.mensaje + " " + a.zona + ".";
      } else if (!descartadas[a.id]) {
        crearToast(a);
      }
    });
    Object.keys(toasts).forEach(function (id) { if (!vigentes[id]) quitarToast(id); });
    Object.keys(descartadas).forEach(function (id) { if (!vigentes[id]) delete descartadas[id]; });
    var n = Object.keys(toasts).length;
    document.querySelectorAll("[data-badge-agenda]").forEach(function (b) {
      b.textContent = String(n);
      b.hidden = n === 0;
    });
  };

  /* ---------- Consulta periódica ---------- */
  var procesar = function (d) {
    if (!d || !d.ok) return;
    reloj.base = leerFecha(d.ahora);
    reloj.recibido = Date.now();
    reloj.velocidad = d.velocidad;
    poner("[data-fecha-evento]", d.fecha);
    poner("[data-dia-evento]", d.dia ? " · Día " + d.dia : "");
    tick();
    if (LOGUEADO) pintarAlertas(d.alertas || []);
  };

  var consultar = function () {
    if (document.hidden) return Promise.resolve();
    return fetch(API + "ahora.php", { credentials: "same-origin", cache: "no-store", headers: { Accept: "application/json" } })
      .then(function (r) { return r.json(); })
      .then(procesar)
      .catch(function () {});
  };

  if (LOGUEADO || document.querySelector("[data-hora-evento]")) {
    consultar();
    setInterval(consultar, 5000);
    setInterval(tick, 1000);
    document.addEventListener("visibilitychange", function () { if (!document.hidden) consultar(); });
  }

  /* ---------- Agenda personal (solo en agenda.php) ---------- */
  var cont = document.querySelector("[data-agenda]");
  if (!cont) return;

  var estado = [];
  try { estado = JSON.parse(document.getElementById("agenda-datos").textContent) || []; } catch (e) { estado = []; }
  var anuncio = document.querySelector("[data-agenda-estado]");
  var soloMia = cont.hasAttribute("data-solo-mia");

  var anunciar = function (texto) { if (anuncio) anuncio.textContent = texto; };
  var enAgenda = function (id) {
    for (var i = 0; i < estado.length; i++) if (String(estado[i].id) === String(id)) return estado[i];
    return null;
  };
  var horaDe = function (s) { return s.slice(11, 16); };

  var choquesDe = function (id, ini, fin) {
    return estado.filter(function (o) { return String(o.id) !== String(id) && ini < o.fin && o.inicio < fin; });
  };
  /* Mismo texto que agenda_texto_choque() en PHP. */
  var textoChoque = function (lista, agregada) {
    if (!lista.length) return "";
    var partes = lista.map(function (o) { return "«" + o.titulo + "» (" + horaDe(o.inicio) + " a " + horaDe(o.fin) + ")"; });
    return (agregada ? "Se empalma con " : "Si la agregas, se empalma con ") + partes.join(" y ") + ".";
  };

  var resumen = function () {
    var n = estado.length, pares = 0;
    for (var i = 0; i < n; i++) for (var j = i + 1; j < n; j++) {
      if (estado[i].inicio < estado[j].fin && estado[j].inicio < estado[i].fin) pares++;
    }
    poner("[data-conteo-agenda]", String(n));
    poner("[data-resumen]",
      (n === 1 ? "1 actividad" : n + " actividades") + " · " +
      (pares === 0 ? "sin empalmes" : pares === 1 ? "1 empalme de horario" : pares + " empalmes de horario"));
  };

  var repintar = function () {
    cont.querySelectorAll(".act-ag").forEach(function (card) {
      var id = card.getAttribute("data-actividad");
      var g = enAgenda(id);
      var chip = card.querySelector("[data-chip-agenda]");
      if (chip) chip.hidden = !g;
      var btn = card.querySelector("[data-agenda-btn]");
      if (btn) {
        btn.textContent = g ? "Quitar de mi agenda" : "Agregar a mi agenda";
        btn.classList.toggle("quitar", !!g);
      }
      var wrap = card.querySelector("[data-alerta-wrap]");
      if (wrap) {
        wrap.hidden = !g;
        if (g) wrap.querySelector("select").value = g.alerta ? String(g.alerta) : "";
      }
      var aviso = card.querySelector("[data-choque]");
      if (aviso) {
        var t = textoChoque(choquesDe(id, card.getAttribute("data-inicio"), card.getAttribute("data-fin")), !!g);
        aviso.hidden = t === "";
        aviso.querySelector("span").textContent = t;
      }
    });
    resumen();
  };

  var quitarTarjeta = function (card) {
    var grupo = card.closest("[data-dia-grupo]");
    card.remove();
    if (grupo && !grupo.querySelector(".act-ag")) grupo.remove();
    var vacio = cont.querySelector("[data-vacio]");
    if (vacio) vacio.hidden = cont.querySelector(".act-ag") !== null;
  };

  var enviar = function (params, card, boton) {
    if (boton) boton.disabled = true;
    return post(params).then(function (d) {
      if (d.estadoHttp === 401) {
        window.location.href = APP + "login.php?volver=" + encodeURIComponent(window.location.pathname + window.location.search);
        return;
      }
      if (!d.ok) { anunciar(d.error || "No se pudo guardar el cambio."); repintar(); return; }
      estado = d.agenda;
      if (soloMia && params.accion === "quitar") quitarTarjeta(card);
      repintar();
      anunciar(d.mensaje);
      consultar();   // las alertas pueden cambiar al instante
    }).catch(function () {
      anunciar("No se pudo conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.");
    }).then(function () { if (boton) boton.disabled = false; });
  };

  cont.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-agenda-btn]");
    if (!btn) return;
    var card = btn.closest(".act-ag");
    var id = card.getAttribute("data-actividad");
    enviar({ accion: enAgenda(id) ? "quitar" : "agregar", actividad_id: id }, card, btn);
  });

  cont.addEventListener("change", function (e) {
    var sel = e.target.closest("[data-alerta-sel]");
    if (!sel) return;
    var card = sel.closest(".act-ag");
    enviar({ accion: "alerta", actividad_id: card.getAttribute("data-actividad"), alerta_minutos: sel.value }, card, null);
  });

  repintar();
})();
