<?php
require __DIR__.'/config.php';
header('Content-Type: text/plain; charset=utf-8');
try {
  $pdo = db();
  echo "OK conexion PDO\n";
  $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
  echo "Tablas (".count($tables)."):\n";
  foreach($tables as $t){ echo " - {$t[0]}\n"; }
  $missing = [];
  $required = ['productos','usuarios','tags','producto_tags'];
  foreach($required as $r){
    $exists = $pdo->query("SHOW TABLES LIKE '".$r."'")->fetch();
    if(!$exists) $missing[] = $r;
  }
  if($missing){ echo "FALTAN tablas: ".implode(', ',$missing)."\n"; }
  $cProd = 0; $cUsr=0;
  try { $cProd = (int)$pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn(); } catch(Exception $e){ }
  try { $cUsr  = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(); } catch(Exception $e){ }
  echo "Conteo productos: $cProd\n";
  echo "Conteo usuarios: $cUsr\n";
} catch(Exception $e){
  echo "ERROR: ".$e->getMessage()."\n";
}
