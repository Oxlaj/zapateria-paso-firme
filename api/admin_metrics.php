<?php
require __DIR__ . '/config.php';
$pdo = db();
$method = request_method();

// Solo admin
require_role('admin');

if ($method !== 'GET') {
  json_response(['error' => 'Método no permitido'], 405);
}

// Filtros opcionales por fecha (para pedidos)
$from = isset($_GET['from']) ? trim($_GET['from']) : null; // YYYY-MM-DD
$to   = isset($_GET['to'])   ? trim($_GET['to'])   : null; // YYYY-MM-DD

// Construir cláusula WHERE segura para pedidos
$where = [];
$params = [];
if ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'p.creado_en >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $where[] = 'p.creado_en <= ?'; $params[] = $to   . ' 23:59:59'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$out = [
  'products'  => null,
  'cart'      => null,
  'users'     => null,
  'favorites' => null,
  'ventas'    => null,
  'compras'   => null,
];

// Productos
try {
  ensure_table('productos');
  $row = $pdo->query("SELECT COUNT(*) cnt, AVG(precio) avg_price, 
    SUM(CASE WHEN imagen IS NULL OR imagen='' THEN 1 ELSE 0 END) sin_imagen,
    SUM(CASE WHEN imagen IS NOT NULL AND imagen<>'' THEN 1 ELSE 0 END) con_imagen
    FROM productos")->fetch(PDO::FETCH_ASSOC);
  $out['products'] = [
    'count'       => (int)($row['cnt'] ?? 0),
    'avg_price'   => (float)($row['avg_price'] ?? 0),
    'with_image'  => (int)($row['con_imagen'] ?? 0),
    'without_img' => (int)($row['sin_imagen'] ?? 0),
  ];
} catch (Exception $e) {
  $out['products'] = ['count'=>0,'avg_price'=>0,'with_image'=>0,'without_img'=>0,'error'=>$e->getMessage()];
}

// Carrito global (distribución por talla y color)
try {
  ensure_table('carrito'); ensure_table('productos');
  $row = $pdo->query('SELECT COUNT(*) lineas, COALESCE(SUM(cantidad),0) items FROM carrito')->fetch(PDO::FETCH_ASSOC);
  $row2 = $pdo->query('SELECT COALESCE(SUM(c.cantidad * p.precio),0) total FROM carrito c JOIN productos p ON p.id=c.producto_id')->fetch(PDO::FETCH_ASSOC);
  $sizeRows = $pdo->query("SELECT talla, COALESCE(SUM(cantidad),0) qty FROM carrito GROUP BY talla ORDER BY qty DESC")->fetchAll();
  $colorRows = $pdo->query("SELECT color, COALESCE(SUM(cantidad),0) qty FROM carrito GROUP BY color ORDER BY qty DESC")->fetchAll();
  $out['cart'] = [
    'lines'     => (int)($row['lineas'] ?? 0),
    'items'     => (int)($row['items'] ?? 0),
    'total'     => (float)($row2['total'] ?? 0),
    'by_size'   => array_map(fn($r)=>['label'=>$r['talla'],'qty'=>(int)$r['qty']], $sizeRows),
    'by_color'  => array_map(fn($r)=>['label'=>$r['color'],'qty'=>(int)$r['qty']], $colorRows),
  ];
} catch (Exception $e) {
  $out['cart'] = ['lines'=>0,'items'=>0,'total'=>0,'by_size'=>[],'by_color'=>[],'error'=>$e->getMessage()];
}

// Usuarios
try {
  ensure_table('usuarios');
  $row = $pdo->query("SELECT COUNT(*) total, SUM(rol='admin') admins, SUM(rol='cliente') clientes FROM usuarios")->fetch(PDO::FETCH_ASSOC);
  $out['users'] = [
    'count'   => (int)($row['total'] ?? 0),
    'admins'  => (int)($row['admins'] ?? 0),
    'clients' => (int)($row['clientes'] ?? 0),
  ];
} catch (Exception $e) {
  $out['users'] = ['count'=>0,'admins'=>0,'clients'=>0,'error'=>$e->getMessage()];
}

// Favoritos
try {
  ensure_table('favoritos');
  $row = $pdo->query('SELECT COUNT(*) cnt FROM favoritos')->fetch(PDO::FETCH_ASSOC);
  $top = $pdo->query("SELECT p.id, p.titulo, COUNT(*) cnt
                      FROM favoritos f JOIN productos p ON p.id=f.producto_id
                      GROUP BY p.id, p.titulo ORDER BY cnt DESC, p.titulo ASC LIMIT 10")->fetchAll();
  $out['favorites'] = [
    'count' => (int)($row['cnt'] ?? 0),
    'top_products' => array_map(fn($r)=>['id'=>(int)$r['id'],'title'=>$r['titulo'],'count'=>(int)$r['cnt']], $top)
  ];
} catch (Exception $e) {
  $out['favorites'] = ['count'=>0,'top_products'=>[],'error'=>$e->getMessage()];
}

// Ventas (pedidos + pedido_items)
try {
  ensure_table('pedidos'); ensure_table('pedido_items');
  $stmt = $pdo->prepare("SELECT COUNT(*) pedidos, COALESCE(SUM(total),0) ingresos FROM pedidos p $whereSql");
  $stmt->execute($params); $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(pi.cantidad),0) items
                           FROM pedido_items pi JOIN pedidos p ON p.id=pi.pedido_id $whereSql");
  $stmt2->execute($params); $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);

  $stmt3 = $pdo->prepare("SELECT DATE_FORMAT(p.creado_en, '%Y-%m') ym,
                                  COALESCE(SUM(p.total),0) ingresos,
                                  COUNT(*) pedidos
                           FROM pedidos p $whereSql
                           GROUP BY ym ORDER BY ym");
  $stmt3->execute($params); $perMonth = $stmt3->fetchAll();

  $stmt4 = $pdo->prepare("SELECT pr.id, pr.titulo,
                                  COALESCE(SUM(pi.cantidad),0) qty,
                                  COALESCE(SUM(pi.cantidad*pi.precio),0) ingresos
                           FROM pedido_items pi
                           JOIN pedidos p ON p.id=pi.pedido_id
                           JOIN productos pr ON pr.id=pi.producto_id
                           " . ($where ? ('WHERE ' . implode(' AND ', array_map(fn($w)=>preg_replace('/^p\./','p.',$w), $where))) : '') . "
                           GROUP BY pr.id, pr.titulo
                           ORDER BY ingresos DESC, qty DESC, pr.titulo ASC
                           LIMIT 10");
  $stmt4->execute($params); $topProducts = $stmt4->fetchAll();

  $out['ventas'] = [
    'pedidos'    => (int)($row['pedidos'] ?? 0),
    'ingresos'   => (float)($row['ingresos'] ?? 0),
    'items'      => (int)($row2['items'] ?? 0),
    'por_mes'    => array_map(fn($r)=>['ym'=>$r['ym'],'ingresos'=>(float)$r['ingresos'],'pedidos'=>(int)$r['pedidos']], $perMonth),
    'top_products' => array_map(fn($r)=>['id'=>(int)$r['id'],'title'=>$r['titulo'],'qty'=>(int)$r['qty'],'ingresos'=>(float)$r['ingresos']], $topProducts)
  ];
} catch (Exception $e) {
  $out['ventas'] = ['pedidos'=>0,'ingresos'=>0,'items'=>0,'por_mes'=>[],'top_products'=>[],'error'=>$e->getMessage()];
}

// Compras (compras + compra_items)
try {
  ensure_table('compras'); ensure_table('compra_items');
  $where2 = [];
  $params2 = [];
  if ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where2[] = 'c.creado_en >= ?'; $params2[] = $from . ' 00:00:00'; }
  if ($to   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $where2[] = 'c.creado_en <= ?'; $params2[] = $to   . ' 23:59:59'; }
  $w2 = $where2 ? ('WHERE ' . implode(' AND ', $where2)) : '';
  $stmt = $pdo->prepare("SELECT COUNT(*) movimientos, COALESCE(SUM(total),0) total FROM compras c $w2");
  $stmt->execute($params2); $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt2 = $pdo->prepare("SELECT DATE_FORMAT(c.creado_en,'%Y-%m') ym, COALESCE(SUM(c.total),0) total, COUNT(*) compras
                          FROM compras c $w2 GROUP BY ym ORDER BY ym");
  $stmt2->execute($params2); $perMonth = $stmt2->fetchAll();
  $out['compras'] = [
    'movimientos' => (int)($row['movimientos'] ?? 0),
    'total'       => (float)($row['total'] ?? 0),
    'por_mes'     => array_map(fn($r)=>['ym'=>$r['ym'],'total'=>(float)$r['total'],'compras'=>(int)$r['compras']], $perMonth)
  ];
} catch(Exception $e){
  $out['compras'] = ['movimientos'=>0,'total'=>0,'por_mes'=>[],'error'=>$e->getMessage()];
}

json_response($out);

?>
