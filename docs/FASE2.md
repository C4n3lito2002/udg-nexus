# Fase 2 — Cuentas, roles y reloj del evento

Fase 2 completa: parte 1 (cuentas, roles y reloj) y parte 2 (agenda personal con alertas, al final de este documento).

## 1. Importar lo nuevo (una sola vez)

En phpMyAdmin, con la base `udg_nexus` seleccionada, pestaña **Importar**, en este orden:

1. `db/migraciones/002_usuarios.sql` (tabla `usuarios`).
2. `db/migraciones/003_reloj.sql` (claves nuevas del reloj en `config_evento`).
3. `db/seed/002_seed_usuarios.sql` (un usuario de demostración por rol).

Los tres se pueden repetir sin duplicar datos.

## 2. Cuentas de demostración

Contraseña de todas: `nexus2027`

| Rol | Correo |
|---|---|
| Administrador | admin@udgnexus.test |
| Staff | staff@udgnexus.test |
| Expositor / investigador | expositor@udgnexus.test |
| Inversionista | inversionista@udgnexus.test |
| Corporativo / comprador | corporativo@udgnexus.test |
| Estudiante / público | estudiante@udgnexus.test |

La pantalla de inicio de sesión (`app/login.php`) las lista y llena el correo con un toque.
Cualquier persona también puede crear su propia cuenta en `app/registro_app.php`
(solo con roles de público: estudiante, inversionista, corporativo o expositor; staff y administrador solo existen por seed).

## 3. Qué incluye

- Inicio y cierre de sesión, con contraseñas cifradas (`password_hash`), id de sesión regenerado al entrar y bloqueo de 60 s tras 5 intentos fallidos.
- Token CSRF en los formularios de la app.
- Roles verificados en cada página protegida (`requiere_login()` y `requiere_rol()` en `includes/auth.php`). Una cuenta sin permiso ve "Sin acceso" (HTTP 403).
- `app/perfil.php` con los datos de la cuenta y cierre de sesión.
- Panel de administración (`app/admin/`) solo para el rol administrador.

## 4. Reloj del evento

Panel: **Administración → Reloj del evento** (`app/admin/reloj.php`).

| Modo | Qué hace |
|---|---|
| Avanza sola | Arranca en la hora que elijas y corre sola, a velocidad x1, x10, x60 o x300. Sirve para que las alertas salten en vivo durante la demo. |
| Hora fija | Se queda quieta en la hora elegida. |
| Hora real | Usa la hora de la computadora. |

Hay botones para saltar a momentos clave (Día 1 inauguración, media mañana, tarde; Día 2 y Día 3).
Las claves viven en `config_evento`: `reloj_modo`, `reloj_base`, `reloj_ancla`, `reloj_velocidad`.
Si no se importó la migración 003, la app sigue usando la clave anterior `reloj_simulado` (hora fija).

## 5. Notas

- Las rutas de la app son relativas al servidor, así que funcionan igual con `localhost` y con la IP de la computadora (para probar desde celulares en el mismo wifi).
- Los formularios del sitio institucional (`procesar_*.php`) todavía no tienen CSRF ni protección contra doble envío; se resuelve junto con el registro con boleto de la Fase 3.


---

# Fase 2, parte 2 — Agenda personal con alertas

## 6. Importar lo nuevo (una sola vez)

En phpMyAdmin, base `udg_nexus`, pestaña **Importar**, en este orden:

1. `db/migraciones/004_agenda.sql` (tabla `agenda_usuario`).
2. `db/seed/003_seed_agenda.sql` (opcional): deja lista la cuenta `estudiante@udgnexus.test` con 4 actividades, dos de ellas empalmadas, y una alerta que salta con el reloj en 10 mar 2027 11:40.

Ambos se pueden repetir sin duplicar datos. Ojo: `db/seed/001_seed_catalogo.sql` vacía `actividades` y `config_evento`; si lo vuelves a importar, vuelve a importar también la migración 003 (reloj) y el seed 003 (agenda).

## 7. Qué incluye

- **Pestaña Agenda** (`app/agenda.php`):
  - *Programa*: por día (Día 1 a 3) y agrupado **por hora** o **por zona**. Se puede ver sin sesión; para agregar se pide entrar.
  - *Mi agenda* (`?ver=mia`): tus actividades por día, con resumen ("4 actividades · 1 empalme de horario").
- **Agregar / quitar por AJAX** (`app/api/agenda_toggle.php`, solo POST, con sesión y token CSRF). Al agregar, la alerta queda en 15 min por omisión; cada actividad tiene su selector (sin alerta, 15, 30 o 60 min antes).
- **Empalmes de horario**: cada tarjeta avisa "Se empalma con «X» (hora a hora)" y, si todavía no la agregaste, "Si la agregas, se empalma con…". Dos actividades que se tocan en el límite (una termina a las 13:00 y otra empieza a las 13:00) no cuentan como empalme.
- **Alertas dentro de la app**: `app/api/ahora.php` devuelve la hora del evento y las alertas pendientes; `assets/js/app.js` lo consulta cada 5 s y muestra la alerta en cualquier pantalla, con insignia en la pestaña Agenda. La alerta se abre `minutos` antes del inicio y sigue hasta que termina la actividad; "Entendido" la descarta.
- Las alertas usan **el reloj del evento**, no la hora real. Al guardar el reloj en `admin/reloj.php`, las alertas descartadas se vuelven a armar (así la demo se puede repetir).
- Las horas y el estado de cada tarjeta (En curso, Empieza en N min, Finalizada) se mueven solos cuando el reloj está en modo "avanza sola".

## 8. Probar la demo de alertas

1. Entra con `estudiante@udgnexus.test` (contraseña `nexus2027`). Con el reloj en hora fija 10 mar 2027 11:40 salta la alerta de "Demostración en vivo: RehabGlove y NeuroBand" (empieza 12:00, alerta de 30 min).
2. En **Administración → Reloj del evento** pon modo "Avanza sola", velocidad x60, desde las 11:00 del día 1: las alertas van saltando en vivo.
3. En **Mi agenda** verás las actividades 3 y 4 del día 1 marcadas como empalmadas.

## 9. Notas técnicas

- `agenda_usuario(usuario_id, actividad_id, alerta_minutos, alerta_vista, agregado_en)`; clave compuesta; borrado en cascada con usuarios y actividades.
- Sin migración 004 la página `agenda.php` muestra un aviso y no ofrece botones; el resto de la app sigue funcionando.
- `ahora.php` libera la sesión antes de responder para que las consultas periódicas no bloqueen otras peticiones.

---

# Endurecimiento de los formularios del sitio (acreditación, cita B2B y contacto)

## 10. Qué cambió

Afecta a `procesar_acreditacion.php`, `procesar_cita.php`, `procesar_contacto.php` y a las páginas `registro.php` y `contacto.php`. Todo vive en `includes/forms.php`.

- **CSRF y doble envío con un solo mecanismo:** cada formulario trae un token de un solo uso ligado a la sesión (`form_campo()`). El procesador lo consume (`form_abrir()`):
  - sin token, con token de otro formulario o vencido (2 h): no se guarda nada y la página muestra "El formulario expiró o ya se había enviado. Recarga la página…" (`?acred=expirado`, `?cita=expirado`, `?estado=expirado`);
  - token ya usado (doble clic, botón "atrás", reenvío del POST): **no se vuelve a insertar** y se muestra el resultado del primer envío. Probado con 3 envíos simultáneos: 1 sola fila.
- **Registro de errores:** `logs/formularios.log` guarda validaciones fallidas (solo el nombre de los campos), tokens inválidos, dobles envíos y errores de la base de datos (`prepare` o `insert`). Nunca guarda lo que escribió la persona. Se rota a `.log.1` al pasar de 1 MB. La carpeta `logs/` tiene un `.htaccess` que impide abrirla desde el navegador (probado: 403). Si no se puede escribir el archivo, se usa el log de PHP.
- Si falla la conexión a MySQL, `config.php` también lo escribe en el log de PHP.
- **Botón:** además de deshabilitarse al enviar, vuelve a su texto si la persona regresa con "atrás" (`main.js`).
- La sesión (`nexus_sid`) ahora también se abre en `registro.php` y `contacto.php`; es la misma cookie de la app.
- `h()` de `includes/app.php` quedó protegida con `function_exists` para convivir con `forms.php`.

## 11. Para ver los errores

Abre `logs/formularios.log` en VS Code. Ejemplo de líneas:

```
[2026-11-14 16:19:44] acreditacion | doble_envio | ip=::1 | se ignora el reenvio; resultado previo: ok
[2026-11-14 16:19:44] cita_b2b | validacion | ip=::1 | campos invalidos: correo
```
