# Fase 5 — Avisos y notificaciones

El administrador publica avisos para todos o para un rol; cada persona los ve en un centro de avisos
dentro de la app, con los no leídos destacados y una insignia en la campana de la barra. Un aviso nuevo
llega al celular **sin recargar**.

## 1. Importar lo nuevo (una sola vez)

En phpMyAdmin, base `udg_nexus`, pestaña **Importar**, en este orden:

1. `db/migraciones/007_notificaciones.sql` (tablas `notificaciones` y `notificaciones_leidas`).
2. `db/seed/006_seed_avisos.sql` (7 avisos de ejemplo del día 1 y algunas marcas de lectura).

Ambos se pueden repetir sin duplicar. El seed solo reemplaza los avisos con id 1 a 7 (y sus marcas de lectura);
los que publique el administrador después (id 8 en adelante) no se tocan. Si falta la migración, las pantallas
de avisos muestran un aviso en lugar de fallar y el resto de la app sigue igual.

## 2. Qué incluye

- **Centro de avisos** (`app/notificaciones.php`, hace falta sesión): lista del último publicado al primero.
  Cada persona ve los avisos dirigidos a **todos** y a **su rol**. Los no leídos van resaltados (borde amarillo,
  chip «Sin leer»); cada uno tiene «Marcar como leído» y arriba hay «Marcar todos como leídos». Sin JavaScript
  funciona con formularios normales; con JavaScript marca por AJAX.
- **Campana con insignia** (`includes/app_header.php`): en la barra de toda la app, con el conteo de no leídos
  (`9+` si pasan de 9). Solo aparece con sesión y con la migración 007 importada.
- **Avisos sin recargar**: se reutiliza el sondeo que ya existía. `app.js` consulta `api/ahora.php` cada 5 s;
  ahora la respuesta trae también `avisos { no_leidos, visibles[], nuevos[] }`. Con `?av=N` (el id más alto que
  la página ya conoce, escrito en `data-avisos-max` del `<body>`) el servidor devuelve solo los avisos posteriores.
  - En el centro de avisos, el aviso nuevo se inserta arriba (y se anuncia en una región `aria-live`); uno
    retirado desaparece solo de la lista.
  - En cualquier otra pantalla sale una alerta «Nuevo aviso» (o «Aviso importante») con «Ver avisos» y «Cerrar».
  - **Por qué no hay un segundo sondeo:** `ahora.php` ya se consulta cada 5 s y ya valida la sesión; sumar otra
    petición duplicaría el costo de cada celular sin ganar nada. Si el costo creciera, la consulta de avisos
    ya está aparte (`avisos_para_sondeo()`) y se puede separar sin cambiar el cliente.
- **Panel del administrador** (`app/admin/avisos.php`, solo rol admin; tarjeta en `admin/index.php`):
  - *Publicar*: título (3 a 80 caracteres), mensaje (10 a 500), «Dirigido a» (Todos o un rol) y nivel
    (informativo o importante), con contador de caracteres. Se valida en el servidor y se conserva lo escrito
    si hay errores.
  - *Publicados*: cada aviso con su audiencia, quién y cuándo lo publicó y **cuántas personas lo leyeron**
    («3 de 6 personas lo leyeron (50%)», con barra).
  - *Retirar*, en dos pasos: «Retirar…» abre una pantalla de confirmación (GET, no cambia nada) y solo su
    botón «Sí, retirar el aviso» lo retira. Retirar **no borra**: el aviso deja de mostrarse y de contar como
    no leído, y se conserva su conteo de lecturas (sección «Retirados»).

## 3. Seguridad

- Rol verificado con `requiere_rol('admin')` en el panel y en cada envío; la API de lectura exige sesión.
- **CSRF**: `csrf_campo()` / `csrf_validar()` en el panel y en `notificaciones.php`; `csrf_valido()` en las APIs.
- **Doble envío**: el token de un solo uso de `includes/forms.php` (`form_campo()` / `form_abrir()`). Reenviar el
  mismo formulario (doble clic, «atrás», recarga del POST) no duplica el aviso: se muestra «Ese aviso ya se había
  publicado». Además el botón se deshabilita al enviar (`form[data-una-vez]`).
- **Retiro en dos pasos en el servidor**: el token de «retirar» solo lo emite la pantalla de confirmación, así
  que un POST directo sin pasar por ella no retira nada.
- Consultas preparadas, salida con `h()`, y en el navegador todo texto dinámico se escribe con `textContent`
  (los avisos nunca se insertan como HTML). Los errores del panel se registran en `logs/formularios.log`
  (nombres de campo, nunca el contenido).
- Un aviso solo se puede marcar como leído si es visible para el rol de la persona; el id se valida como entero.

## 4. Decisiones

| Tema | Decisión |
|---|---|
| Hora mostrada | `publicada_en` guarda la hora del **evento** (`ahora_evento()`), no la real: así tiene sentido con el reloj de la demo. `creada_en` guarda la hora real (auditoría). |
| Orden | Por id (orden de publicación), no por hora simulada: lo último publicado siempre queda arriba aunque se mueva el reloj. |
| Retirar | Marca `retirada_en`; no borra filas, para conservar el conteo de lecturas. |
| Audiencia | `'todos'` o la clave de un rol (`estudiante`, `inversionista`, `corporativo`, `expositor`, `staff`, `admin`). |
| Sin sesión | El centro de avisos pide iniciar sesión (la lectura se guarda por persona). |
| Extras sobre ARQUITECTURA.md | Columnas `nivel`, `publicada_en` y `retirada_en` en `notificaciones`. |
| Push del navegador | No (decisión de ARQUITECTURA.md): solo alertas dentro de la app. |

## 5. Pruebas hechas (navegador integrado, MAMP, dos sesiones)

Admin en `localhost` y estudiante en `127.0.0.1` (cookies separadas); estudiante a 375 px de ancho.

- El admin publica y el aviso **aparece solo** en la lista del estudiante (insignia 1 → 2, tarjeta nueva, mensaje
  «Llegó un aviso nuevo»); en Inicio sale la alerta «Nuevo aviso».
- Marcar como leído por AJAX (resumen e insignia bajan y persisten al recargar).
- Audiencia por rol: el estudiante ve los suyos y los de «todos»; el inversionista ve los suyos (no el del
  estudiante ni el de staff).
- Visitante sin sesión: `notificaciones.php` y `admin/avisos.php` redirigen al login; `api/avisos.php` responde
  401; `ahora.php` no incluye avisos; no hay campana.
- Validaciones: título/mensaje cortos y largos, audiencia y nivel inválidos (no se guarda nada y se muestran los errores).
- Doble envío: 3 POST simultáneos con el mismo token más un reenvío posterior → 1 sola fila.
- Falta de CSRF o CSRF falso → 400; sin token de formulario → «El formulario expiró…», sin guardar.
- Rol sin permiso: estudiante en `admin/avisos.php` (GET, POST y `?retirar=`) → 403, sin publicar.
- API de lectura: GET (405), sin CSRF (400), acción inválida (400), aviso de otro rol, id con letras o con
  intento de inyección (404).
- Retiro: POST directo sin confirmación no retira; la pantalla de confirmación no cambia nada; dos envíos del
  segundo paso retiran una sola vez; el aviso retirado desaparece en vivo de la lista del estudiante y su
  conteo de lecturas se conserva.
- Texto con `<b>` y `<script>` en título y mensaje: se muestra como texto, sin ejecutarse.
- Pendiente menor cerrado: guardar el reloj en `admin/reloj.php` **vuelve a armar las alertas de la agenda**
  (con una alerta descartada, al guardar el reloj reaparece). El reloj quedó como estaba (hora fija, 10 mar 2027 11:40).
- Mapa y centro de avisos revisados a 375 px de ancho.
- No probado: celular real (cámara del escáner y mapa los prueba Cris).

## 6. Archivos nuevos o cambiados

- Nuevos: `includes/avisos.php`, `app/notificaciones.php`, `app/api/avisos.php`, `app/admin/avisos.php`,
  `db/migraciones/007_notificaciones.sql`, `db/seed/006_seed_avisos.sql`, `docs/FASE5.md`.
- Cambiados: `includes/app.php` (carga `avisos.php`), `includes/app_header.php` (campana y `data-avisos-max`),
  `app/api/ahora.php` (campo `avisos`), `app/admin/index.php` (tarjeta activa), `assets/js/app.js` (sondeo de avisos,
  centro de avisos, contador y doble envío), `assets/css/app.css` (sección «Fase 5»), `README.md`.
