<?php
require __DIR__.'/config.php';
header('Content-Type: text/plain; charset=utf-8');
try {
  $pdo = db();
  echo "== Diagnóstico BD ==\n";
  echo "Host: 127.0.0.1  DB: calzado_oxlaj\n";
  echo "Hora servidor: ".$pdo->query('SELECT NOW()')->fetchColumn()."\n";
  echo "OK conexion PDO\n\n";

  $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
  $tableNames = array_map(fn($r)=>$r[0], $tables);
  echo "Tablas (".count($tables)."):\n";
  foreach($tableNames as $tn){ echo " - $tn\n"; }
  echo "\n";
  $required = ['productos','usuarios','tags','producto_tags','carrito','favoritos'];
  $missing = array_values(array_diff($required, $tableNames));
  if($missing){ echo "FALTAN tablas: ".implode(', ',$missing)."\n\n"; }

  $counts = [
    'productos' => 'SELECT COUNT(*) FROM productos',
    'usuarios' => 'SELECT COUNT(*) FROM usuarios',
    'carrito' => 'SELECT COUNT(*) FROM carrito',
    'favoritos' => 'SELECT COUNT(*) FROM favoritos',
    'testimonios' => 'SELECT COUNT(*) FROM testimonios'
  ];
  foreach($counts as $label=>$sql){
    try { $c = (int)$pdo->query($sql)->fetchColumn(); echo "Conteo $label: $c\n"; }
    catch(Exception $e){ echo "Conteo $label: ERROR (".$e->getMessage().")\n"; }
  }

  // Mostrar primeras filas carrito si existen
  if(in_array('carrito',$tableNames)){
    $rows = $pdo->query('SELECT producto_id,cantidad FROM carrito ORDER BY producto_id LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
    echo "\nCarrito (primeros 10):\n";
    if(!$rows){ echo "  (vacío)\n"; }
    else { foreach($rows as $r){ echo "  producto_id=".$r['producto_id']." cantidad=".$r['cantidad']."\n"; } }
  }
  // Mostrar favoritos del primer usuario si existen
  if(in_array('favoritos',$tableNames) && in_array('usuarios',$tableNames)){
    $firstUser = $pdo->query('SELECT id,correo FROM usuarios ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if($firstUser){
      $favRows = $pdo->prepare('SELECT producto_id FROM favoritos WHERE usuario_id=? ORDER BY producto_id');
      $favRows->execute([$firstUser['id']]);
      $favIds = $favRows->fetchAll(PDO::FETCH_COLUMN);
      echo "\nFavoritos usuario #{$firstUser['id']} ({$firstUser['correo']}): ".( $favIds? implode(',', $favIds): '(ninguno)')."\n";
    }
  }
} catch(Exception $e){
  echo "ERROR: ".$e->getMessage()."\n";
}
