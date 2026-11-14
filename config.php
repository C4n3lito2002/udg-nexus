<?php
/**
 * Configuración de conexión a la base de datos (MAMP).
 *
 * Los valores sensibles viven en config.local.php (NO se sube a Git).
 * Si ese archivo no existe, se usan los valores por defecto de MAMP:
 *   - Host:     localhost   (otro puerto: 'localhost:8889' o define DB_PORT)
 *   - Usuario:  root
 *   - Password: root
 *
 * Para personalizar: copia config.example.php como config.local.php
 * y edita ahí tus datos.
 */

$configLocal = __DIR__ . '/config.local.php';
if (is_file($configLocal)) {
    require $configLocal;
}

defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', 'root');
defined('DB_NAME') || define('DB_NAME', 'udg_nexus');

/**
 * Conexión global vía mysqli.
 * Se usa en los scripts procesar_*.php
 */
function obtenerConexion(): mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);
    // mysqli no entiende 'host:puerto' dentro del host: se separa aquí.
    // También se puede definir DB_PORT en config.local.php.
    $host = DB_HOST;
    $puerto = null;
    if (defined('DB_PORT') && DB_PORT !== '') {
        $puerto = (int) DB_PORT;
    } elseif (preg_match('/^(.+):(\d+)$/', $host, $m)) {
        $host = $m[1];
        $puerto = (int) $m[2];
    }
    $conexion = $puerto
        ? @new mysqli($host, DB_USER, DB_PASS, DB_NAME, $puerto)
        : @new mysqli($host, DB_USER, DB_PASS, DB_NAME);

    if ($conexion->connect_error) {
        http_response_code(500);
        die(
            '<div style="font-family: sans-serif; padding: 40px; max-width: 600px; margin: 60px auto; ' .
            'background:#fff5f5; border:1px solid #f1b4b4; border-radius:10px;">' .
            '<h2 style="color:#b42318;">No se pudo conectar a la base de datos</h2>' .
            '<p>Verifica que:</p>' .
            '<ul>' .
            '<li>MAMP esté corriendo (Apache + MySQL en verde).</li>' .
            '<li>Hayas importado <code>db/udg_nexus.sql</code> en phpMyAdmin.</li>' .
            '<li>Los datos en <code>config.php</code> o <code>config.local.php</code> (host, usuario, password) coincidan con tu MAMP.</li>' .
            '</ul>' .
            '<p style="color:#888; font-size: 0.9em;">Detalle técnico: ' . htmlspecialchars($conexion->connect_error ?? '') . '</p>' .
            '</div>'
        );
    }

    $conexion->set_charset('utf8mb4');
    return $conexion;
}

/** Año actual para el footer */
define('ANIO_ACTUAL', date('Y'));
