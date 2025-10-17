<?php
require __DIR__ . '/config.php';
$pdo = db();
$method = request_method();

if ($method === 'GET') {
  $u = current_user();
  json_response(['user' => $u ? ['id'=>$u['id'],'nombre'=>$u['nombre'],'correo'=>$u['correo'],'rol'=>$u['rol']] : null]);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
  // Modos soportados:
  // 1) role + password fija (cliente123 / admin123)
  // 2) login por password hash, opcionalmente con nombre/correo
  $role = isset($input['role']) ? strtolower((string)$input['role']) : null;
  $pass = (string)($input['password'] ?? '');
  $nombre = isset($input['nombre']) ? trim((string)$input['nombre']) : null;
  $correo = isset($input['correo']) ? trim((string)$input['correo']) : null;
  if ($pass === '') json_response(['error'=>'Contraseña requerida'], 400);

  $fixed = [ 'cliente'=>'cliente123', 'admin'=>'admin123' ];

  if ($role && isset($fixed[$role])) {
    if ($pass !== $fixed[$role]) json_response(['error'=>'Credenciales inválidas'],401);
    // Intentar obtener usuario real del rol
    $stmt = $pdo->prepare('SELECT id,nombre,correo,rol FROM usuarios WHERE rol=? ORDER BY id LIMIT 1');
    $stmt->execute([$role]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$u){
      // Usuario ficticio en sesión (sin persistir nada sensible)
      $u = [ 'id'=> ($role==='admin'?1:0), 'nombre'=> ucfirst($role).' Local', 'correo'=> $role.'@local', 'rol'=>$role ];
    }
    $_SESSION['user'] = $u;
    json_response(['ok'=>true,'user'=>$_SESSION['user'],'mode'=>'fixed-role']);
  }

  // Modo: nombre/correo + password hash
  if ($nombre || $correo){
    $stmt = null; $val = null;
    if ($nombre){ $stmt = $pdo->prepare('SELECT id,nombre,correo,password_hash,rol FROM usuarios WHERE nombre=? LIMIT 1'); $val = $nombre; }
    else { $stmt = $pdo->prepare('SELECT id,nombre,correo,password_hash,rol FROM usuarios WHERE correo=? LIMIT 1'); $val = $correo; }
    $stmt->execute([$val]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row && password_verify($pass, $row['password_hash'])){
      $_SESSION['user'] = ['id'=>$row['id'],'nombre'=>$row['nombre'],'correo'=>$row['correo'],'rol'=>$row['rol']];
      json_response(['ok'=>true,'user'=>$_SESSION['user'],'mode'=>'user-pass']);
    }
    json_response(['error'=>'Credenciales inválidas'],401);
  }

  // Compatibilidad: si no se envía role ni nombre/correo, seguir modo password-hash global (recorre usuarios)
  $stmt = $pdo->query('SELECT id,nombre,correo,password_hash,rol FROM usuarios ORDER BY id');
  while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    if(password_verify($pass, $row['password_hash'])){ $_SESSION['user'] = ['id'=>$row['id'],'nombre'=>$row['nombre'],'correo'=>$row['correo'],'rol'=>$row['rol']]; json_response(['ok'=>true,'user'=>$_SESSION['user'],'mode'=>'hash']); }
  }
  json_response(['error'=>'Contraseña inválida'],401);
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
