<?php
require __DIR__ . '/config.php';
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

// Asegura existencia de tablas base involucradas
ensure_table('carrito');
ensure_table('productos');

try {
    if ($method === 'GET') {
        $items = $pdo->query('SELECT c.producto_id as id, c.talla, p.titulo, p.precio, p.imagen, c.cantidad FROM carrito c JOIN productos p ON p.id = c.producto_id ORDER BY c.producto_id, c.talla')->fetchAll(PDO::FETCH_ASSOC);
        $mapped = array_map(function($r){
            return [
                'id' => (int)$r['id'],
                'size' => (string)($r['talla'] ?? ''),
                'title' => $r['titulo'],
                'price' => (float)$r['precio'],
                'img' => $r['imagen'],
                'qty' => (int)$r['cantidad']
            ];
        }, $items);
        json_response(['cart'=>$mapped]);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if ($method === 'POST') { // agregar o incrementar
    $id = (int)($input['id'] ?? 0);
    $talla = trim((string)($input['talla'] ?? ''));
        if ($id <= 0) json_response(['error'=>'id requerido'],400);
    if ($talla==='') json_response(['error'=>'talla requerida'],400);
    $stmt = $pdo->prepare('INSERT INTO carrito (producto_id, talla, cantidad) VALUES (?,?,1) ON DUPLICATE KEY UPDATE cantidad = cantidad + 1');
    $stmt->execute([$id,$talla]);
        json_response(['ok'=>true]);
    }

    if ($method === 'PUT') { // actualizar cantidad
    $id = (int)($input['id'] ?? 0);
    $talla = trim((string)($input['talla'] ?? ''));
        $qty = max(1, (int)($input['qty'] ?? 1));
    $pdo->prepare('UPDATE carrito SET cantidad=? WHERE producto_id=? AND talla=?')->execute([$qty,$id,$talla]);
        json_response(['ok'=>true]);
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $talla = trim((string)($_GET['talla'] ?? ''));
        if ($id>0) { 
            if($talla!=='') $pdo->prepare('DELETE FROM carrito WHERE producto_id=? AND talla=?')->execute([$id,$talla]);
            else $pdo->prepare('DELETE FROM carrito WHERE producto_id=?')->execute([$id]);
        }
        json_response(['ok'=>true]);
    }

    http_response_code(405);
    json_response(['error'=>'Método no permitido']);
} catch(Exception $e){
    json_response(['error'=>'Error carrito','detalle'=>$e->getMessage()],500);
}
