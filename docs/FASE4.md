# Fase 4 — Mapa del recinto con pines

Plano SVG esquemático (Plaza Bicentenario + Conjunto Santander) con las 6 zonas y los 24 stands de los proyectos.
Reemplaza el "próximamente" de la pestaña Mapa. Todo es ficticio y sin escala.

## 1. Importar lo nuevo (una sola vez)

En phpMyAdmin, base `udg_nexus`, pestaña **Importar**, en este orden:

1. `db/migraciones/006_mapa.sql`: agrega columnas de coordenadas a `zonas` (`mapa_x`, `mapa_y`, `mapa_ancho`, `mapa_alto`)
   y a `proyectos` (`mapa_x`, `mapa_y`). Cada columna se agrega solo si no existe (MySQL 8 no trae `ADD COLUMN IF NOT EXISTS`).
2. `db/seed/005_seed_mapa.sql`: asigna las coordenadas a las 6 zonas y a los 24 stands.

Ambos se pueden repetir sin duplicar nada. Si falta alguno, `app/mapa.php` muestra un aviso en lugar de fallar.
Ojo: reimportar `db/seed/001_seed_catalogo.sql` vuelve a crear zonas y proyectos **sin** coordenadas; después hay que
volver a importar `005_seed_mapa.sql`.

## 2. Cómo está hecho

- **El plano se dibuja desde la base.** `app/mapa.php` lee `zonas` y `proyectos` y genera el SVG (viewBox 720 × 980):
  cada zona es un rectángulo (`mapa_x`, `mapa_y`, `mapa_ancho`, `mapa_alto`) y cada stand es un recuadro fijo de 88 × 48
  centrado en (`mapa_x`, `mapa_y`) del proyecto. Lo único fijo en el código es la decoración: explanada, acceso, escenario
  de la Demo Arena y las dos salas del Conjunto Santander. Para mover un stand basta con cambiar sus dos números en la base.
- **Colores** solo de la paleta del manual y sus neutros derivados: azul profundo, azul corporativo, cian, ámbar y amarillo
  (el amarillo siempre con texto o trazo azul profundo). El logo no aparece en el plano; solo en la cabecera sobre blanco.
- **Pines** en forma de gota:
  - uno por zona (azul; **amarillo y latiendo** si la zona tiene una actividad en curso según el reloj del evento);
  - uno grande y amarillo que "cae" sobre el stand seleccionado.
- **Detalle** (hoja sobre el plano): al tocar un stand, eje, "Busca inversión", TRL, resumen y botón **Ver ficha**;
  al tocar una zona, su tipo, descripción, número de stands y las actividades del día del evento, con botón a la agenda por zona.
  El día y el estado "en curso" salen del reloj del evento al cargar la página.
- **Filtro por eje** (chips encima del plano): atenúa los stands de otros ejes y avisa cuántos quedan (región `aria-live`).
- **Zoom y desplazamiento** (`assets/js/mapa.js`): pellizco, arrastre, botones + − Todo y Ctrl/Cmd + rueda en escritorio.
  Un arrastre que empieza sobre un stand no lo selecciona. Respeta `prefers-reduced-motion`.
- **Sin JavaScript** cada stand enlaza a su ficha y cada zona a `mapa.php?zona=ID`; además hay una lista "Zonas y stands"
  (desplegable) con todos los stands como enlaces, útil con lector de pantalla.
- Los datos pasan al JavaScript como JSON (con `JSON_HEX_*`) y se escriben en el DOM con `textContent`, nunca como HTML.

## 3. Enlaces con el resto de la app

| Desde | Hacia |
|---|---|
| Ficha de proyecto → "Dónde encontrarlo" → **Ver en el mapa** | `mapa.php?proyecto=ID` (pin sobre su stand, hoja abierta) |
| Tarjetas de la agenda y de "En este momento" (nombre de la zona) | `mapa.php?zona=ID` |
| Pestaña Mapa y atajo del Home | `mapa.php` |
| Hoja de un stand → **Ver ficha** | `proyecto.php?id=ID` |

La URL se actualiza al seleccionar (`?proyecto=` o `?zona=`) y se limpia al cerrar la hoja (botón Cerrar o Esc).
Un identificador inexistente o inválido simplemente muestra el plano sin selección.

## 4. Archivos nuevos o cambiados

- Nuevos: `app/mapa.php`, `assets/js/mapa.js`, `db/migraciones/006_mapa.sql`, `db/seed/005_seed_mapa.sql`, `docs/FASE4.md`.
- Cambiados: `assets/css/app.css` (sección "Mapa del recinto"), `includes/app_footer.php` (pestaña), `app/inicio.php` (atajo y enlace de zona),
  `app/proyecto.php` (botón al mapa), `app/agenda.php` (enlace de zona), `includes/app.php` (las consultas de actividades
  devuelven también `zona_id`), `README.md`.
- `app/proximamente.php` ya no se usa (solo tenía el módulo del mapa) y se movió a `eliminar/` (excluida de Git).

## 5. Límites a tener en cuenta

- Es un plano **esquemático**: no hay GPS ni posición del usuario (decisión de ARQUITECTURA.md).
- El estado "en curso" de las zonas se calcula al cargar la página; si el reloj está en "avanza sola", se actualiza al recargar el mapa.
- Los stands de las zonas sin proyectos (Reverse Pitch, Patent Room, Due Diligence Desk, Conjunto Santander) no tienen recuadros: se consultan como zona.
