/* ============================================================
   UDG NEXUS — Mapa del recinto (Fase 4)
   Sobre el plano SVG que dibuja app/mapa.php agrega: zoom y arrastre (puntero, pellizco, botones),
   selección de zonas y stands con pin y hoja de detalle, y filtro por eje.
   Todo el texto se escribe con textContent (los datos nunca se interpretan como HTML).
   Sin JavaScript el plano sigue siendo útil: cada stand enlaza a su ficha.
   ============================================================ */
(function () {
  "use strict";

  var raiz = document.querySelector("[data-mapa]");
  if (!raiz) return;
  var svg = raiz.querySelector(".plano");
  var hoja = raiz.querySelector("[data-hoja]");
  var pinSel = svg.querySelector(".pin.sel");
  var datos;
  try {
    datos = JSON.parse(document.getElementById("mapa-datos").textContent);
  } catch (e) {
    return;
  }

  var base = svg.viewBox.baseVal;
  var W = base.width, H = base.height;
  var Z_MAX = 5;
  var vista = { cx: W / 2, cy: H / 2, z: 1 };   // centro (en unidades del plano) y zoom (1 = todo el plano)
  var reducir = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  var anim = null;

  /* ---------- Vista: zoom y desplazamiento ---------- */

  function medidas() {
    var r = svg.getBoundingClientRect();
    if (!r.width || !r.height) return null;
    var ar = r.width / r.height;
    return { r: r, ar: ar, wFit: Math.max(W, H * ar) };   // ancho de vista que deja todo el plano a la vista
  }

  /* Mantiene el centro dentro del plano, con un margen extra para poder subir las zonas del borde
     por encima de la hoja de detalle. Con todo el plano a la vista, siempre queda centrado. */
  function limitar(c, tam, total, margen) {
    if (tam >= total) return total / 2;
    return Math.min(Math.max(c, tam / 2 - margen), total - tam / 2 + margen);
  }

  function aplicar() {
    var m = medidas();
    if (!m) return;
    vista.z = Math.min(Z_MAX, Math.max(1, vista.z));
    var w = m.wFit / vista.z, h = w / m.ar;
    vista.cx = limitar(vista.cx, w, W, w * 0.15);
    vista.cy = limitar(vista.cy, h, H, h * 0.5);
    svg.setAttribute("viewBox", (vista.cx - w / 2) + " " + (vista.cy - h / 2) + " " + w + " " + h);
  }

  function detenerAnim() {
    if (anim) { cancelAnimationFrame(anim); anim = null; }
  }

  function desplazar(dxPx, dyPx) {
    var m = medidas();
    if (!m) return;
    var esc = (m.wFit / vista.z) / m.r.width;     // unidades del plano por píxel
    vista.cx -= dxPx * esc;
    vista.cy -= dyPx * esc;
    aplicar();
  }

  /** Acerca o aleja manteniendo fijo el punto de pantalla (px, py). */
  function zoomEn(factor, px, py) {
    var m = medidas();
    if (!m) return;
    var w = m.wFit / vista.z, h = w / m.ar;
    var fx = (px - m.r.left) / m.r.width, fy = (py - m.r.top) / m.r.height;
    var mx = vista.cx - w / 2 + fx * w, my = vista.cy - h / 2 + fy * h;
    vista.z = Math.min(Z_MAX, Math.max(1, vista.z * factor));
    var w2 = m.wFit / vista.z, h2 = w2 / m.ar;
    vista.cx = mx - (fx - 0.5) * w2;
    vista.cy = my - (fy - 0.5) * h2;
    aplicar();
  }

  function irA(cx, cy, z) {
    detenerAnim();
    if (reducir) {
      vista.cx = cx; vista.cy = cy; vista.z = z;
      aplicar();
      return;
    }
    var de = { cx: vista.cx, cy: vista.cy, z: vista.z }, t0 = null, dur = 300;
    function paso(t) {
      if (t0 === null) t0 = t;
      var k = Math.min(1, (t - t0) / dur);
      k = k < 0.5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2;
      vista.cx = de.cx + (cx - de.cx) * k;
      vista.cy = de.cy + (cy - de.cy) * k;
      vista.z = de.z + (z - de.z) * k;
      aplicar();
      anim = k < 1 ? requestAnimationFrame(paso) : null;
    }
    anim = requestAnimationFrame(paso);
  }

  /** Centra (tx, ty) en la parte alta del mapa para que la hoja de detalle no lo tape. */
  function enfocar(tx, ty, z) {
    var m = medidas();
    if (!m) return;
    var h = (m.wFit / z) / m.ar;
    irA(tx, ty + (0.5 - 0.36) * h, z);
  }

  /* ---------- Gestos ---------- */

  var punteros = {};
  var nPunteros = 0;
  var recorrido = 0;
  var suprimirClic = false;
  var pellizco = null;

  function distancia() {
    var ids = Object.keys(punteros);
    var a = punteros[ids[0]], b = punteros[ids[1]];
    return { d: Math.hypot(a.x - b.x, a.y - b.y), x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
  }

  svg.addEventListener("pointerdown", function (e) {
    if (e.pointerType === "mouse" && e.button !== 0) return;
    detenerAnim();
    punteros[e.pointerId] = { x: e.clientX, y: e.clientY };
    nPunteros = Object.keys(punteros).length;
    if (nPunteros === 1) recorrido = 0;
    if (nPunteros === 2) {
      var p = distancia();
      pellizco = { d: p.d, z: vista.z };
      recorrido = 99;                      // un pellizco nunca cuenta como toque
    }
  });

  window.addEventListener("pointermove", function (e) {
    var p = punteros[e.pointerId];
    if (!p) return;
    var dx = e.clientX - p.x, dy = e.clientY - p.y;
    p.x = e.clientX; p.y = e.clientY;
    if (nPunteros === 1) {
      recorrido += Math.abs(dx) + Math.abs(dy);
      if (recorrido > 8) {
        svg.classList.add("arrastrando");
        desplazar(dx, dy);
      }
    } else if (nPunteros === 2 && pellizco) {
      var q = distancia();
      var objetivo = Math.min(Z_MAX, Math.max(1, pellizco.z * (q.d / pellizco.d)));
      zoomEn(objetivo / vista.z, q.x, q.y);
    }
  });

  function soltar(e) {
    if (!punteros[e.pointerId]) return;
    delete punteros[e.pointerId];
    nPunteros = Object.keys(punteros).length;
    if (nPunteros < 2) pellizco = null;
    if (recorrido > 8) {
      suprimirClic = true;                 // el "click" que sigue a un arrastre no debe abrir nada
      setTimeout(function () { suprimirClic = false; }, 0);
    }
    if (nPunteros === 0) svg.classList.remove("arrastrando");
  }
  window.addEventListener("pointerup", soltar);
  window.addEventListener("pointercancel", soltar);

  svg.addEventListener("wheel", function (e) {
    if (!(e.ctrlKey || e.metaKey)) return;  // sin Ctrl/Cmd la rueda sigue desplazando la página
    e.preventDefault();
    detenerAnim();
    zoomEn(e.deltaY < 0 ? 1.25 : 0.8, e.clientX, e.clientY);
  }, { passive: false });

  svg.addEventListener("click", function (e) {
    var a = e.target.closest ? e.target.closest("a[data-tipo]") : null;
    if (!a) return;
    e.preventDefault();                     // con JavaScript el toque abre la hoja; sin él, el enlace lleva a la ficha
    if (suprimirClic) return;
    seleccionar(a.getAttribute("data-tipo"), a.getAttribute("data-id"), true);
  });

  /* Botones + / − / Todo */
  raiz.querySelectorAll("[data-zoom]").forEach(function (b) {
    b.addEventListener("click", function () {
      var m = medidas();
      if (!m) return;
      detenerAnim();
      var que = b.getAttribute("data-zoom");
      if (que === "todo") { irA(W / 2, H / 2, 1); return; }
      zoomEn(que === "mas" ? 1.5 : 1 / 1.5, m.r.left + m.r.width / 2, m.r.top + m.r.height / 2);
    });
  });

  /* ---------- Hoja de detalle ---------- */

  function el(tag, clase, texto) {
    var n = document.createElement(tag);
    if (clase) n.className = clase;
    if (texto !== undefined && texto !== null) n.textContent = texto;
    return n;
  }

  function mostrarHoja(nodos) {
    while (hoja.firstChild) hoja.removeChild(hoja.firstChild);
    nodos.forEach(function (n) { hoja.appendChild(n); });
    hoja.hidden = false;
  }

  function botonCerrar() {
    var b = el("button", "btn-app sec", "Cerrar");
    b.type = "button";
    b.addEventListener("click", cerrarHoja);
    return b;
  }

  function hojaStand(p) {
    var z = datos.zonas[p.zonaId];
    var chips = el("div", "chips");
    chips.appendChild(el("span", "chip", p.area));
    if (p.inv) chips.appendChild(el("span", "chip inv", "Busca inversión"));
    chips.appendChild(el("span", "chip oscuro", "TRL " + p.trl));
    var acciones = el("div", "hoja-acciones");
    var ver = el("a", "btn-app", "Ver ficha");
    ver.href = p.url;
    acciones.appendChild(ver);
    acciones.appendChild(botonCerrar());
    mostrarHoja([
      chips,
      el("h3", null, p.nombre),
      el("p", "suave pequeno", "Stand " + p.stand + (z ? " · " + z.nombre : "")),
      el("p", "hoja-texto", p.resumen),
      acciones
    ]);
  }

  function hojaZona(z) {
    var chips = el("div", "chips");
    chips.appendChild(el("span", "chip", z.tipo));
    if (z.vivo) chips.appendChild(el("span", "chip inv", "Actividad en curso"));
    var nodos = [chips, el("h3", null, z.nombre), el("p", "hoja-texto", z.desc)];
    if (z.n > 0) nodos.push(el("p", "suave pequeno", z.n + (z.n === 1 ? " stand" : " stands") + " en esta zona"));
    if (z.acts.length) {
      nodos.push(el("p", "hoja-sub", "Actividades del día " + datos.dia));
      var ul = el("ul", "hoja-acts");
      z.acts.slice(0, 4).forEach(function (a) {
        var li = el("li", a.e === "en-curso" ? "en-curso" : "");
        li.appendChild(el("strong", null, a.h));
        li.appendChild(document.createTextNode(" " + a.t));
        if (a.e === "en-curso") li.appendChild(el("span", "chip inv", "En curso"));
        ul.appendChild(li);
      });
      nodos.push(ul);
    }
    var acciones = el("div", "hoja-acciones");
    if (z.acts.length) {
      var ag = el("a", "btn-app", "Ver agenda por zona");
      ag.href = datos.agenda;
      acciones.appendChild(ag);
    }
    acciones.appendChild(botonCerrar());
    nodos.push(acciones);
    mostrarHoja(nodos);
  }

  /* ---------- Selección ---------- */

  function limpiarSeleccion() {
    svg.querySelectorAll(".sel").forEach(function (n) {
      if (n !== pinSel) n.classList.remove("sel");
    });
    pinSel.classList.add("oculto");
  }

  function actualizarUrl(tipo, id) {
    if (!window.history || !history.replaceState) return;
    var q = tipo === "stand" ? "?proyecto=" + id : tipo === "zona" ? "?zona=" + id : "";
    try { history.replaceState(null, "", location.pathname + q); } catch (e) { /* sin historial: no pasa nada */ }
  }

  function seleccionar(tipo, id, mover) {
    limpiarSeleccion();
    if (tipo === "stand") {
      var p = datos.proyectos[id];
      var nodo = svg.querySelector('a.stand[data-id="' + id + '"]');
      if (!p || !nodo) return;
      nodo.classList.add("sel");
      pinSel.setAttribute("transform", "translate(" + p.x + " " + (p.y - 27) + ") scale(1.25)");
      pinSel.classList.remove("oculto");
      var dentro = pinSel.firstElementChild;             // reinicia la animación de caída del pin
      dentro.style.animation = "none";
      void dentro.getBoundingClientRect();
      dentro.style.animation = "";
      hojaStand(p);
      if (mover) enfocar(p.x, p.y - 20, Math.max(vista.z, 2));
    } else {
      var z = datos.zonas[id];
      var zn = svg.querySelector('a.zona[data-id="' + id + '"]');
      if (!z || !zn) return;
      zn.classList.add("sel");
      hojaZona(z);
      if (mover) {
        var m = medidas();
        var zoom = m ? Math.min(2.8, Math.max(1, m.wFit / (z.w + 60))) : 1;   // que la zona entera quepa a lo ancho
        enfocar(z.x + z.w / 2, z.y + z.h / 2, zoom);
      }
    }
    actualizarUrl(tipo, id);
  }

  function cerrarHoja() {
    hoja.hidden = true;
    limpiarSeleccion();
    actualizarUrl(null, null);
  }

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !hoja.hidden) cerrarHoja();
  });

  /* ---------- Filtro por eje ---------- */

  var estadoFiltro = raiz.querySelector("[data-filtro-estado]");
  raiz.querySelectorAll("[data-filtro]").forEach(function (b) {
    b.addEventListener("click", function () {
      var area = b.getAttribute("data-filtro");
      raiz.querySelectorAll("[data-filtro]").forEach(function (x) {
        x.setAttribute("aria-pressed", x === b ? "true" : "false");
      });
      var visibles = 0;
      svg.querySelectorAll("a.stand").forEach(function (s) {
        var apagar = area !== "" && s.getAttribute("data-area") !== area;
        s.classList.toggle("apagado", apagar);
        if (!apagar) visibles++;
      });
      if (estadoFiltro) {
        estadoFiltro.textContent = area === "" ? "Mostrando todos los stands."
          : visibles + (visibles === 1 ? " stand de " : " stands de ") + b.textContent + ".";
      }
    });
  });

  /* ---------- Arranque ---------- */

  aplicar();
  window.addEventListener("resize", aplicar);
  if (datos.inicial) {
    requestAnimationFrame(function () {
      seleccionar(datos.inicial.tipo, String(datos.inicial.id), true);
    });
  }
})();
