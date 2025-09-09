<?php
require __DIR__ . '/config.php';
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

// Solo admin
require_role('admin');

if ($method === 'GET') {
  $rows = $pdo->query('SELECT * FROM productos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  json_response(['items'=>$rows]);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
  $id = (int)($input['id'] ?? 0);
  $titulo = trim($input['titulo'] ?? '');
  $precio = (float)($input['precio'] ?? 0);
  $imagen = trim($input['imagen'] ?? '');
  $etiquetas = trim($input['etiquetas'] ?? '');
  if ($id<=0 || $titulo==='') json_response(['error'=>'Datos inválidos'], 400);
  $stmt = $pdo->prepare('INSERT INTO productos (id,titulo,precio,imagen,etiquetas) VALUES (?,?,?,?,?)');
  $stmt->execute([$id,$titulo,$precio,$imagen,$etiquetas]);
  json_response(['ok'=>true]);
}

if ($method === 'PUT') {
  $id = (int)($input['id'] ?? 0);
  $titulo = trim($input['titulo'] ?? '');
  $precio = (float)($input['precio'] ?? 0);
  $imagen = trim($input['imagen'] ?? '');
  $etiquetas = trim($input['etiquetas'] ?? '');
  if ($id<=0 || $titulo==='') json_response(['error'=>'Datos inválidos'], 400);
  $stmt = $pdo->prepare('UPDATE productos SET titulo=?, precio=?, imagen=?, etiquetas=? WHERE id=?');
  $stmt->execute([$titulo,$precio,$imagen,$etiquetas,$id]);
  json_response(['ok'=>true]);
}

if ($method === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id>0) { $pdo->prepare('DELETE FROM productos WHERE id=?')->execute([$id]); }
  json_response(['ok'=>true]);
}

http_response_code(405);
json_response(['error'=>'Método no permitido']);
