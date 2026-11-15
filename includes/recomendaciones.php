<?php
/**
 * UDG NEXUS — Recomendaciones por perfil (Fase 5B).
 *
 * Reglas simples por rol e intereses, SIN inteligencia artificial. Cada recomendación lleva su motivo
 * ("Porque te interesa Salud"). Se calcula todo en cada petición con consultas preparadas; no se guarda nada.
 *
 * Datos que usa:
 *   usuarios.rol, usuarios.intereses (slugs de areas_tecnologicas separados por coma),
 *   usuarios.proyecto_id (solo expositores; migración 008), actividad_area (migración 008),
 *   actividades (tipo, zona), proyectos (area_id, trl, busca_inversion), la agenda de la persona
 *   y el reloj del evento (lo que ya terminó no se recomienda).
 *
 * Puntos: cada regla suma puntos; gana el que más tiene (empata: actividades por hora de inicio,
 * proyectos por TRL). Lo que ya está en la agenda resta RECO_PENALIZA_EN_AGENDA para que suban las novedades,
 * pero se sigue mostrando con la marca "En tu agenda".
 *
 * Funciones principales:
 *   reco_para($usuario, $ahora)       -> ['aplica', 'ejes', 'proyecto', 'items'] (items ya ordenados)
 *   reco_filtrar($items, $tipo, $n)   -> los primeros $n de un tipo ('actividad' | 'proyecto' | null = todos)
 *   reco_html_item(...)               -> tarjeta lista para imprimir
 */

/** Roles a los que se les recomienda (staff y admin no aplican). */
const RECO_ROLES = ['estudiante', 'inversionista', 'corporativo', 'expositor'];
/** TRL desde el que un proyecto se considera de alta madurez (el mismo umbral de la vitrina). */
const RECO_TRL_ALTO = 7;
/** Puntos que resta estar ya en la agenda. */
const RECO_PENALIZA_EN_AGENDA = 15;

/** True si ya se importó db/migraciones/008_recomendaciones.sql. */
function reco_tablas_existen(): bool
{
    static $ok = null;
    if ($ok === null) {
        $ok = consulta("SHOW TABLES LIKE 'actividad_area'") !== [];
    }
    return $ok;
}

/** True si el rol de la persona recibe recomendaciones. */
function reco_aplica(?array $u): bool
{
    return $u !== null && in_array($u['rol'], RECO_ROLES, true);
}

/** True si usuarios tiene la columna proyecto_id (migración 008). */
function reco_columna_proyecto_existe(): bool
{
    static $ok = null;
    if ($ok === null) {
        $ok = consulta("SHOW COLUMNS FROM usuarios LIKE 'proyecto_id'") !== [];
    }
    return $ok;
}

/** Ejes de interés de la persona: [area_id => nombre]. Vacío si no eligió ninguno. */
function reco_ejes_usuario(array $u): array
{
    $slugs = array_filter(array_map('trim', explode(',', (string) ($u['intereses'] ?? ''))));
    if (!$slugs) {
        return [];
    }
    $res = [];
    foreach (areas_todas() as $a) {
        if (in_array($a['slug'], $slugs, true)) {
            $res[(int) $a['id']] = $a['nombre'];
        }
    }
    return $res;
}

/** Proyecto que expone una cuenta de expositor, con su eje y su zona (null si no está ligada). */
function reco_proyecto_usuario(int $uid): ?array
{
    if (!reco_columna_proyecto_existe()) {
        return null;
    }
    return consulta_uno(
        'SELECT p.id, p.nombre, p.area_id, a.nombre AS area, p.zona_id, z.nombre AS zona
         FROM usuarios u
         JOIN proyectos p ON p.id = u.proyecto_id
         JOIN areas_tecnologicas a ON a.id = p.area_id
         LEFT JOIN zonas z ON z.id = p.zona_id
         WHERE u.id = ?',
        'i',
        [$uid]
    );
}

/** "Salud", "Salud y Software", "Salud, Software y Energía". */
function reco_lista_nombres(array $nombres): string
{
    $n = array_values($nombres);
    if (count($n) <= 1) {
        return (string) ($n[0] ?? '');
    }
    return implode(', ', array_slice($n, 0, -1)) . ' y ' . end($n);
}

/** Motivo base: "Porque te interesa Salud" / "Porque te interesan Salud y Software". */
function reco_porque(array $nombres): string
{
    return (count($nombres) === 1 ? 'Porque te interesa ' : 'Porque te interesan ') . reco_lista_nombres($nombres);
}

/** Nombres de los ejes de la persona que coinciden con los ejes de un elemento. */
function reco_coincidencias(array $areasElemento, array $ejes): array
{
    $res = [];
    foreach ($ejes as $id => $nombre) {
        if (in_array((int) $id, $areasElemento, true)) {
            $res[] = $nombre;
        }
    }
    return $res;
}

/** Actividades que todavía no terminan, con la lista de ejes ('areas_ids'; vacía = general). */
function reco_actividades(DateTimeInterface $ahora): array
{
    $filas = consulta(
        'SELECT a.id, a.titulo, a.tipo, a.ponente, a.inicio, a.fin, a.cupo, z.id AS zona_id, z.nombre AS zona,
                GROUP_CONCAT(aa.area_id ORDER BY aa.area_id) AS areas
         FROM actividades a
         JOIN zonas z ON z.id = a.zona_id
         LEFT JOIN actividad_area aa ON aa.actividad_id = a.id
         WHERE a.fin > ?
         GROUP BY a.id, z.id, z.nombre
         ORDER BY a.inicio, a.id',
        's',
        [$ahora->format('Y-m-d H:i:s')]
    );
    foreach ($filas as &$f) {
        $f['areas_ids'] = ($f['areas'] === null || $f['areas'] === '')
            ? []
            : array_map('intval', explode(',', (string) $f['areas']));
    }
    unset($f);
    return $filas;
}

/** Todos los proyectos, con lo que pide tarjeta_proyecto(). */
function reco_proyectos(): array
{
    return consulta(
        'SELECT p.id, p.nombre, p.resumen, p.trl, p.busca_inversion, p.area_id, a.nombre AS area, z.nombre AS zona, p.stand
         FROM proyectos p
         JOIN areas_tecnologicas a ON a.id = p.area_id
         LEFT JOIN zonas z ON z.id = p.zona_id
         ORDER BY p.id'
    );
}

/**
 * Calcula las recomendaciones de una persona.
 * Devuelve ['aplica' => bool, 'ejes' => [id => nombre], 'proyecto' => ?array, 'items' => [...]].
 * Cada item: ['tipo' => 'actividad'|'proyecto', 'fila' => [...], 'puntos' => int, 'motivo' => string, 'en_agenda' => bool].
 */
function reco_para(array $u, DateTimeInterface $ahora): array
{
    $res = ['aplica' => false, 'ejes' => [], 'proyecto' => null, 'items' => []];
    if (!reco_aplica($u)) {
        return $res;
    }

    $uid  = (int) $u['id'];
    $rol  = (string) $u['rol'];
    $ejes = reco_ejes_usuario($u);
    $proy = $rol === 'expositor' ? reco_proyecto_usuario($uid) : null;
    $res['aplica'] = true;
    $res['ejes'] = $ejes;
    $res['proyecto'] = $proy;

    $enAgenda = [];
    if (agenda_tabla_existe()) {
        foreach (agenda_usuario($uid) as $g) {
            $enAgenda[(int) $g['id']] = true;
        }
    }

    $items = [];
    $poner = static function (string $tipo, array $fila, int $puntos, string $motivo) use (&$items, $enAgenda): void {
        $ya = $tipo === 'actividad' && isset($enAgenda[(int) $fila['id']]);
        $items[] = [
            'tipo'      => $tipo,
            'fila'      => $fila,
            'puntos'    => $puntos - ($ya ? RECO_PENALIZA_EN_AGENDA : 0),
            'motivo'    => $motivo,
            'en_agenda' => $ya,
        ];
    };

    /* ---------- Actividades ---------- */
    foreach (reco_actividades($ahora) as $a) {
        $co      = reco_coincidencias($a['areas_ids'], $ejes);
        $extra   = 5 * max(0, count($co) - 1);      // más ejes en común, más puntos
        $general = $a['areas_ids'] === [];
        $tipo    = $a['tipo'];

        switch ($rol) {
            case 'estudiante':
                $base = in_array($tipo, ['Taller', 'Conferencia'], true);
                if ($co && $base) {
                    $poner('actividad', $a, 40 + $extra, reco_porque($co));
                } elseif ($co && $tipo === 'Presentación') {
                    $poner('actividad', $a, 22 + $extra, reco_porque($co));
                } elseif ($general && $base) {
                    $poner('actividad', $a, 10, 'Útil para estudiantes');
                }
                break;

            case 'inversionista':
                if ($tipo === 'Rueda de negocios') {
                    if ($co) {
                        $poner('actividad', $a, 32 + $extra, reco_porque($co));
                    } else {
                        $poner('actividad', $a, 8, 'Rueda de negocios para inversionistas');
                    }
                }
                break;

            case 'corporativo':
                if ($tipo === 'Reverse Pitch') {
                    if ($co) {
                        $poner('actividad', $a, 38 + $extra, reco_porque($co));
                    } else {
                        $poner('actividad', $a, 10, 'Reverse Pitch: retos para empresas');
                    }
                }
                break;

            case 'expositor':
                $enMiZona = $proy && $proy['zona_id'] !== null && (int) $proy['zona_id'] === (int) $a['zona_id'];
                $miEje = ($proy && $tipo === 'Rueda de negocios')
                    ? reco_coincidencias($a['areas_ids'], [(int) $proy['area_id'] => $proy['area']])
                    : [];
                if ($enMiZona) {
                    $poner('actividad', $a, 40 + ($co ? 5 : 0), 'Es en tu zona: ' . $a['zona']);
                } elseif ($miEje) {
                    $poner('actividad', $a, 34, 'Rueda de negocios de tu eje: ' . $proy['area']);
                } elseif ($co && $tipo === 'Rueda de negocios') {
                    $poner('actividad', $a, 30 + $extra, reco_porque($co));
                } elseif ($co) {
                    $poner('actividad', $a, 15 + $extra, reco_porque($co));
                }
                break;
        }
    }

    /* ---------- Proyectos (solo inversionista y corporativo) ---------- */
    if ($rol === 'inversionista' || $rol === 'corporativo') {
        foreach (reco_proyectos() as $p) {
            $co  = reco_coincidencias([(int) $p['area_id']], $ejes);
            $trl = (int) $p['trl'];

            if ($rol === 'inversionista' && !empty($p['busca_inversion'])) {
                if ($co) {
                    $poner('proyecto', $p, 40 + $trl, reco_porque($co) . ' · Busca inversión');
                } elseif (!$ejes) {
                    $poner('proyecto', $p, 12 + $trl, 'Destacado del evento · Busca inversión');
                } else {
                    $poner('proyecto', $p, 10 + $trl, 'Busca inversión · Otro eje: ' . $p['area']);
                }
            }
            if ($rol === 'corporativo' && $trl >= RECO_TRL_ALTO) {
                if ($co) {
                    $poner('proyecto', $p, 40 + $trl, reco_porque($co) . ' · TRL ' . $trl);
                } elseif (!$ejes) {
                    $poner('proyecto', $p, 12 + $trl, 'Alta madurez tecnológica · TRL ' . $trl);
                }
            }
        }
    }

    usort($items, static function (array $a, array $b): int {
        if ($a['puntos'] !== $b['puntos']) {
            return $b['puntos'] <=> $a['puntos'];
        }
        if ($a['tipo'] !== $b['tipo']) {
            return $a['tipo'] === 'actividad' ? -1 : 1;
        }
        if ($a['tipo'] === 'actividad') {
            return [$a['fila']['inicio'], (int) $a['fila']['id']] <=> [$b['fila']['inicio'], (int) $b['fila']['id']];
        }
        return [(int) $b['fila']['trl'], (int) $a['fila']['id']] <=> [(int) $a['fila']['trl'], (int) $b['fila']['id']];
    });

    $res['items'] = $items;
    return $res;
}

/** Los primeros $n elementos (de un tipo, o de todos si $tipo es null). */
function reco_filtrar(array $items, ?string $tipo, int $n): array
{
    if ($tipo !== null) {
        $items = array_values(array_filter($items, static fn($i) => $i['tipo'] === $tipo));
    }
    return array_slice($items, 0, max(0, $n));
}

/** Línea del motivo que va arriba de cada tarjeta. */
function reco_motivo_html(string $motivo): string
{
    return '<p class="reco-motivo">' . icono('estrella') . '<span>' . h($motivo) . '</span></p>';
}

/**
 * Tarjeta de una recomendación. Las actividades usan la misma tarjeta (y el mismo botón de agenda) que la
 * pantalla de Agenda; los proyectos, la misma que el catálogo.
 */
function reco_html_item(array $it, DateTimeImmutable $ahora, array $usuario, bool $tablaOk, array $miAgenda, array $porId): string
{
    if ($it['tipo'] === 'actividad') {
        $a = $it['fila'];
        return tarjeta_agenda($a, $porId[(int) $a['id']] ?? null, $ahora, $usuario, $tablaOk, $miAgenda, $it['motivo']);
    }
    return tarjeta_proyecto($it['fila'], $it['motivo']);
}

/** [agenda, agenda indexada por id] de una persona; vacíos si falta la migración 004. */
function reco_agenda_de(int $uid): array
{
    $mia = agenda_tabla_existe() ? agenda_usuario($uid) : [];
    $porId = [];
    foreach ($mia as $g) {
        $porId[(int) $g['id']] = $g;
    }
    return [$mia, $porId];
}

/** Datos de la agenda para el JS (el mismo bloque que usa agenda.php). */
function reco_json_agenda(array $miAgenda): string
{
    return '<script type="application/json" id="agenda-datos">'
        . json_encode(agenda_para_js($miAgenda), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        . '</script>';
}

/** Reglas de cada rol, en palabras, para la sección "¿Cómo se eligen?". */
function reco_reglas(string $rol): array
{
    switch ($rol) {
        case 'estudiante':
            return [
                'Talleres y conferencias de tus ejes de interés.',
                'Presentaciones de tus ejes, para ver prototipos funcionando.',
                'Talleres y conferencias generales (propiedad intelectual, financiamiento) cuando no hay más de tu eje.',
            ];
        case 'inversionista':
            return [
                'Proyectos que buscan inversión y son de tus ejes de interés.',
                'Ruedas de negocios de tus ejes.',
                'Si tus ejes no alcanzan, otros proyectos que buscan inversión, indicando su eje.',
            ];
        case 'corporativo':
            return [
                'Proyectos de tus ejes con TRL 7 o más (probados en entorno real).',
                'Reverse Pitch de tus ejes, donde tu empresa puede plantear retos.',
                'Otros Reverse Pitch cuando no hay uno de tu eje.',
            ];
        case 'expositor':
            return [
                'Actividades en la zona donde está tu proyecto.',
                'Ruedas de negocios del eje de tu proyecto, para tus citas.',
                'Actividades de tus ejes de interés.',
            ];
    }
    return [];
}
