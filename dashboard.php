<?php
// Página protegida: sólo ADMIN
require __DIR__ . '/api/config.php';

// Usar verificación de sesión para páginas HTML (redirección en vez de JSON)
$u = current_user();
if(!$u || ($u['rol'] ?? '') !== 'admin'){
    header('Location: /?unauthorized=1');
    exit;
}

// Seleccionar el archivo de dashboard a mostrar
$candidateFiles = [
    __DIR__ . '/panel/dashboard.html', // recomendado: coloca aquí tu HTML de dashboard
    __DIR__ . '/panel/index.html',     // fallback si ya tienes un panel existente
];
$target = null;
foreach($candidateFiles as $f){ if (is_file($f)) { $target = $f; break; } }

header('Content-Type: text/html; charset=utf-8');
if($target){
    readfile($target);
} else {
    echo "<!doctype html><html lang='es'><head><meta charset='utf-8'><title>Dashboard</title></head><body>";
    echo "<h1>Dashboard administrador</h1><p>No se encontró <code>panel/dashboard.html</code> ni <code>panel/index.html</code>. Crea uno de esos archivos con el contenido de tu dashboard.</p>";
    echo "<p><a href='/'>&larr; Volver al sitio</a></p>";
    echo "</body></html>";
}
?>
