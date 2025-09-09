<?php
// Configuración de conexión MySQL (compatible con XAMPP)
// Se pueden sobreescribir con variables de entorno o un archivo config.local.php
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = (int)(getenv('DB_PORT') ?: 3306);
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$DB_NAME = getenv('DB_NAME') ?: 'calzado_oxlaj';

// Permite overrides locales sin commitear
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
  /** @noinspection PhpIncludeInspection */
  require $localConfig; // este archivo puede redefinir $DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $DB_NAME
}

function db() {
    global $DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $DB_NAME;
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Crear BD si no existe (idempotente)
            $pdoTmp = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;charset=utf8mb4", $DB_USER, $DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdoTmp->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Conectar a la BD
            $pdo = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Crear tablas en español si no existen
            $pdo->exec("CREATE TABLE IF NOT EXISTS productos (id INT PRIMARY KEY, titulo VARCHAR(150) NOT NULL, precio DECIMAL(10,2) NOT NULL, imagen VARCHAR(255), etiquetas VARCHAR(255), creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS categorias (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) UNIQUE NOT NULL)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS producto_categorias (producto_id INT NOT NULL, categoria_id INT NOT NULL, PRIMARY KEY(producto_id, categoria_id))");
            $pdo->exec("CREATE TABLE IF NOT EXISTS carrito (id INT AUTO_INCREMENT PRIMARY KEY, producto_id INT NOT NULL, cantidad INT NOT NULL DEFAULT 1, UNIQUE KEY uniq_prod (producto_id))");
            $pdo->exec("CREATE TABLE IF NOT EXISTS favoritos (id INT AUTO_INCREMENT PRIMARY KEY, producto_id INT NOT NULL, UNIQUE KEY uniq_fav (producto_id))");
            $pdo->exec("CREATE TABLE IF NOT EXISTS testimonios (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120) NOT NULL, texto TEXT NOT NULL, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120) NOT NULL, correo VARCHAR(180) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL, rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente', creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            // Semilla mínima de usuarios si está vacío
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            if ($cnt === 0) {
                $stmt = $pdo->prepare('INSERT INTO usuarios (nombre,correo,password_hash,rol) VALUES (?,?,?,?)');
                // admin123
                $stmt->execute(['Administrador','admin@oxlaj.local','$2y$10$0kXyq1VvC2m6yHqkS1q7HuAau0nTqv7e1H0t3Y8w6bqN4n8X7w8d2','admin']);
                // cliente123
                $stmt->execute(['Cliente Demo','cliente@oxlaj.local','$2y$10$2uQyNf0mG5l0vYV2bqk2qO3m1s2t3u4v5w6x7y8z9a0b1c2d3e4f6','cliente']);
            }
            $pdo->exec("CREATE TABLE IF NOT EXISTS mensajes_contacto (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120), correo VARCHAR(180), mensaje TEXT, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS pedidos (id INT AUTO_INCREMENT PRIMARY KEY, estado VARCHAR(30) DEFAULT 'pendiente', total DECIMAL(10,2) DEFAULT 0, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS pedido_items (id INT AUTO_INCREMENT PRIMARY KEY, pedido_id INT NOT NULL, producto_id INT NOT NULL, cantidad INT NOT NULL, precio DECIMAL(10,2) NOT NULL)");
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Error de conexión a la base de datos', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    return $pdo;
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_role($role) {
    $u = current_user();
    if (!$u || $u['rol'] !== $role) {
        json_response(['error'=>'No autorizado'], 401);
    }
}
