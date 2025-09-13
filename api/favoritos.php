<?php
require __DIR__ . '/config.php';
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

// Requiere usuario autenticado para asociar favoritos (usa session user id)
$u = current_user();
$user_id = $u ? (int)$u['id'] : 0;

// Asegurar que existe tabla 'favoritos' acorde al esquema normalizado.
ensure_table('favoritos');

if ($method === 'GET') {
    if(!$u){ // No autenticado: devolver lista vacía (Frontend lo tomará como "sin favoritos")
        json_response(['favs'=>[]]);
    }
    $stmt = $pdo->prepare('SELECT producto_id FROM favoritos WHERE usuario_id=? ORDER BY producto_id');
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $ids = array_map('intval', $rows);
    json_response(['favs'=>$ids]);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') { // agregar
    if(!$u) json_response(['error'=>'No autenticado'],401);
    $id = (int)($input['id'] ?? 0);
    if ($id<=0) json_response(['error'=>'id requerido'],400);
    $pdo->prepare('INSERT IGNORE INTO favoritos (usuario_id, producto_id) VALUES (?,?)')->execute([$user_id,$id]);
    json_response(['ok'=>true]);
}
if ($method === 'DELETE') { // quitar
    if(!$u) json_response(['error'=>'No autenticado'],401);
    $id = (int)($_GET['id'] ?? 0);
    if ($id>0) { $pdo->prepare('DELETE FROM favoritos WHERE usuario_id=? AND producto_id=?')->execute([$user_id,$id]); }
    json_response(['ok'=>true]);
}

http_response_code(405);
json_response(['error'=>'Método no permitido']);
