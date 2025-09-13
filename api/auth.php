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
  // Modo simplificado: solo contraseña. Determina rol según coincidencia con usuarios existentes.
  $pass = (string)($input['password'] ?? '');
  if ($pass === '') json_response(['error'=>'Contraseña requerida'], 400);
  // Estrategia: buscar todos los usuarios y verificar primer hash que calce.
  $stmt = $pdo->query('SELECT id,nombre,correo,password_hash,rol FROM usuarios ORDER BY id');
  $found = null;
  while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    if(password_verify($pass, $row['password_hash'])){ $found = $row; break; }
  }
  if(!$found){ json_response(['error'=>'Contraseña inválida'],401); }
  $_SESSION['user'] = ['id'=>$found['id'],'nombre'=>$found['nombre'],'correo'=>$found['correo'],'rol'=>$found['rol']];
  json_response(['ok'=>true,'user'=>$_SESSION['user']]);
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
