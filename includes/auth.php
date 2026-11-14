<?php
/**
 * UDG NEXUS — Sesiones, roles y protección CSRF.
 * Se carga desde includes/app.php (después de db(), consulta() y h()).
 */

/** Roles de la app y su nombre visible. */
const ROLES = [
    'estudiante'    => 'Estudiante o público general',
    'inversionista' => 'Inversionista',
    'corporativo'   => 'Corporativo o comprador',
    'expositor'     => 'Expositor o investigador',
    'staff'         => 'Staff del evento',
    'admin'         => 'Administrador',
];

/** Roles que una persona puede elegir al crear su propia cuenta. */
const ROLES_PUBLICOS = ['estudiante', 'inversionista', 'corporativo', 'expositor'];

/* ============================================================
   Sesión
   ============================================================ */

function sesion_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('nexus_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Ruta web de la carpeta /app, sin barra final (ej. /udg-nexus/app). */
function base_app(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/app/x.php'));
    foreach (['/admin', '/api'] as $sub) {
        if (substr($dir, -strlen($sub)) === $sub) {
            $dir = substr($dir, 0, -strlen($sub));
        }
    }
    return rtrim($dir, '/');
}

/** Prefijo relativo hacia /app desde la página actual ('' o '../'). */
function prefijo_app(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return substr($dir, -6) === '/admin' ? '../' : '';
}

/** Prefijo relativo hacia la raíz del sitio ('../' o '../../'). */
function prefijo_raiz(): string
{
    return prefijo_app() . '../';
}

/**
 * Acepta solo rutas internas de la app para volver tras iniciar sesión
 * (evita redirecciones a otros sitios). Devuelve null si no es segura.
 */
function ruta_segura(?string $ruta): ?string
{
    if ($ruta === null || $ruta === '') {
        return null;
    }
    if (strpos($ruta, base_app() . '/') !== 0) {
        return null;
    }
    if (strpos($ruta, '//') !== false || strpos($ruta, '..') !== false || preg_match('/[\r\n\\\\]/', $ruta)) {
        return null;
    }
    return $ruta;
}

/* ============================================================
   Usuario actual y permisos
   ============================================================ */

/** Usuario de la sesión (se lee de la base en cada petición), o null. */
function usuario_actual(): ?array
{
    static $cargado = false, $usuario = null;
    if ($cargado) {
        return $usuario;
    }
    $cargado = true;
    $id = (int) ($_SESSION['uid'] ?? 0);
    if ($id > 0) {
        $usuario = consulta_uno(
            'SELECT id, nombre, correo, rol, institucion, intereses FROM usuarios WHERE id = ?',
            'i',
            [$id]
        );
        if ($usuario === null) {
            unset($_SESSION['uid']);   // la cuenta ya no existe
        }
    }
    return $usuario;
}

function tiene_rol(string ...$roles): bool
{
    $u = usuario_actual();
    return $u !== null && in_array($u['rol'], $roles, true);
}

function requiere_login(): void
{
    if (usuario_actual() !== null) {
        return;
    }
    $destino = base_app() . '/login.php?volver=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: ' . $destino);
    exit;
}

/** Exige sesión y uno de los roles indicados; si no, muestra "sin acceso". */
function requiere_rol(string ...$roles): void
{
    requiere_login();
    if (tiene_rol(...$roles)) {
        return;
    }
    http_response_code(403);
    $titulo = 'Sin acceso';
    $activa = '';
    require __DIR__ . '/app_header.php';
    echo '<div class="proximamente"><div class="circulo-grande">' . icono('gafete') . '</div>'
       . '<h1>Sin acceso</h1>'
       . '<p class="suave">Tu cuenta (' . h(ROLES[usuario_actual()['rol']] ?? '') . ') no tiene permiso para ver esta sección.</p>'
       . '<a class="btn-app" href="' . h(prefijo_app()) . 'inicio.php">Volver al inicio</a></div>';
    require __DIR__ . '/app_footer.php';
    exit;
}

/* ============================================================
   CSRF
   ============================================================ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

/** Detiene la petición si el token del formulario no coincide. */
function csrf_validar(): void
{
    $enviado = (string) ($_POST['csrf'] ?? '');
    if ($enviado === '' || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(400);
        exit('La sesión del formulario expiró. Regresa, recarga la página e inténtalo de nuevo.');
    }
}

/* ============================================================
   Inicio y cierre de sesión
   ============================================================ */

function iniciar_sesion_usuario(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    unset($_SESSION['login_fallos'], $_SESSION['login_ultimo']);
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'] ?? false, true);
    }
    session_destroy();
}

/** Segundos de bloqueo restantes tras demasiados intentos fallidos, o null. */
function login_bloqueado(): ?int
{
    $fallos = (int) ($_SESSION['login_fallos'] ?? 0);
    $ultimo = (int) ($_SESSION['login_ultimo'] ?? 0);
    if ($fallos >= 5) {
        $resta = 60 - (time() - $ultimo);
        if ($resta > 0) {
            return $resta;
        }
        unset($_SESSION['login_fallos'], $_SESSION['login_ultimo']);
    }
    return null;
}

/** Intenta iniciar sesión. Devuelve null si salió bien, o el mensaje de error. */
function intentar_login(string $correo, string $clave): ?string
{
    if (($resta = login_bloqueado()) !== null) {
        return 'Demasiados intentos. Espera ' . $resta . ' segundos e inténtalo de nuevo.';
    }
    $u = consulta_uno('SELECT id, password_hash FROM usuarios WHERE correo = ?', 's', [trim($correo)]);
    if ($u !== null && password_verify($clave, $u['password_hash'])) {
        iniciar_sesion_usuario((int) $u['id']);
        return null;
    }
    $_SESSION['login_fallos'] = (int) ($_SESSION['login_fallos'] ?? 0) + 1;
    $_SESSION['login_ultimo'] = time();
    return 'Correo o contraseña incorrectos.';
}
