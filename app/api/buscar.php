<?php
/**
 * API de búsqueda universal (sugerencias del buscador del Home).
 * GET api/buscar.php?q=texto  ->  JSON { q, total, grupos: [...] }
 * Las URLs devueltas son relativas a la carpeta /app.
 */
require_once __DIR__ . '/../../includes/app.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = trim((string) ($_GET['q'] ?? ''));
$salida = ['q' => $q, 'total' => 0, 'grupos' => []];

if (mb_strlen($q) >= 2 && mb_strlen($q) <= 80) {
    $r = buscar_todo($q, 4);

    $grupos = [
        ['clave' => 'proyectos', 'titulo' => 'Proyectos', 'items' => array_map(fn($p) => [
            'titulo'  => $p['nombre'],
            'detalle' => $p['area'] . ' · TRL ' . $p['trl'],
            'url'     => 'proyecto.php?id=' . (int) $p['id'],
        ], $r['proyectos'])],
        ['clave' => 'areas', 'titulo' => 'Áreas tecnológicas', 'items' => array_map(fn($a) => [
            'titulo'  => $a['nombre'],
            'detalle' => $a['descripcion'],
            'url'     => 'catalogo.php?area=' . rawurlencode($a['slug']),
        ], $r['areas'])],
        ['clave' => 'empresas', 'titulo' => 'Empresas e inversionistas', 'items' => array_map(fn($e) => [
            'titulo'  => $e['nombre'],
            'detalle' => etiqueta_tipo_empresa($e['tipo']) . ($e['sector'] ? ' · ' . $e['sector'] : ''),
            'url'     => 'empresa.php?id=' . (int) $e['id'],
        ], $r['empresas'])],
        ['clave' => 'investigadores', 'titulo' => 'Investigadores', 'items' => array_map(fn($i) => [
            'titulo'  => $i['nombre'],
            'detalle' => $i['centro'] . ($i['especialidad'] ? ' · ' . $i['especialidad'] : ''),
            'url'     => $i['proyecto_id'] ? 'proyecto.php?id=' . (int) $i['proyecto_id'] : 'buscar.php?q=' . rawurlencode($i['nombre']),
        ], $r['investigadores'])],
    ];

    foreach ($grupos as $g) {
        if ($g['items']) {
            $salida['grupos'][] = $g;
            $salida['total'] += count($g['items']);
        }
    }
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
