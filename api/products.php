<?php
require __DIR__ . '/config.php';
$pdo = db();

// Obtener productos con tags normalizados (GROUP_CONCAT) y luego convertir a array
$sql = "SELECT p.id, p.titulo, p.precio, p.imagen,
               GROUP_CONCAT(t.nombre ORDER BY t.nombre SEPARATOR ',') AS tags
        FROM productos p
        LEFT JOIN producto_tags pt ON pt.producto_id = p.id
        LEFT JOIN tags t ON t.id = pt.tag_id
        GROUP BY p.id
        ORDER BY p.id";

$rows = $pdo->query($sql)->fetchAll();
$mapped = array_map(function($r){
    $tags = $r['tags'] !== null && $r['tags'] !== '' ? explode(',', $r['tags']) : [];
    return [
        'id'    => (int)$r['id'],
        'title' => $r['titulo'],
        'price' => (float)$r['precio'],
        'img'   => $r['imagen'],
        'tags'  => $tags
    ];
}, $rows);

json_response(['products'=>$mapped,'count'=>count($mapped)]);
