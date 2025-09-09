<?php
require __DIR__ . '/config.php';
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $u = current_user();
  json_response(['user' => $u ? ['id'=>$u['id'],'nombre'=>$u['nombre'],'correo'=>$u['correo'],'rol'=>$u['rol']] : null]);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
  $correo = trim($input['correo'] ?? '');
  $pass = (string)($input['password'] ?? '');
  if ($correo === '' || $pass === '') json_response(['error'=>'Campos requeridos'], 400);
  $stmt = $pdo->prepare('SELECT id,nombre,correo,password_hash,rol FROM usuarios WHERE correo=? LIMIT 1');
  $stmt->execute([$correo]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$u || !password_verify($pass, $u['password_hash'])) {
    json_response(['error'=>'Credenciales inválidas'], 401);
  }
  $_SESSION['user'] = ['id'=>$u['id'],'nombre'=>$u['nombre'],'correo'=>$u['correo'],'rol'=>$u['rol']];
  json_response(['ok'=>true, 'user'=>$_SESSION['user']]);
}

if ($method === 'DELETE') { // logout
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
  json_response(['ok'=>true]);
}

http_response_code(405);
json_response(['error'=>'Método no permitido']);
