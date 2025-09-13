<?php
require __DIR__.'/config.php';
$pdo = db();
$rows = $pdo->query('SELECT id, nombre, texto, creado_en FROM testimonios ORDER BY id')->fetchAll();
json_response(['testimonios'=>$rows, 'count'=>count($rows)]);
