# UDG NEXUS — Sitio web del proyecto

Sitio del proyecto final de Gestión de Ferias y Exposiciones: la feria de
transferencia tecnológica **UDG NEXUS**. PHP puro + HTML/CSS/JS (sin
frameworks) con formularios que guardan directamente en MySQL.

## Estructura del proyecto

```
udg-nexus/
├── index.php              Inicio — La idea, oportunidad y viabilidad
├── experiencia.php         Zonificación, comité organizador y cronograma
├── patrocinadores.php      Patrocinadores y aliados por categoría
├── registro.php            Enlace al registro con boleto (app) + solicitud de cita B2B
├── contacto.php             Contacto / interés en patrocinio (formulario)
├── procesar_cita.php          Backend: guarda la solicitud de cita B2B
├── procesar_contacto.php      Backend: guarda el mensaje de contacto
├── config.php              Conexión a MySQL (edítalo si tu MAMP es distinto)
├── includes/
│   ├── header.php          <head> + barra de navegación
│   └── footer.php          Pie de página + script
├── assets/
│   ├── css/style.css       Toda la identidad visual
│   └── js/main.js          Menú móvil + validación de formularios
└── db/
    └── udg_nexus.sql       Esquema de la base de datos (3 tablas)
```

## 1. Preparar MAMP

1. Abre **MAMP** y dale clic a **Start Servers** (Apache y MySQL deben
   quedar en verde).
2. Clic en **WebStart** o **Open WebStart page** para confirmar que
   corre; ahí mismo puedes entrar a **phpMyAdmin**.
3. En **phpMyAdmin**:
   - Ve a la pestaña **Importar**.
   - Selecciona el archivo `db/udg_nexus.sql` de este proyecto.
   - Dale **Continuar/Ejecutar**. Esto crea la base `udg_nexus` con sus
     3 tablas: `acreditaciones`, `citas_b2b` y `contactos_patrocinio`.

## 2. Colocar el proyecto donde MAMP lo sirve

Copia toda la carpeta `udg-nexus/` dentro de la carpeta `htdocs` de MAMP:

- **Mac:** `/Applications/MAMP/htdocs/`
- **Windows:** `C:\MAMP\htdocs\`

Quedando, por ejemplo: `MAMP/htdocs/udg-nexus/`

## 3. Verificar `config.php`

Por defecto MAMP usa usuario `root` y password `root`. Si tu MAMP usa un
puerto distinto para MySQL (por ejemplo `8889`), edita la primera línea
de `config.php`:

```php
define('DB_HOST', 'localhost:8889'); // en vez de 'localhost'
```

(Puedes ver el puerto exacto en MAMP → Preferencias → Ports.)

## 4. Abrir el sitio

Con Apache y MySQL corriendo, abre en el navegador:

```
http://localhost:8888/udg-nexus/index.php
```

(El puerto `8888` es el default de Apache en MAMP; si el tuyo es otro,
ajusta la URL — también visible en MAMP → Preferencias → Ports.)

## 5. Trabajar en Visual Studio (Code)

1. Abre la carpeta `udg-nexus/` directamente en VS Code
   (`File → Open Folder...`).
2. Instala la extensión **PHP Intelephense** (autocompletado/validación)
   si no la tienes.
3. No necesitas un servidor local de VS Code para PHP — MAMP ya cumple
   ese rol. Edita el código en VS Code y recarga el navegador apuntando
   a la URL de MAMP para ver los cambios.

## Qué sí funciona de verdad (y qué no)

- Los formularios del sitio (cita B2B y contacto) sí
  validan los campos obligatorios y sí insertan los registros en
  MySQL — puedes revisarlos después en phpMyAdmin, pestaña **Examinar**
  de cada tabla.
- No hay login de usuarios ni panel de administración — fue una
  decisión explícita para mantener el alcance de "demo académico con
  formularios reales". Si más adelante quieres un panel simple para ver
  los registros desde el navegador (en vez de phpMyAdmin), dímelo y lo
  agregamos.
- La app móvil (`/app`) incluye un **mapa del recinto** (`app/mapa.php`): plano SVG
  con zonas, stands y pines enlazados a las fichas de proyecto. Las coordenadas
  viven en la base; instrucciones de importación en `docs/FASE4.md`.
- No se incluyen imágenes/fotos reales de personas, marcas o del CCU
  (por restricciones de derechos de imagen); el diseño usa solo
  tipografía, color y elementos gráficos propios.

## Cómo ver los registros guardados

En phpMyAdmin → base `udg_nexus` → clic en cada tabla → pestaña
**Examinar**:

- `acreditaciones` — histórico del antiguo formulario de acreditación (ya
  no se llena: el registro con boleto y gafete QR vive en la app, ver
  `docs/FASE3.md`).
- `citas_b2b` — todas las solicitudes de cita 1:1.
- `contactos_patrocinio` — todos los mensajes del formulario de
  Contacto.

## Siguientes pasos posibles (si quieres ampliarlo)

- Panel de administración simple (listar/exportar registros sin entrar
  a phpMyAdmin).
- Envío de correo de confirmación automático al registrarse (requiere
  configurar SMTP).
- Exportar las acreditaciones a Excel/CSV para el equipo de logística.
