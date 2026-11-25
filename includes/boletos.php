<?php
/**
 * UDG NEXUS — Boletos, gafete QR y control de acceso (Fase 3).
 * Se carga desde includes/app.php (después de db(), consulta(), ejecutar() y auth.php).
 *
 * Reglas:
 *  - El QR contiene SOLO el token del boleto (16 bytes de random_bytes en hexadecimal,
 *    32 caracteres). Ningún dato personal viaja en el código.
 *  - Una persona tiene un solo boleto vigente o usado a la vez.
 *  - La entrada se valida una sola vez: el UPDATE ... WHERE estatus = 'vigente' es atómico,
 *    así que dos lecturas simultáneas del mismo QR no pueden entrar las dos.
 *  - El pago es simulado: no se pide ni se guarda ningún dato de tarjeta.
 */

/* ============================================================
   Utilidades
   ============================================================ */

/** True si ya se importó db/migraciones/005_boletos.sql. */
function boletos_tablas_existen(): bool
{
    static $existe = null;
    if ($existe === null) {
        $existe = consulta("SHOW TABLES LIKE 'tipos_boleto'") !== []
               && consulta("SHOW TABLES LIKE 'boletos'") !== []
               && consulta("SHOW TABLES LIKE 'accesos'") !== [];
    }
    return $existe;
}

/** Token nuevo e impredecible para un boleto. */
function boleto_generar_token(): string
{
    return bin2hex(random_bytes(16));
}

function token_boleto_valido(string $token): bool
{
    return (bool) preg_match('/^[a-f0-9]{32}$/', $token);
}

/** Folio legible del boleto (no sirve para entrar; solo para identificarlo). */
function boleto_folio(int $id): string
{
    return 'NX-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

/** Token en grupos de 4 caracteres, para teclearlo a mano si falla la cámara. */
function boleto_token_agrupado(string $token): string
{
    return implode(' ', str_split($token, 4));
}

function precio_texto($precio): string
{
    $p = (float) $precio;
    if ($p <= 0) {
        return 'Gratis';
    }
    return '$' . number_format($p, fmod($p, 1.0) === 0.0 ? 0 : 2) . ' MXN';
}

/** "11 al 12 de marzo de 2027" a partir de config_evento (fecha ficticia). */
function evento_rango_texto(): string
{
    $ini = config_evento('evento_inicio');
    if (!$ini) {
        return '';
    }
    $a = new DateTimeImmutable($ini);
    $b = $a->modify('+' . (max(1, (int) config_evento('evento_dias', '2')) - 1) . ' days');
    $mesA = MESES_ES[(int) $a->format('n') - 1];
    $mesB = MESES_ES[(int) $b->format('n') - 1];
    if ($a->format('Y-n') === $b->format('Y-n')) {
        return $a->format('j') . ' al ' . $b->format('j') . ' de ' . $mesA . ' de ' . $a->format('Y');
    }
    return $a->format('j') . ' de ' . $mesA . ' al ' . $b->format('j') . ' de ' . $mesB . ' de ' . $b->format('Y');
}

/** "sábado 14 de noviembre, 16:22" para una fecha guardada en la base. */
function fecha_hora_texto(?string $valor): string
{
    $d = parse_fecha_hora($valor);
    return $d ? fecha_larga($d) . ', ' . hora_corta($d) : '';
}

/* ============================================================
   Catálogo de boletos
   ============================================================ */

/** Tipos de boleto activos con lo emitido y lo que queda de cupo. */
function tipos_boleto_todos(): array
{
    $filas = consulta(
        "SELECT t.*, (SELECT COUNT(*) FROM boletos b WHERE b.tipo_boleto_id = t.id AND b.estatus <> 'cancelado') AS emitidos
         FROM tipos_boleto t WHERE t.activo = 1 ORDER BY t.orden, t.id"
    );
    foreach ($filas as &$f) {
        $f['id'] = (int) $f['id'];
        $f['emitidos'] = (int) $f['emitidos'];
        $f['disponibles'] = max(0, (int) $f['cupo'] - $f['emitidos']);
        $f['beneficios'] = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $f['incluye']))));
        $f['roles'] = array_values(array_filter(explode(',', (string) $f['rol_sugerido'])));
    }
    unset($f);
    return $filas;
}

function tipo_boleto_por_id(int $id): ?array
{
    foreach (tipos_boleto_todos() as $t) {
        if ($t['id'] === $id) {
            return $t;
        }
    }
    return null;
}

/** Tipo recomendado para un rol (el primero que lo lista), o null. */
function tipo_boleto_sugerido(array $tipos, string $rol): ?array
{
    foreach ($tipos as $t) {
        if (in_array($rol, $t['roles'], true)) {
            return $t;
        }
    }
    return null;
}

/**
 * Boleto de la persona: el vigente o usado más reciente; si solo tiene cancelados,
 * el último cancelado. Null si nunca ha tenido uno.
 */
function boleto_de_usuario(int $uid): ?array
{
    return consulta_uno(
        "SELECT b.*, t.nombre AS tipo, t.clave AS tipo_clave, t.descripcion AS tipo_descripcion
         FROM boletos b JOIN tipos_boleto t ON t.id = b.tipo_boleto_id
         WHERE b.usuario_id = ?
         ORDER BY (b.estatus = 'cancelado') ASC, b.id DESC LIMIT 1",
        'i',
        [$uid]
    );
}

/* ============================================================
   Emisión (registro y compra simulada)
   ============================================================ */

/**
 * Emite un boleto vigente. Devuelve:
 *   ['ok' => true,  'boleto_id' => N, 'token' => '...', 'precio' => 0.0, 'ref' => ...|null]
 *   ['ok' => false, 'codigo' => 'tipo'|'ya_tiene'|'agotado'|'bd', 'error' => 'texto para la persona']
 * Todo ocurre en una transacción que bloquea a la persona y al tipo: dos envíos
 * simultáneos no emiten dos boletos ni pasan del cupo.
 */
function boleto_emitir(int $uid, int $tipoId): array
{
    $db = db();
    $db->begin_transaction();

    $u = consulta_uno('SELECT id FROM usuarios WHERE id = ? FOR UPDATE', 'i', [$uid]);
    $t = consulta_uno('SELECT * FROM tipos_boleto WHERE id = ? AND activo = 1 FOR UPDATE', 'i', [$tipoId]);
    if ($u === null || $t === null) {
        $db->rollback();
        return ['ok' => false, 'codigo' => 'tipo', 'error' => 'Ese boleto no está disponible.'];
    }

    $ya = consulta_uno("SELECT id FROM boletos WHERE usuario_id = ? AND estatus IN ('vigente','usado') LIMIT 1", 'i', [$uid]);
    if ($ya !== null) {
        $db->rollback();
        return ['ok' => false, 'codigo' => 'ya_tiene', 'error' => 'Ya tienes un boleto.', 'boleto_id' => (int) $ya['id']];
    }

    $n = consulta_uno("SELECT COUNT(*) AS n FROM boletos WHERE tipo_boleto_id = ? AND estatus <> 'cancelado'", 'i', [$tipoId]);
    if ((int) ($n['n'] ?? 0) >= (int) $t['cupo']) {
        $db->rollback();
        return ['ok' => false, 'codigo' => 'agotado', 'error' => 'Ese boleto se agotó. Elige otro tipo.'];
    }

    $precio = (float) $t['precio'];
    $ref = $precio > 0 ? 'SIM-' . strtoupper(bin2hex(random_bytes(4))) : null;
    $ahora = date('Y-m-d H:i:s');
    $ok = false;
    $token = '';
    for ($i = 0; $i < 3 && !$ok; $i++) {          // el token es único; si chocara, se genera otro
        $token = boleto_generar_token();
        $ok = ejecutar(
            "INSERT INTO boletos (usuario_id, tipo_boleto_id, token_qr, estatus, precio_pagado, pago_simulado_ref, comprado_en)
             VALUES (?, ?, ?, 'vigente', ?, ?, ?)",
            'iisdss',
            [$uid, $tipoId, $token, $precio, $ref, $ahora]
        );
    }
    if (!$ok) {
        $db->rollback();
        return ['ok' => false, 'codigo' => 'bd', 'error' => 'No pudimos generar tu boleto. Inténtalo de nuevo en un momento.'];
    }
    $id = (int) $db->insert_id;
    $db->commit();
    return ['ok' => true, 'boleto_id' => $id, 'token' => $token, 'precio' => $precio, 'ref' => $ref];
}

/* ============================================================
   Control de acceso (escáner del staff)
   ============================================================ */

function acceso_registrar(?int $boletoId, ?int $staffId, string $resultado, ?string $leido = null): void
{
    ejecutar(
        'INSERT INTO accesos (boleto_id, staff_id, resultado, token_leido, escaneado_en) VALUES (?, ?, ?, ?, ?)',
        'iisss',
        [$boletoId, $staffId, $resultado, $leido !== null ? mb_substr($leido, 0, 64) : null, date('Y-m-d H:i:s')]
    );
}

/**
 * Valida un QR leído en la entrada y, si procede, marca el boleto como usado.
 * Devuelve siempre ['resultado' => entrada|repetido|cancelado|invalido, 'mensaje' => ...]
 * y, si el boleto existe, nombre, rol, institución, tipo y folio.
 */
function boleto_validar_entrada(string $leido, ?int $staffId): array
{
    $token = strtolower(trim($leido));

    if (!token_boleto_valido($token)) {
        acceso_registrar(null, $staffId, 'invalido', $leido);
        return ['resultado' => 'invalido', 'mensaje' => 'Código no reconocido: no es un boleto de UDG NEXUS.'];
    }

    $sql = "SELECT b.id, b.estatus, b.usado_en, t.nombre AS tipo, u.nombre, u.rol, u.institucion
            FROM boletos b
            JOIN tipos_boleto t ON t.id = b.tipo_boleto_id
            JOIN usuarios u ON u.id = b.usuario_id
            WHERE b.token_qr = ?";
    $b = consulta_uno($sql, 's', [$token]);
    if ($b === null) {
        acceso_registrar(null, $staffId, 'invalido', $token);
        return ['resultado' => 'invalido', 'mensaje' => 'Este código no corresponde a ningún boleto.'];
    }

    $datos = [
        'folio'       => boleto_folio((int) $b['id']),
        'nombre'      => $b['nombre'],
        'rol'         => ROLES[$b['rol']] ?? $b['rol'],
        'institucion' => $b['institucion'],
        'tipo'        => $b['tipo'],
    ];

    if ($b['estatus'] === 'vigente') {
        // Atómico: solo el primero que llegue cambia la fila; el segundo ve 0 filas afectadas.
        $stmt = db()->prepare("UPDATE boletos SET estatus = 'usado', usado_en = ? WHERE id = ? AND estatus = 'vigente'");
        $filas = 0;
        if ($stmt) {
            $ahora = date('Y-m-d H:i:s');
            $id = (int) $b['id'];
            $stmt->bind_param('si', $ahora, $id);
            $stmt->execute();
            $filas = $stmt->affected_rows;
            $stmt->close();
        }
        if ($filas === 1) {
            acceso_registrar((int) $b['id'], $staffId, 'entrada');
            return ['resultado' => 'entrada', 'mensaje' => 'Acceso permitido. El boleto quedó marcado como usado.'] + $datos;
        }
        $b = consulta_uno($sql, 's', [$token]) ?? $b;   // otra lectura se adelantó: se vuelve a leer
    }

    if ($b['estatus'] === 'cancelado') {
        acceso_registrar((int) $b['id'], $staffId, 'cancelado');
        return ['resultado' => 'cancelado', 'mensaje' => 'Boleto cancelado. No puede ingresar.'] + $datos;
    }

    acceso_registrar((int) $b['id'], $staffId, 'repetido');
    $cuando = fecha_hora_texto($b['usado_en']);
    return [
        'resultado' => 'repetido',
        'mensaje'   => 'Este boleto ya se usó' . ($cuando !== '' ? ' (' . $cuando . ')' : '') . '. No puede ingresar otra vez.',
        'usado_en'  => $cuando,
    ] + $datos;
}

/* ============================================================
   Resúmenes para el panel de administración
   ============================================================ */

/** Totales por tipo: emitidos, vigentes, usados, cancelados y cupo. */
function boletos_resumen_por_tipo(): array
{
    return consulta(
        "SELECT t.id, t.nombre, t.precio, t.cupo,
                SUM(b.estatus = 'vigente')   AS vigentes,
                SUM(b.estatus = 'usado')     AS usados,
                SUM(b.estatus = 'cancelado') AS cancelados
         FROM tipos_boleto t LEFT JOIN boletos b ON b.tipo_boleto_id = t.id
         WHERE t.activo = 1 GROUP BY t.id, t.nombre, t.precio, t.cupo, t.orden ORDER BY t.orden, t.id"
    );
}

/** Últimas lecturas del escáner. */
function accesos_recientes(int $limite = 12): array
{
    return consulta(
        "SELECT a.id, a.resultado, a.escaneado_en, a.token_leido, u.nombre AS persona, s.nombre AS staff, b.id AS boleto_id
         FROM accesos a
         LEFT JOIN boletos b  ON b.id = a.boleto_id
         LEFT JOIN usuarios u ON u.id = b.usuario_id
         LEFT JOIN usuarios s ON s.id = a.staff_id
         ORDER BY a.id DESC LIMIT " . (int) $limite
    );
}

/** Texto visible del estatus de un boleto. */
function estatus_boleto_etiqueta(string $estatus): string
{
    return ['vigente' => 'Vigente', 'usado' => 'Usado', 'cancelado' => 'Cancelado'][$estatus] ?? $estatus;
}

/** Chip HTML con el estatus del boleto (el texto siempre acompaña al color). */
function chip_estatus_boleto(string $estatus): string
{
    return '<span class="chip estatus-' . h($estatus) . '">' . h(estatus_boleto_etiqueta($estatus)) . '</span>';
}
