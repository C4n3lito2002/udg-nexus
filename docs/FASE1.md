# Fase 1 — Catálogo, buscador y Home de la app

## 1. Importar las tablas y los datos (una sola vez)

En phpMyAdmin (MAMP), con la base `udg_nexus` ya creada por `db/udg_nexus.sql`:

1. Selecciona la base `udg_nexus` → pestaña **Importar**.
2. Importa `db/migraciones/001_catalogo.sql` (crea las tablas nuevas).
3. Importa `db/seed/001_seed_catalogo.sql` (carga los datos de demostración).

El paso 3 se puede repetir cuando quieras: borra y vuelve a crear los datos de demostración sin duplicarlos.
Tus registros reales de los formularios (`acreditaciones`, `citas_b2b`, `contactos_patrocinio`) NO se tocan.

## 2. Abrir la app

- Sitio: http://localhost:8888/udg-nexus/index.php (botón **Ver la app** en el menú)
- App directa: http://localhost:8888/udg-nexus/app/inicio.php

(Ajusta el puerto 8888 si el tuyo es otro.)

## 3. Reloj de la demo

La app usa una hora simulada para que el feed "En este momento" siempre tenga actividades.
En phpMyAdmin → tabla `config_evento`:

| clave | valor por defecto | qué hace |
|---|---|---|
| `evento_inicio` | `2027-03-10` | Primer día del evento (fecha ficticia). Las actividades se calculan desde aquí. |
| `evento_dias` | `3` | Duración del evento. |
| `reloj_simulado` | `2027-03-10 11:40:00` | Hora que usa la app. **Déjalo vacío** para usar la hora real. |

Para cambiar el momento que se muestra, edita `reloj_simulado` (formato `AAAA-MM-DD HH:MM:SS`).
Si cambias `evento_inicio`, vuelve a importar el seed para que las actividades se muevan a esa fecha.

## 4. Qué incluye esta fase

- Home: buscador universal, accesos directos, "En este momento", vitrina rotativa (TRL 7+ o buscan inversión), avisos y patrocinadores.
- Catálogo de proyectos con filtros (área, TRL, busca inversión) y fichas con equipo, ubicación y datos para negociar.
- Perfiles de empresas, fondos y compradores, con proyectos afines a su interés.
- Buscador: sugerencias en vivo (`app/api/buscar.php`) y página de resultados.
- Agenda, Mapa y Gafete: pantallas "Disponible muy pronto" (siguientes fases).

## 5. Si la conexión a MySQL falla

Si tu MySQL de MAMP usa otro puerto, copia `config.example.php` como `config.local.php` y define ahí
`DB_HOST` (por ejemplo `localhost`) y `DB_PORT` (por ejemplo `8889`).
