<?php
require_once __DIR__.'/config.php';

/**
 * Obtiene ID de un tag, creándolo si no existe.
 */
function ensure_tag(string $nombre): int {
    $nombre = trim($nombre);
    if ($nombre==='') return 0;
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM tags WHERE nombre=? LIMIT 1');
    $stmt->execute([$nombre]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;
    $ins = $pdo->prepare('INSERT INTO tags (nombre) VALUES (?)');
    $ins->execute([$nombre]);
    return (int)$pdo->lastInsertId();
}

/**
 * Sincroniza lista de tags (array de strings) para un producto dado.
 */
function sync_product_tags(int $productoId, array $tags): void {
    $pdo = db();
    $pdo->prepare('DELETE FROM producto_tags WHERE producto_id=?')->execute([$productoId]);
    $unique = [];
    foreach ($tags as $t) {
        $t = trim($t);
        if ($t==='') continue;
        $key = mb_strtolower($t);
        if (isset($unique[$key])) continue;
        $unique[$key] = true;
        $tagId = ensure_tag($t);
        if ($tagId>0) {
            $pdo->prepare('INSERT IGNORE INTO producto_tags (producto_id, tag_id) VALUES (?,?)')->execute([$productoId,$tagId]);
        }
    }
}

/**
 * Retorna array de strings con los tags de un producto.
 */
function get_product_tags(int $productoId): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT t.nombre FROM producto_tags pt JOIN tags t ON t.id=pt.tag_id WHERE pt.producto_id=? ORDER BY t.nombre');
    $stmt->execute([$productoId]);
    return array_map(fn($r)=>$r['nombre'], $stmt->fetchAll());
}
