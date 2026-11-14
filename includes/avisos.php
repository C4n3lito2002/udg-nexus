<?php
/**
 * UDG NEXUS — Avisos y notificaciones (Fase 5).
 * Se carga desde includes/app.php (después de db(), consulta(), ejecutar(), auth y boletos).
 *
 * Un aviso lo publica el administrador para "todos" o para un rol. Quien lo ve es la gente
 * cuyo rol coincide y mientras no esté retirado. La lectura se guarda por persona
 * (notificaciones_leidas). Retirar un aviso no lo borra: solo deja de verse.
 * La hora que se muestra es la del EVENTO (publicada_en), no la real.
 */

/** Límites del formulario del administrador (los mismos que valida el servidor). */
const AVISO_TITULO_MIN = 3;
const AVISO_TITULO_MAX = 80;
const AVISO_CUERPO_MIN = 10;
const AVISO_CUERPO_MAX = 500;

/** True si ya se importó db/migraciones/007_notificaciones.sql. */
function avisos_tablas_existen(): bool
{
    static $existe = null;
    if ($existe === null) {
        $existe = consulta("SHOW TABLES LIKE 'notificaciones'") !== []
               && consulta("SHOW TABLES LIKE 'notificaciones_leidas'") !== [];
    }
    return $existe;
}

/** Audiencias que puede elegir el administrador: 'todos' y cada rol. */
function avisos_audiencias(): array
{
    return ['todos' => 'Todos'] + ROLES;
}

function aviso_audiencia_texto(string $audiencia): string
{
    return avisos_audiencias()[$audiencia] ?? $audiencia;
}

/** Fecha y hora de publicación para mostrar: "miércoles 10 de marzo, 08:30". */
function aviso_hora_texto(?string $publicada): string
{
    $d = parse_fecha_hora($publicada);
    return $d ? fecha_larga($d) . ', ' . hora_corta($d) : '';
}

/* ============================================================
   Lo que ve cada persona
   ============================================================ */

/**
 * Avisos visibles para una persona (su rol o "todos", no retirados), del último publicado al primero (el orden de publicación, no el de la hora simulada).
 * Cada fila trae 'leido' (0/1). Con $desdeId solo devuelve los de id mayor.
 */
function avisos_visibles(int $uid, string $rol, ?int $desdeId = null, int $limite = 100): array
{
    $sql = 'SELECT n.id, n.titulo, n.cuerpo, n.audiencia, n.nivel, n.publicada_en,
                   (l.usuario_id IS NOT NULL) AS leido
            FROM notificaciones n
            LEFT JOIN notificaciones_leidas l ON l.notificacion_id = n.id AND l.usuario_id = ?
            WHERE n.retirada_en IS NULL AND (n.audiencia = \'todos\' OR n.audiencia = ?)';
    $tipos = 'is';
    $params = [$uid, $rol];
    if ($desdeId !== null) {
        $sql .= ' AND n.id > ?';
        $tipos .= 'i';
        $params[] = $desdeId;
    }
    $sql .= ' ORDER BY n.id DESC LIMIT ' . max(1, $limite);
    return consulta($sql, $tipos, $params);
}

/** Cuántos avisos visibles no ha leído la persona. */
function avisos_no_leidos(int $uid, string $rol): int
{
    $f = consulta_uno(
        'SELECT COUNT(*) AS n
         FROM notificaciones n
         LEFT JOIN notificaciones_leidas l ON l.notificacion_id = n.id AND l.usuario_id = ?
         WHERE n.retirada_en IS NULL AND (n.audiencia = \'todos\' OR n.audiencia = ?) AND l.usuario_id IS NULL',
        'is',
        [$uid, $rol]
    );
    return (int) ($f['n'] ?? 0);
}

/** Id más alto entre los avisos visibles para un rol (0 si no hay). Sirve de punto de partida del sondeo. */
function avisos_id_maximo(string $rol): int
{
    $f = consulta_uno(
        'SELECT COALESCE(MAX(id), 0) AS m FROM notificaciones
         WHERE retirada_en IS NULL AND (audiencia = \'todos\' OR audiencia = ?)',
        's',
        [$rol]
    );
    return (int) ($f['m'] ?? 0);
}

/** Un aviso con los campos que necesita el JavaScript (todo texto plano; el cliente lo escribe con textContent). */
function aviso_para_js(array $a): array
{
    return [
        'id'        => (int) $a['id'],
        'titulo'    => $a['titulo'],
        'cuerpo'    => $a['cuerpo'],
        'nivel'     => $a['nivel'],
        'para'      => $a['audiencia'] === 'todos' ? '' : aviso_audiencia_texto($a['audiencia']),
        'publicada' => aviso_hora_texto($a['publicada_en']),
        'leido'     => !empty($a['leido']),
    ];
}

/**
 * Respuesta del sondeo (api/ahora.php):
 *   no_leidos  conteo para la insignia
 *   visibles   ids que la persona puede ver ahora (para quitar de la lista los retirados)
 *   nuevos     avisos con id > $desde (solo si se manda $desde; la primera consulta no manda nada)
 */
function avisos_para_sondeo(array $usuario, ?int $desde): array
{
    $uid = (int) $usuario['id'];
    $rol = (string) $usuario['rol'];
    $visibles = avisos_visibles($uid, $rol);
    $nuevos = [];
    if ($desde !== null) {
        foreach ($visibles as $a) {
            if ((int) $a['id'] > $desde) {
                $nuevos[] = aviso_para_js($a);
            }
        }
        usort($nuevos, static fn($x, $y) => $x['id'] <=> $y['id']);   // del más viejo al más nuevo
    }
    return [
        'no_leidos' => avisos_no_leidos($uid, $rol),
        'visibles'  => array_map(static fn($a) => (int) $a['id'], $visibles),
        'nuevos'    => $nuevos,
    ];
}

/* ============================================================
   Marcar como leído
   ============================================================ */

/**
 * Marca un aviso como leído. Devuelve false si el aviso no existe, está retirado
 * o no es para el rol de la persona. Marcar dos veces no hace daño.
 */
function aviso_marcar_leido(int $uid, string $rol, int $id): bool
{
    $existe = consulta_uno(
        'SELECT id FROM notificaciones
         WHERE id = ? AND retirada_en IS NULL AND (audiencia = \'todos\' OR audiencia = ?)',
        'is',
        [$id, $rol]
    );
    if ($existe === null) {
        return false;
    }
    return ejecutar(
        'INSERT IGNORE INTO notificaciones_leidas (usuario_id, notificacion_id) VALUES (?, ?)',
        'ii',
        [$uid, $id]
    );
}

/** Marca como leídos todos los avisos visibles de la persona. */
function aviso_marcar_todos(int $uid, string $rol): bool
{
    return ejecutar(
        'INSERT IGNORE INTO notificaciones_leidas (usuario_id, notificacion_id)
         SELECT ?, n.id FROM notificaciones n
         WHERE n.retirada_en IS NULL AND (n.audiencia = \'todos\' OR n.audiencia = ?)',
        'is',
        [$uid, $rol]
    );
}

/* ============================================================
   Panel del administrador
   ============================================================ */

/** Quita caracteres de control (menos el salto de línea) y normaliza saltos y espacios del texto. */
function aviso_limpiar_texto(string $texto, bool $variasLineas): string
{
    $texto = str_replace(["\r\n", "\r"], "\n", $texto);
    $texto = (string) preg_replace('/[^\P{C}\n]+/u', '', $texto);   // fuera controles, queda \n
    if ($variasLineas) {
        $texto = (string) preg_replace("/\n{3,}/", "\n\n", $texto);
        $texto = (string) preg_replace('/[ \t]+\n/', "\n", $texto);
    } else {
        $texto = (string) preg_replace('/\s+/u', ' ', $texto);
    }
    return trim($texto);
}

/**
 * Valida los datos del formulario de publicación.
 * Devuelve [datos limpios, errores por campo].
 */
function aviso_validar(array $post): array
{
    $titulo = aviso_limpiar_texto((string) ($post['titulo'] ?? ''), false);
    $cuerpo = aviso_limpiar_texto((string) ($post['cuerpo'] ?? ''), true);
    $audiencia = (string) ($post['audiencia'] ?? '');
    $nivel = (string) ($post['nivel'] ?? 'info');
    $e = [];

    $lt = mb_strlen($titulo);
    if ($lt < AVISO_TITULO_MIN) {
        $e['titulo'] = 'Escribe un título de al menos ' . AVISO_TITULO_MIN . ' caracteres.';
    } elseif ($lt > AVISO_TITULO_MAX) {
        $e['titulo'] = 'El título puede tener hasta ' . AVISO_TITULO_MAX . ' caracteres (tiene ' . $lt . ').';
    }
    $lc = mb_strlen($cuerpo);
    if ($lc < AVISO_CUERPO_MIN) {
        $e['cuerpo'] = 'Escribe un mensaje de al menos ' . AVISO_CUERPO_MIN . ' caracteres.';
    } elseif ($lc > AVISO_CUERPO_MAX) {
        $e['cuerpo'] = 'El mensaje puede tener hasta ' . AVISO_CUERPO_MAX . ' caracteres (tiene ' . $lc . ').';
    }
    if (!array_key_exists($audiencia, avisos_audiencias())) {
        $e['audiencia'] = 'Elige a quién va dirigido el aviso.';
    }
    if (!in_array($nivel, ['info', 'importante'], true)) {
        $e['nivel'] = 'Elige el nivel del aviso.';
    }
    return [['titulo' => $titulo, 'cuerpo' => $cuerpo, 'audiencia' => $audiencia, 'nivel' => $nivel], $e];
}

/** Inserta el aviso con la hora del evento. Devuelve el id nuevo, o 0 si falló. */
function aviso_publicar(array $d, int $adminId): int
{
    $ok = ejecutar(
        'INSERT INTO notificaciones (titulo, cuerpo, audiencia, nivel, creada_por, publicada_en) VALUES (?, ?, ?, ?, ?, ?)',
        'ssssis',
        [$d['titulo'], $d['cuerpo'], $d['audiencia'], $d['nivel'], $adminId, ahora_evento()->format('Y-m-d H:i:s')]
    );
    return $ok ? (int) db()->insert_id : 0;
}

/** Retira un aviso (no lo borra). True solo si ESTA llamada lo retiró. */
function aviso_retirar(int $id): bool
{
    $stmt = db()->prepare('UPDATE notificaciones SET retirada_en = NOW() WHERE id = ? AND retirada_en IS NULL');
    if (!$stmt) {
        error_log('UDG NEXUS SQL: ' . db()->error);
        return false;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $filas = $stmt->affected_rows;
    $stmt->close();
    return $filas === 1;
}

/** Un aviso con sus conteos (para el panel y la pantalla de confirmación). */
function aviso_admin_uno(int $id): ?array
{
    $f = avisos_admin_lista($id);
    return $f[0] ?? null;
}

/**
 * Todos los avisos (publicados y retirados) con autor, destinatarios y cuántos lo leyeron.
 * Con $id devuelve solo ese aviso.
 */
function avisos_admin_lista(?int $id = null): array
{
    $sql = 'SELECT n.id, n.titulo, n.cuerpo, n.audiencia, n.nivel, n.publicada_en, n.retirada_en, u.nombre AS autor,
                   (SELECT COUNT(*) FROM notificaciones_leidas l WHERE l.notificacion_id = n.id) AS leyeron,
                   (SELECT COUNT(*) FROM usuarios d WHERE n.audiencia = \'todos\' OR d.rol = n.audiencia) AS destinatarios
            FROM notificaciones n LEFT JOIN usuarios u ON u.id = n.creada_por';
    if ($id !== null) {
        return consulta($sql . ' WHERE n.id = ?', 'i', [$id]);
    }
    return consulta($sql . ' ORDER BY n.id DESC');
}
