<?php
/**
 * UDG NEXUS — Protección de los formularios del sitio institucional
 * (registro.php, contacto.php y sus procesar_*.php).
 *
 *  1. CSRF + doble envío con UN solo mecanismo: cada formulario lleva un token
 *     de un solo uso ligado a la sesión (form_campo). El procesador lo consume
 *     (form_abrir): sin token válido no se guarda nada; con un token ya usado
 *     (doble clic, botón "atrás", recarga del POST) NO se vuelve a insertar y se
 *     devuelve el resultado del primer envío.
 *  2. Registro de errores en logs/formularios.log (form_log): validaciones
 *     fallidas, tokens inválidos, dobles envíos y errores de la base de datos.
 *     Solo se guardan nombres de campos y mensajes técnicos, nunca el contenido
 *     que escribió la persona.
 */

require_once __DIR__ . '/../config.php';

if (!function_exists('h')) {
    /** Escapa texto para HTML (la misma función que usa la app en /app). */
    function h($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

date_default_timezone_set('America/Mexico_City');   // las horas del log coinciden con la hora local

require_once __DIR__ . '/auth.php';   // sesion_iniciar()
sesion_iniciar();

const FORM_VIDA_TOKEN  = 7200;   // segundos que vale un formulario abierto
const FORM_VIDA_HECHOS = 3600;   // segundos que se recuerda un envío ya procesado
const FORM_MAX_TOKENS  = 50;     // formularios abiertos que se recuerdan por sesión

/* ============================================================
   Registro de errores
   ============================================================ */

/** Escribe una línea en logs/formularios.log (o en el log de PHP si no se puede). */
function form_log(string $formulario, string $evento, string $detalle = ''): void
{
    $dir = __DIR__ . '/../logs';
    $archivo = $dir . '/formularios.log';
    $detalle = trim((string) preg_replace('/[\r\n]+/', ' ', $detalle));
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $formulario . ' | ' . $evento
           . ' | ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-')
           . ($detalle !== '' ? ' | ' . mb_substr($detalle, 0, 300) : '') . PHP_EOL;

    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    if (is_file($archivo) && @filesize($archivo) > 1000000) {
        @rename($archivo, $archivo . '.1');   // rotación simple: conserva el anterior
    }
    if (@file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX) === false) {
        error_log('UDG NEXUS formularios: ' . trim($linea));
    }
}

/* ============================================================
   Token de un solo uso (CSRF + doble envío)
   ============================================================ */

function form_limpiar(): void
{
    $ahora = time();
    $_SESSION['form_pend']  = array_filter($_SESSION['form_pend']  ?? [], static fn($t) => $ahora - $t['t'] < FORM_VIDA_TOKEN);
    $_SESSION['form_hechos'] = array_filter($_SESSION['form_hechos'] ?? [], static fn($t) => $ahora - $t['t'] < FORM_VIDA_HECHOS);
    while (count($_SESSION['form_pend']) > FORM_MAX_TOKENS) {
        array_shift($_SESSION['form_pend']);
    }
}

/** Campo oculto con un token nuevo para el formulario $formulario. Va dentro del <form>. */
function form_campo(string $formulario): string
{
    form_limpiar();
    $token = bin2hex(random_bytes(24));
    $_SESSION['form_pend'][$token] = ['form' => $formulario, 't' => time()];
    return '<input type="hidden" name="form_token" value="' . h($token) . '">';
}

/**
 * Primer paso de un procesador. Devuelve null si se puede continuar (el token
 * quedó consumido), o el resultado con el que hay que redirigir:
 *   'ok' / 'error'  el mismo formulario ya se envió: no se vuelve a guardar
 *   'expirado'      token ausente, inválido, de otro formulario o vencido
 */
function form_abrir(string $formulario): ?string
{
    form_limpiar();
    $token = (string) ($_POST['form_token'] ?? '');

    if ($token !== '' && isset($_SESSION['form_hechos'][$token])) {
        $previo = $_SESSION['form_hechos'][$token];
        if ($previo['form'] === $formulario) {
            form_log($formulario, 'doble_envio', 'se ignora el reenvio; resultado previo: ' . $previo['resultado']);
            return $previo['resultado'];
        }
    }

    $pend = $_SESSION['form_pend'][$token] ?? null;
    if ($token === '' || $pend === null || $pend['form'] !== $formulario) {
        form_log($formulario, 'token_invalido', $token === '' ? 'sin token' : 'token desconocido, usado en otro formulario o vencido');
        return 'expirado';
    }

    unset($_SESSION['form_pend'][$token]);
    // Mientras se procesa, un reenvío simultáneo (se serializa con el bloqueo de sesión) verá 'error'.
    $_SESSION['form_hechos'][$token] = ['form' => $formulario, 't' => time(), 'resultado' => 'error'];
    $GLOBALS['__form_token_actual'] = $token;
    return null;
}

/** Último paso: registra si el guardado salió bien, para los reenvíos del mismo token. */
function form_cerrar(bool $exito): void
{
    $token = $GLOBALS['__form_token_actual'] ?? '';
    if ($token !== '' && isset($_SESSION['form_hechos'][$token])) {
        $_SESSION['form_hechos'][$token]['resultado'] = $exito ? 'ok' : 'error';
    }
}

/** Redirige y termina. */
function form_redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}
