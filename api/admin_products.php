<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db_tags.php';
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

require_role('admin');

if ($method === 'GET') {
    $sql = "SELECT p.id,p.titulo,p.precio,p.imagen, GROUP_CONCAT(t.nombre ORDER BY t.nombre SEPARATOR ',') as tags
            FROM productos p
            LEFT JOIN producto_tags pt ON pt.producto_id=p.id
            LEFT JOIN tags t ON t.id=pt.tag_id
            GROUP BY p.id ORDER BY p.id";
    $rows = $pdo->query($sql)->fetchAll();
    $items = array_map(fn($r)=>[
        'id'=>(int)$r['id'],
        'titulo'=>$r['titulo'],
        'precio'=>(float)$r['precio'],
        'imagen'=>$r['imagen'],
        'tags'=>$r['tags']?explode(',',$r['tags']):[]
    ], $rows);
    json_response(['items'=>$items]);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    // Crear producto (id autoincrement)
    $titulo = trim($input['titulo'] ?? '');
    $precio = (float)($input['precio'] ?? 0);
    $imagen = trim($input['imagen'] ?? '');
    $tags = $input['tags'] ?? [];
    if ($titulo==='') json_response(['error'=>'Datos inválidos'],400);
    $stmt = $pdo->prepare('INSERT INTO productos (titulo,precio,imagen) VALUES (?,?,?)');
    $stmt->execute([$titulo,$precio,$imagen]);
    $id = (int)$pdo->lastInsertId();
    if (!is_array($tags)) { $tags = []; }
    sync_product_tags($id, $tags);
    json_response(['ok'=>true,'id'=>$id]);
}

if ($method === 'PUT') {
    $id = (int)($input['id'] ?? 0);
    $titulo = trim($input['titulo'] ?? '');
    $precio = (float)($input['precio'] ?? 0);
    $imagen = trim($input['imagen'] ?? '');
    $tags = $input['tags'] ?? [];
    if ($id<=0 || $titulo==='') json_response(['error'=>'Datos inválidos'],400);
    $stmt = $pdo->prepare('UPDATE productos SET titulo=?, precio=?, imagen=? WHERE id=?');
    $stmt->execute([$titulo,$precio,$imagen,$id]);
    if (!is_array($tags)) { $tags = []; }
    sync_product_tags($id, $tags);
    json_response(['ok'=>true]);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id>0) { $pdo->prepare('DELETE FROM productos WHERE id=?')->execute([$id]); }
    json_response(['ok'=>true]);
}

http_response_code(405);
json_response(['error'=>'Método no permitido']);
