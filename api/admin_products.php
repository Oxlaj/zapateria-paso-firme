<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db_tags.php';
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

require_role('admin');

/**
 * Garantiza que la columna productos.imagen tenga capacidad suficiente (MEDIUMTEXT)
 * para almacenar Base64 (normalmente >600 chars). Intenta alterar solo una vez.
 */
function ensure_imagen_column_large($pdo, $data){
    static $done = false; if($done) return; // Ejecutar una vez por request
    if(strlen($data) <= 600 && strpos($data,'data:image')===false) return; // Parece ruta corta, no forzamos
    try {
        $col = $pdo->query("SHOW COLUMNS FROM productos LIKE 'imagen'")->fetch(PDO::FETCH_ASSOC);
        if($col && stripos($col['Type']??'', 'varchar(')!==false){
            $pdo->exec('ALTER TABLE productos MODIFY imagen MEDIUMTEXT NULL');
        }
        $done = true;
    } catch(Exception $e){ /* Silencioso: reintento se hará si ocurre 22001 */ }
}

/**
 * Garantiza que la columna id de productos sea AUTO_INCREMENT.
 */
function ensure_product_id_autoincrement($pdo){
    static $checked=false; if($checked) return; $checked=true;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM productos LIKE 'id'")->fetch(PDO::FETCH_ASSOC);
        if($col && stripos($col['Extra']??'', 'auto_increment')===false){
            $pdo->exec('ALTER TABLE productos MODIFY id INT AUTO_INCREMENT PRIMARY KEY');
        }
    } catch(Exception $e){ /* silencioso */ }
}

/**
 * Ejecuta un INSERT/UPDATE con retry en caso de error 22001 (Data too long for column 'imagen').
 * $exec es un closure que recibe PDO y debe ejecutar la sentencia.
 */
function with_imagen_retry($pdo, $exec, $imagen){
    try {
        return $exec($pdo);
    } catch(PDOException $e){
        $msg = $e->getMessage();
        if(stripos($msg, "Data too long for column 'imagen'") !== false || $e->getCode()==='22001'){
            try { $pdo->exec('ALTER TABLE productos MODIFY imagen MEDIUMTEXT NULL'); } catch(Exception $e2){ /* ignorar */ }
            // Reintentar una vez
            try {
                return $exec($pdo);
            } catch(PDOException $e3){
                json_response([
                    'error'=>'Columna imagen demasiado pequeña (falló ampliación automática).',
                    'detalle'=>$e3->getMessage(),
                    'solucion'=>'Ejecute manualmente: ALTER TABLE productos MODIFY imagen MEDIUMTEXT NULL;'
                ],500);
            }
        }
        // Otro tipo de error
        throw $e;
    }
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
        // Asegurar migraciones automáticas necesarias
        ensure_imagen_column_large($pdo, $imagen);
        ensure_product_id_autoincrement($pdo);
        if (strlen($imagen) > 2000000) json_response(['error'=>'Imagen demasiado grande (>2MB codificado)'],400);
        $stmt = $pdo->prepare('INSERT INTO productos (titulo,precio,imagen) VALUES (?,?,?)');
        with_imagen_retry($pdo, function($pdo) use ($stmt,$titulo,$precio,$imagen){
            $stmt->execute([$titulo,$precio,$imagen]);
        }, $imagen);
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
        with_imagen_retry($pdo, function($pdo) use ($stmt,$titulo,$precio,$imagen,$id){
            $stmt->execute([$titulo,$precio,$imagen,$id]);
        }, $imagen);
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
