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
