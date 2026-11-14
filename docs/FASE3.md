# Fase 3 — Registro con boleto, gafete QR y escáner de acceso

Reemplaza al formulario de acreditación del sitio: ahora la persona se registra en la app, elige un boleto
(gratis o con pago **simulado**) y recibe un gafete con código QR. El staff valida el QR en la entrada y cada
boleto entra **una sola vez**. La tabla `acreditaciones` queda como histórico (ya no se llena).

## 1. Importar lo nuevo (una sola vez)

En phpMyAdmin, base `udg_nexus`, pestaña **Importar**, en este orden:

1. `db/migraciones/005_boletos.sql` (tablas `tipos_boleto`, `boletos` y `accesos`).
2. `db/seed/004_seed_boletos.sql` (4 tipos de boleto y 3 boletos de demo).

Ambos se pueden repetir sin duplicar datos. Repetir el seed 004 **reinicia la demo**: deja los boletos de demo
como "vigente" y borra sus lecturas del escáner. (También se puede reiniciar desde `Administración → Boletos y accesos`.)

## 2. Boletos de demostración

| Cuenta | Boleto | Estado | Token (el que lleva el QR) |
|---|---|---|---|
| estudiante@udgnexus.test | Estudiante (NX-000001) | vigente | `a4bf9a423c9a101a18adef73e11d85de` |
| inversionista@udgnexus.test | Profesional (NX-000002) | vigente | `6afceec67a2b45010483bdc6dd085d2d` |
| corporativo@udgnexus.test | Profesional (NX-000003) | cancelado | `7a3549c1ccb305fdad89282898fb2263` |
| expositor@udgnexus.test | sin boleto | — | para probar el registro y la compra |

Los tokens del seed son fijos **solo para la demostración**; los boletos que genera la app usan `random_bytes()`.
Contraseña de todas las cuentas: `nexus2027` (ver `docs/FASE2.md`).

## 3. Cómo funciona

- **Tipos de boleto** (precios ficticios, en pesos): Estudiante (gratis, cupo 400), Público general ($120, cupo 300),
  Profesional ($850, cupo 150) y Expositor (gratis, cupo 100). Se recomienda el que corresponde al rol.
- **Registro** (`app/registro_app.php`): crea la cuenta y pide elegir boleto (se preselecciona según el rol).
  Si es gratis, se emite al momento y se abre el gafete; si tiene costo, pasa a la confirmación de compra.
- **Compra simulada** (`app/boletos.php?comprar=ID`): resumen y botón "Pagar (simulado)". **No se pide ni se
  guarda ningún dato de tarjeta**: el boleto queda con una referencia ficticia (`SIM-XXXXXXXX`). Una persona solo
  puede tener un boleto vigente o usado; el cupo se respeta aunque lleguen compras al mismo tiempo (transacción
  que bloquea a la persona y al tipo).
- **Gafete** (`app/gafete.php`, pestaña Gafete): logo sobre fondo blanco, nombre, tipo, folio, fechas, sede y el QR.
  El QR contiene **solo el token** (16 bytes aleatorios en hexadecimal = 32 caracteres): ningún dato personal.
  Hay botón para ampliarlo a pantalla completa y un "código de respaldo" por si la cámara no lo lee. Un boleto
  usado se ve atenuado con el sello "Usado"; uno cancelado no muestra QR.
- **Sin internet**: el QR se dibuja en el navegador con `assets/js/vendor/qrcode.js` (copia local; no se carga nada
  de un CDN). Probado: la página del gafete no hace ninguna petición a otro origen. Licencias en
  `assets/js/vendor/LICENCIAS.txt`.
- **Escáner** (`app/admin/escaner.php`, roles staff y administrador): lee el QR con la cámara (usa
  `BarcodeDetector` si el navegador lo trae; si no, `jsQR` local, que solo se descarga en esa pantalla) o recibe
  el código tecleado. Resultados: *Acceso permitido*, *Boleto ya utilizado* (con la hora de la primera entrada),
  *Boleto cancelado* y *Código no válido*. Cada lectura queda en `accesos`.
- **Una sola vez**: `boleto_validar_entrada()` hace `UPDATE boletos SET estatus='usado' ... WHERE estatus='vigente'`
  y mira las filas afectadas; la base garantiza que solo una lectura cambia la fila aunque lleguen varias al mismo tiempo.
- **Panel** (`app/admin/boletos.php`, solo administrador): totales por tipo, últimas lecturas y reinicio de la demo
  (los usados vuelven a vigentes y se borra la bitácora; pide confirmación con un segundo paso).
- **API**: `app/api/checkin.php` (solo POST, solo staff/admin, con token CSRF) devuelve JSON con el resultado.

## 4. Usar la cámara en la demo

Los navegadores solo dejan abrir la cámara en **`localhost` o con HTTPS**. Si la demo se abre por la IP de la
computadora (`http://192.168.x.x:8888/...`) desde un celular, la cámara **no estará disponible** y el escáner
muestra un aviso; en ese caso se usa el campo de código (se puede teclear el código de respaldo del gafete) o se
escanea desde la laptop con su cámara abierta en `localhost`.

## 5. Archivos nuevos o cambiados

- Nuevos: `includes/boletos.php`, `app/gafete.php`, `app/boletos.php`, `app/api/checkin.php`,
  `app/admin/escaner.php`, `app/admin/boletos.php`, `assets/js/gafete.js`, `assets/js/escaner.js`,
  `assets/js/boletos.js`, `assets/js/vendor/*`, `db/migraciones/005_boletos.sql`, `db/seed/004_seed_boletos.sql`.
- Cambiados: `app/registro_app.php` (elige boleto), `app/perfil.php` (boleto y acceso del staff),
  `app/admin/index.php`, `includes/app_footer.php` (pestaña Gafete y `$scripts`), `includes/app.php`,
  `app/inicio.php`, `app/proximamente.php`, `registro.php` (la acreditación ahora enlaza al registro de la app),
  `assets/css/app.css`, `assets/css/style.css`, `README.md`.
- `procesar_acreditacion.php` ya no se usa y se movió a `eliminar/` (excluida de Git).
