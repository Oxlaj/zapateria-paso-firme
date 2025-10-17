<?php
require __DIR__ . '/config.php';
$pdo = db();
$method = request_method();

if ($method !== 'POST') {
  json_response(['error'=>'Método no permitido'],405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$nombre = trim((string)($input['nombre'] ?? ''));
$correoIn = trim((string)($input['correo'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($nombre === '' || $password === '') {
  json_response(['error'=>'Nombre y contraseña son requeridos'],400);
}
if (strlen($password) < 6) {
  json_response(['error'=>'La contraseña debe tener al menos 6 caracteres'],400);
}

try {
  // Determinar correo a usar: si viene desde el cliente, usarlo; si no, generar uno sintético
  $correo = $correoIn;
  if ($correo === ''){
    // Generar correo sintético para cumplir restricción NOT NULL + UNIQUE
    // Formato: slug-nombre+timestamp@local
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/','-', $nombre), '-'));
    if($slug==='') $slug = 'cliente';
    $correo = $slug . '+' . time() . '@local';
    // Asegurar unicidad (intento simple)
    for($i=0;$i<3;$i++){
      $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo=? LIMIT 1');
      $stmt->execute([$correo]);
      if(!$stmt->fetch()) break;
      $correo = $slug . '+' . time() . rand(100,999) . '@local';
    }
  } else {
    // Validación básica de formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)){
      json_response(['error'=>'Correo inválido'],400);
    }
    // Verificar unicidad de correo provisto
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo=? LIMIT 1');
    $stmt->execute([$correo]);
    if($stmt->fetch()){
      json_response(['error'=>'El correo ya está registrado'],409);
    }
  }

  $hash = password_hash($password, PASSWORD_BCRYPT);
  $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES (?,?,?,"cliente")');
  $stmt->execute([$nombre, $correo, $hash]);
  $id = (int)$pdo->lastInsertId();
  // No iniciar sesión aquí; el usuario debe volver al login y autenticarse con su nombre y contraseña
  json_response(['ok'=>true, 'id'=>$id]);
} catch(Exception $e){
  json_response(['error'=>'Error registrando usuario','detalle'=>$e->getMessage()],500);
}

?>
