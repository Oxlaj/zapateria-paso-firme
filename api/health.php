<?php
require __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');
$info = [ 'ok'=>false ];
try {
  $pdo = db();
  $info['db'] = 'ok';
  $info['now'] = $pdo->query('SELECT NOW()')->fetchColumn();
  $prodCount = (int)$pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
  $info['productos'] = $prodCount;
  $info['ok'] = true;
  $u = current_user();
  if($u){ $info['user'] = ['id'=>$u['id'],'rol'=>$u['rol']]; }
} catch(Exception $e){
  $info['error'] = $e->getMessage();
}
echo json_encode($info, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
