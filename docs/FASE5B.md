# Fase 5B — Recomendaciones por perfil ("Para ti")

Recomendaciones de actividades y proyectos según el rol y los intereses de cada persona, con **reglas simples y sin
inteligencia artificial**. Cada recomendación explica su motivo («Porque te interesa Salud»). Es el último módulo
«Si alcanza» del plan; la Fase 6 queda solo para pruebas y cierre.

## 1. Importar lo nuevo (una sola vez)

En phpMyAdmin, base `udg_nexus`, pestaña **Importar**, en este orden:

1. `db/migraciones/008_recomendaciones.sql` (tabla `actividad_area` y columna `usuarios.proyecto_id`).
2. `db/seed/007_seed_recomendaciones.sql` (ejes de las 16 actividades y el proyecto del expositor de demo).

Ambos se pueden repetir sin duplicar. Si falta la migración, la pantalla «Para ti» muestra un aviso y el bloque del
Home no aparece; el resto de la app sigue igual.

**Aviso:** reimportar `001_seed_catalogo.sql` vacía `actividades` y deja `actividad_area` con ejes que ya no
corresponden; después hay que reimportar el seed 007 (junto con la migración 003, el seed 003 de agenda y el seed 005
de mapa, como ya se indicaba).

## 2. Qué incluye

- **Bloque «Para ti» en el Home** (`app/inicio.php`): 4 tarjetas entre «En este momento» y la vitrina, con «Ver todas».
  Un visitante sin sesión ve una invitación a iniciar sesión; staff y admin no ven nada.
- **Pantalla `app/recomendaciones.php`** (requiere sesión): tus ejes de interés, hasta 6 actividades y 6 proyectos, y un
  desplegable «¿Cómo se eligen?» con las reglas de tu rol. Staff y admin ven que no aplica a su rol.
- **Misma tarjeta que la Agenda**: las actividades recomendadas traen el botón *Agregar a mi agenda*, la alerta, la marca
  «En tu agenda» y el aviso de empalme, todo por AJAX con `api/agenda_toggle.php` (sin código nuevo en el servidor ni en
  `app.js`). Los proyectos usan la tarjeta del catálogo. Ambas llevan arriba el motivo con una estrella.
- `tarjeta_agenda()` y `estado_agenda()` pasaron de `app/agenda.php` a `includes/app.php` para poder reutilizarlas; la Agenda
  se ve y funciona igual. `tarjeta_agenda()` y `tarjeta_proyecto()` aceptan un `$motivo` opcional.

## 3. Reglas por rol

Cada regla suma puntos; se muestran primero los que más tienen. Con empate, las actividades van por hora de inicio y los
proyectos por TRL.

| Rol | Regla | Puntos | Motivo |
|---|---|---|---|
| Estudiante | Taller o Conferencia de uno de sus ejes | 40 | Porque te interesa Salud |
| | Presentación de uno de sus ejes | 22 | Porque te interesa Software |
| | Taller o Conferencia general (sin eje) | 10 | Útil para estudiantes |
| Inversionista | Proyecto que busca inversión y es de sus ejes | 40 + TRL | Porque te interesa Salud · Busca inversión |
| | Rueda de negocios de sus ejes | 32 | Porque te interesa Energía |
| | Proyecto que busca inversión de otro eje | 10 + TRL | Busca inversión · Otro eje: Agroindustria |
| | Otra rueda de negocios | 8 | Rueda de negocios para inversionistas |
| Corporativo | Proyecto de sus ejes con TRL 7 o más | 40 + TRL | Porque te interesa Manufactura · TRL 8 |
| | Reverse Pitch de sus ejes | 38 | Porque te interesa Agroindustria |
| | Otro Reverse Pitch | 10 | Reverse Pitch: retos para empresas |
| Expositor | Actividad en la zona de su proyecto | 40 | Es en tu zona: Demo Arena |
| | Rueda de negocios del eje de su proyecto (sus citas) | 34 | Rueda de negocios de tu eje: Energía |
| | Rueda de negocios de sus ejes de interés | 30 | Porque te interesa Ciudades |
| | Cualquier actividad de sus ejes | 15 | Porque te interesa Ciudades |
| Staff y admin | No aplica | — | — |

Ajustes comunes:

- Con **varios ejes en común** el motivo los junta («Porque te interesan Manufactura y Energía») y suma 5 puntos por eje extra.
- Lo que **ya terminó** según `ahora_evento()` no se recomienda (el reloj de la demo manda).
- Lo que **ya está en la agenda** resta 15 puntos para que suban las novedades, pero se muestra con «En tu agenda».
- **Sin ejes de interés** (cuenta sin ejes marcados): estudiante recibe talleres y conferencias generales; inversionista, los
  proyectos que buscan inversión («Destacado del evento · Busca inversión»); corporativo, los de TRL 7 o más («Alta madurez
  tecnológica · TRL 8»); y la pantalla avisa que son recomendaciones generales.
- **Expositor sin proyecto** (`usuarios.proyecto_id` vacío): no hay zona, así que solo se recomiendan ruedas y actividades de
  sus ejes, y la pantalla lo explica.

## 4. Datos y decisiones

| Tema | Decisión |
|---|---|
| Ejes de las actividades | Tabla `actividad_area` (N:M). Sin filas = actividad general: inauguración, panorama, taller de propiedad intelectual, financiamiento, due diligence, clausura y cóctel de networking. |
| Expositor | `usuarios.proyecto_id` (opcional). El Dr. Mauricio Lara expone SolarTejado (proyecto 12, Energía, Demo Arena). Su zona y su eje salen del proyecto. |
| «Sus citas» | No se usa `citas_b2b` (es del formulario del sitio, sin usuario ni horario). Las citas del expositor son las ruedas de negocios de su eje. |
| Presentaciones para estudiante | Se añaden a sus recomendaciones (22 puntos): los talleres y conferencias con eje no existen en el seed, y sin esto el estudiante solo vería actividades generales. |
| Cálculo | En cada petición, con consultas preparadas. No se guarda nada. Sin IA ni servicios externos. |
| Editar intereses | La app no tiene pantalla para cambiar los intereses después del registro; se eligen al crear la cuenta. |

## 5. Seguridad

- Sin entradas del usuario en el cálculo: solo el rol y los intereses de la sesión; todas las consultas de lectura van
  preparadas y la salida se escapa con `h()`. Los motivos se arman con nombres de ejes y zonas de la base y también se escapan.
- La pantalla pide sesión (`requiere_login()`); el bloque del Home solo se calcula con sesión.
- El botón de agenda reutiliza `api/agenda_toggle.php` (POST, sesión y CSRF), ya probado en la Fase 2.

## 6. Pruebas hechas (navegador integrado, MAMP)

- Migración y seed importados **dos veces** sin errores ni duplicados.
- Seis cuentas de demo: estudiante, inversionista, corporativo y expositor reciben los motivos esperados en la pantalla y en
  el Home (4 tarjetas); staff y admin ven «no aplica» y sin bloque en el Home; el visitante ve la invitación y la pantalla
  lo manda al login con `volver`.
- Casos límite: cuentas sin ejes (estudiante, inversionista y corporativo) y expositor sin proyecto, cambiando esos datos
  de la demo y **restaurándolos** de inmediato (verificado).
- Agregar y quitar una actividad recomendada por AJAX desde el Home (chip, botón y alerta); la agenda del estudiante quedó como estaba.
- Diseño revisado a 375 px sin desbordes; 25 páginas del sitio, de la app y del admin cargan sin errores de PHP.
- No probado: celular real.

## 7. Archivos nuevos o cambiados

- Nuevos: `includes/recomendaciones.php`, `app/recomendaciones.php`, `db/migraciones/008_recomendaciones.sql`,
  `db/seed/007_seed_recomendaciones.sql`, `docs/FASE5B.md`.
- Cambiados: `includes/app.php` (carga `recomendaciones.php`, icono `estrella`, `tarjeta_proyecto()` con motivo y las funciones
  de tarjeta de agenda), `app/agenda.php` (usa esas funciones), `app/inicio.php` (bloque «Para ti»), `assets/css/app.css`
  (sección «Fase 5B»), `README.md`.
