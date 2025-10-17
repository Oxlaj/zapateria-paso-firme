<?php
// Script para poblar datos DEMO para el dashboard
// Uso: php api/seed_demo.php [--months=6] [--orders=120] [--users=15] [--sales=15] [--purchases=15]
require __DIR__ . '/config.php';
$pdo = db();

// Parse args simples
$args = $argv ?? [];
function argVal($name, $def){
  global $args; foreach($args as $a){ if(strpos($a, "--$name=")===0){ return (int)substr($a, strlen($name)+3); } }
  return $def;
}
$months    = max(1, argVal('months', 6));
$orders    = max(1, argVal('orders', 120));
$usersN    = max(1, argVal('users', 15));
$sales     = max(1, argVal('sales', 15));       // ventas exactas a generar
$purchases = max(1, argVal('purchases', 15));   // compras exactas a generar

echo "Generando datos demo: months=$months orders=$orders users=$usersN sales=$sales purchases=$purchases\n";

// Asegurar tablas
foreach(['productos','tags','producto_tags','carrito','usuarios','favoritos','testimonios','pedidos','pedido_items'] as $t){ ensure_table($t); }

$pdo->beginTransaction();
try {
  // 1) Productos semilla si hay pocos
  $prodCount = (int)$pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
  if ($prodCount < 10) {
    $imgs = [
      'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_2fc74464.jpg',
      'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.20_ffb7cb86.jpg',
    ];
    $base = [
      ['Zapato Casual Premium', 349.00],
      ['Tenis Deportivos Pro', 429.00],
      ['Bota Urbana', 499.00],
      ['Mocasín Clásico', 379.00],
      ['Sandalia Confort', 189.00],
      ['Zapato Escolar', 219.00],
      ['Tenis Running', 459.00],
      ['Bota Trekking', 559.00],
      ['Zapato Formal', 399.00],
      ['Tenis Casual', 299.00],
    ];
    $stmt = $pdo->prepare('INSERT INTO productos (titulo,precio,imagen) VALUES (?,?,?)');
    foreach($base as $i=>$p){ $img = $imgs[$i % count($imgs)]; $stmt->execute([$p[0], $p[1], $img]); }
    echo "Productos demo insertados.\n";
  }

  // 2) Usuarios demo (clientes) si hay pocos
  $uCount = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='cliente'")->fetchColumn();
  if ($uCount < $usersN) {
    $toAdd = $usersN - $uCount;
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre,correo,password_hash,rol) VALUES (?,?,?,"cliente")');
    for($i=0;$i<$toAdd;$i++){
      $name = 'Cliente Demo '.($uCount+$i+1);
      $mail = 'cliente'.($uCount+$i+1).'@demo.local';
      $hash = password_hash('cliente123', PASSWORD_BCRYPT);
      $stmt->execute([$name, $mail, $hash]);
    }
    echo "Usuarios cliente demo insertados.\n";
  }

  // 3) Vaciar estados volátiles (carrito, favoritos) para repoblar demo
  $pdo->exec('DELETE FROM carrito');
  $pdo->exec('DELETE FROM favoritos');

  // 4) Carrito demo (distribución por tallas/colores)
  $sizes = ['37','38','39','40','41','42','43'];
  $colors = ['negro','cafe'];
  $prodIds = $pdo->query('SELECT id, precio FROM productos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  if ($prodIds){
    $insC = $pdo->prepare('INSERT INTO carrito (producto_id, talla, color, cantidad) VALUES (?,?,?,?)');
    foreach($prodIds as $p){
      // 1-3 líneas por producto
      $lines = rand(1,3);
      for($i=0;$i<$lines;$i++){
        $s = $sizes[array_rand($sizes)];
        $c = $colors[array_rand($colors)];
        $qty = rand(1,4);
        // Clave compuesta: on duplicate key incrementa cantidad
        try { $insC->execute([$p['id'], $s, $c, $qty]); }
        catch(Exception $e){ /* si duplica, actualizar */
          $pdo->prepare('UPDATE carrito SET cantidad=cantidad+? WHERE producto_id=? AND talla=? AND color=?')
              ->execute([$qty, $p['id'], $s, $c]);
        }
      }
    }
    echo "Carrito demo generado.\n";
  }

  // 5) Favoritos demo aleatorios por usuario
  $userIds = $pdo->query("SELECT id FROM usuarios WHERE rol='cliente' ORDER BY id LIMIT " . (int)$usersN)->fetchAll(PDO::FETCH_COLUMN);
  if ($userIds && $prodIds){
    $insF = $pdo->prepare('INSERT IGNORE INTO favoritos (usuario_id, producto_id) VALUES (?,?)');
    foreach($userIds as $uid){
      $favCount = rand(2, 6);
      $picked = array_rand($prodIds, min($favCount, count($prodIds)));
      $picked = is_array($picked) ? $picked : [$picked];
      foreach($picked as $idx){ $insF->execute([$uid, $prodIds[$idx]['id']]); }
    }
    echo "Favoritos demo generados.\n";
  }

  // 6) Pedidos e items distribuidos (exactamente $sales ventas)
  // Limpiar pedidos existentes DEMO (opcional: si quieres conservar, comenta estas líneas)
  $pdo->exec('DELETE FROM pedido_items');
  $pdo->exec('DELETE FROM pedidos');
  $insP = $pdo->prepare('INSERT INTO pedidos (usuario_id, estado, total, creado_en) VALUES (?,?,?,?)');
  $insI = $pdo->prepare('INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio) VALUES (?,?,?,?)');

  // Generar exactamente $sales ventas repartidas en los últimos $months meses
  $now = new DateTime('now');
  $monthDates = [];
  for($m=$months-1; $m>=0; $m--){ $monthDates[] = (clone $now)->modify("first day of -$m month"); }
  for($s=0;$s<$sales;$s++){
    $monthDate = $monthDates[$s % count($monthDates)];
    $uid = $userIds ? $userIds[array_rand($userIds)] : null;
    $day = rand(1, (int)$monthDate->format('t'));
    $h = rand(8, 20); $min = rand(0,59); $sec = rand(0,59);
    $date = DateTime::createFromFormat('Y-m-d H:i:s', sprintf('%s-%02d %02d:%02d:%02d', $monthDate->format('Y-m'), $day, $h,$min,$sec));
    $itemsN = rand(1, 3);
    $total = 0.0; $items = [];
    for($j=0;$j<$itemsN;$j++){
      $pp = $prodIds[array_rand($prodIds)];
      $qty = rand(1, 3);
      $price = (float)$pp['precio'];
      $total += $qty * $price;
      $items[] = [$pp['id'], $qty, $price];
    }
    $insP->execute([$uid, 'completado', $total, $date->format('Y-m-d H:i:s')]);
    $pid = (int)$pdo->lastInsertId();
    foreach($items as $it){ $insI->execute([$pid, $it[0], $it[1], $it[2]]); }
  }
  echo "Ventas demo generadas ($sales).\n";

  // 7) Compras demo (exactamente $purchases)
  $pdo->exec('DELETE FROM compra_items');
  $pdo->exec('DELETE FROM compras');
  $insC = $pdo->prepare('INSERT INTO compras (proveedor, total, creado_en) VALUES (?,?,?)');
  $insCI = $pdo->prepare('INSERT INTO compra_items (compra_id, producto_id, cantidad, costo) VALUES (?,?,?,?)');
  $proveedores = ['Proveedor Alfa','Proveedor Beta','Proveedor Gamma','Proveedor Delta'];
  for($c=0;$c<$purchases;$c++){
    $monthDate = $monthDates[$c % count($monthDates)];
    $prov = $proveedores[array_rand($proveedores)];
    $day = rand(1, (int)$monthDate->format('t'));
    $h = rand(9, 18); $min = rand(0,59); $sec = rand(0,59);
    $date = DateTime::createFromFormat('Y-m-d H:i:s', sprintf('%s-%02d %02d:%02d:%02d', $monthDate->format('Y-m'), $day, $h,$min,$sec));
    $itemsN = rand(1,3);
    $total = 0.0; $items = [];
    for($j=0;$j<$itemsN;$j++){
      $pp = $prodIds[array_rand($prodIds)];
      $qty = rand(2, 6);
      $costo = round(((float)$pp['precio']) * (0.55 + (mt_rand(0,20)/100)), 2); // costo ~55%-75% del precio
      $total += $qty * $costo;
      $items[] = [$pp['id'], $qty, $costo];
    }
    $insC->execute([$prov, $total, $date->format('Y-m-d H:i:s')]);
    $cid = (int)$pdo->lastInsertId();
    foreach($items as $it){ $insCI->execute([$cid, $it[0], $it[1], $it[2]]); }
  }
  echo "Compras demo generadas ($purchases).\n";

  $pdo->commit();
  echo "SEED DEMO COMPLETADO\n";
} catch(Exception $e){
  $pdo->rollBack();
  echo "ERROR SEED: ".$e->getMessage()."\n";
  exit(1);
}

?>
