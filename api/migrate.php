<?php
require __DIR__ . '/config.php';
$pdo = db();
// Ejecutar schema.sql por si el usuario prefiere PHP en vez de cliente MySQL
$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    echo "schema.sql no encontrado\n";
    exit(1);
}
$sql = file_get_contents($schemaFile);
try {
    $pdo->exec($sql);
    echo "Migración completada.\n";
} catch (PDOException $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}

// Asegurar que la columna imagen sea MEDIUMTEXT en instalaciones previas.
try {
    $col = $pdo->query("SHOW COLUMNS FROM productos LIKE 'imagen'")->fetch(PDO::FETCH_ASSOC);
    if($col && stripos($col['Type']??'', 'varchar(')!==false){
        $pdo->exec('ALTER TABLE productos MODIFY imagen MEDIUMTEXT NULL');
        echo "Columna productos.imagen actualizada a MEDIUMTEXT.\n";
    }
} catch(Exception $e){ /* silencioso */ }

// Asegurar AUTO_INCREMENT en productos.id
try {
    $col = $pdo->query("SHOW COLUMNS FROM productos LIKE 'id'")->fetch(PDO::FETCH_ASSOC);
    if($col && stripos($col['Extra']??'', 'auto_increment')===false){
        $pdo->exec('ALTER TABLE productos MODIFY id INT AUTO_INCREMENT PRIMARY KEY');
        echo "Columna productos.id ajustada a AUTO_INCREMENT.\n";
    }
} catch(Exception $e){ /* silencioso */ }
