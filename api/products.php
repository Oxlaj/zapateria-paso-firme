<?php
require __DIR__ . '/config.php';
$pdo = db();
// Semilla básica desde data.js (solo si tabla vacía)
$count = $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
if ($count == 0) {
    // Productos mínimos (puedes sincronizar manualmente con data.js)
    $seed = [
        [1,'Zapato casual clásico',350,'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_2fc74464.jpg','casual'],
        [2,'Tenis deportivos',420,'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.20_ffb7cb86.jpg','deporte'],
    ];
    $stmt = $pdo->prepare('INSERT INTO productos (id,titulo,precio,imagen,etiquetas) VALUES (?,?,?,?,?)');
    foreach($seed as $p){ $stmt->execute($p); }
}
$rows = $pdo->query('SELECT * FROM productos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
// Mapear a las claves usadas por el frontend (title, price, img, tags)
$mapped = array_map(function($r){
    return [
        'id' => (int)$r['id'],
        'title' => $r['titulo'],
        'price' => (float)$r['precio'],
        'img' => $r['imagen'],
        'tags' => $r['etiquetas']
    ];
}, $rows);
json_response(['products'=>$mapped]);
