<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db_tags.php';
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

require_role('admin');

function ensure_imagen_column_large($pdo, $data){
    // Si la imagen (Base64) excede 600 chars y la columna es VARCHAR(600), ampliar a MEDIUMTEXT.
    if(strlen($data) <= 600) return; // no hace falta
    static $checked = false; if($checked) return; $checked = true;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM productos LIKE 'imagen'")->fetch(PDO::FETCH_ASSOC);
        if($col && stripos($col['Type']??'','varchar(600)')!==false){
            $pdo->exec('ALTER TABLE productos MODIFY imagen MEDIUMTEXT NULL');
        }
    } catch(Exception $e){ /* Silencioso: si falla se verá luego al insertar */ }
}

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
    try {
        $titulo = trim($input['titulo'] ?? '');
        $precio = (float)($input['precio'] ?? 0);
        $imagen = trim($input['imagen'] ?? '');
        $tags = $input['tags'] ?? [];
        if ($titulo==='') json_response(['error'=>'Título requerido'],400);
        if ($precio < 0) json_response(['error'=>'Precio inválido'],400);
        if ($imagen==='') json_response(['error'=>'Imagen requerida'],400);
        ensure_imagen_column_large($pdo, $imagen);
        if (strlen($imagen) > 2000000) json_response(['error'=>'Imagen demasiado grande (>2MB codificado)'],400);
        $stmt = $pdo->prepare('INSERT INTO productos (titulo,precio,imagen) VALUES (?,?,?)');
        $stmt->execute([$titulo,$precio,$imagen]);
        $id = (int)$pdo->lastInsertId();
        if (!is_array($tags)) { $tags = []; }
        sync_product_tags($id, $tags);
        json_response(['ok'=>true,'id'=>$id]);
    } catch(Exception $e){ json_response(['error'=>'Error creando','detalle'=>$e->getMessage()],500); }
}

if ($method === 'PUT') {
    try {
        $id = (int)($input['id'] ?? 0);
        $titulo = trim($input['titulo'] ?? '');
        $precio = (float)($input['precio'] ?? 0);
        $imagen = trim($input['imagen'] ?? '');
        $tags = $input['tags'] ?? [];
        if ($id<=0) json_response(['error'=>'ID inválido'],400);
        if ($titulo==='') json_response(['error'=>'Título requerido'],400);
        if ($precio < 0) json_response(['error'=>'Precio inválido'],400);
        if ($imagen==='') json_response(['error'=>'Imagen requerida'],400);
        ensure_imagen_column_large($pdo, $imagen);
        if (strlen($imagen) > 2000000) json_response(['error'=>'Imagen demasiado grande (>2MB codificado)'],400);
        $stmt = $pdo->prepare('UPDATE productos SET titulo=?, precio=?, imagen=? WHERE id=?');
        $stmt->execute([$titulo,$precio,$imagen,$id]);
        if (!is_array($tags)) { $tags = []; }
        sync_product_tags($id, $tags);
        json_response(['ok'=>true]);
    } catch(Exception $e){ json_response(['error'=>'Error actualizando','detalle'=>$e->getMessage()],500); }
}

if ($method === 'DELETE') {
    try {
        $id = (int)($_GET['id'] ?? 0);
        if ($id>0) { $pdo->prepare('DELETE FROM productos WHERE id=?')->execute([$id]); }
        json_response(['ok'=>true]);
    } catch(Exception $e){ json_response(['error'=>'Error eliminando','detalle'=>$e->getMessage()],500); }
}

http_response_code(405);
json_response(['error'=>'Método no permitido']);
