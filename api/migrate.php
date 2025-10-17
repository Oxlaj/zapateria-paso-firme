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

// Normalizar esquema de carrito para compatibilidad con API actual
try {
    $cols = $pdo->query("SHOW COLUMNS FROM carrito")->fetchAll(PDO::FETCH_ASSOC);
    $names = array_map(fn($c)=>$c['Field'] ?? '', $cols);
    $names = array_values(array_filter($names));

    if (!in_array('talla', $names, true)) {
        $pdo->exec("ALTER TABLE carrito ADD talla VARCHAR(5) NOT NULL DEFAULT '' AFTER producto_id");
        echo "Tabla carrito: columna talla agregada.\n";
    }
    if (!in_array('color', $names, true)) {
        $pdo->exec("ALTER TABLE carrito ADD color ENUM('negro','cafe') NOT NULL DEFAULT 'negro' AFTER talla");
        echo "Tabla carrito: columna color agregada.\n";
    }
    // Asegurar cantidad con default 1
    try {
        $pdo->exec("ALTER TABLE carrito MODIFY cantidad INT NOT NULL DEFAULT 1");
    } catch(Exception $e) { /* ignorar si ya está correcto */ }

    // Asegurar clave primaria compuesta (producto_id, talla, color)
    try {
        $pk = $pdo->query("SHOW KEYS FROM carrito WHERE Key_name='PRIMARY'")->fetchAll(PDO::FETCH_ASSOC);
        $pkCols = array_column($pk, 'Column_name');
        $desired = ['producto_id','talla','color'];
        $needPk = (count($pkCols) !== count($desired)) || (array_map('strtolower',$pkCols) !== $desired);
        if ($needPk) {
            $pdo->exec("ALTER TABLE carrito DROP PRIMARY KEY");
            $pdo->exec("ALTER TABLE carrito ADD PRIMARY KEY (producto_id, talla, color)");
            echo "Tabla carrito: clave primaria compuesta (producto_id,talla,color) aplicada.\n";
        }
    } catch(Exception $e){ /* ignorar si no aplica */ }
} catch(Exception $e){ /* silencioso si la tabla no existe */ }

// Verificar tablas de compras (por si esquema no se aplicó por completo)
try {
    $pdo->query('SELECT 1 FROM compras LIMIT 1');
    $pdo->query('SELECT 1 FROM compra_items LIMIT 1');
} catch(Exception $e){
    try {
        // Asegurar engines InnoDB en tablas referenciadas
        try { $pdo->exec('CREATE TABLE IF NOT EXISTS `compras` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `proveedor` VARCHAR(160) NULL, `total` DECIMAL(10,2) DEFAULT 0, `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB'); } catch(Exception $ie){}
        try { $pdo->exec('ALTER TABLE `productos` ENGINE=InnoDB'); } catch(Exception $ie){}
        try { $pdo->exec('ALTER TABLE `compras` ENGINE=InnoDB'); } catch(Exception $ie){}
        // Intentar crear compra_items con FKs
        $pdo->exec('CREATE TABLE IF NOT EXISTS `compra_items` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `compra_id` INT UNSIGNED NOT NULL,
            `producto_id` INT UNSIGNED NOT NULL,
            `cantidad` INT NOT NULL,
            `costo` DECIMAL(10,2) NOT NULL,
            KEY `idx_ci_compra` (`compra_id`),
            KEY `idx_ci_producto` (`producto_id`),
            CONSTRAINT `fk_ci_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_ci_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB');
    } catch(Exception $e2){
        // Fallback: crear sin FKs si el hosting no permite
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS `compra_items` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `compra_id` INT UNSIGNED NOT NULL,
                `producto_id` INT UNSIGNED NOT NULL,
                `cantidad` INT NOT NULL,
                `costo` DECIMAL(10,2) NOT NULL,
                KEY `idx_ci_compra` (`compra_id`),
                KEY `idx_ci_producto` (`producto_id`)
            ) ENGINE=InnoDB');
            echo "Tabla compra_items creada sin llaves foráneas (fallback).\n";
        } catch(Exception $e3){ /* reportar pero no abortar */ echo "No se pudo crear compra_items: ".$e3->getMessage()."\n"; }
    }
}
