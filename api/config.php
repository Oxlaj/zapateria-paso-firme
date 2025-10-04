<?php
// Configuración de conexión MySQL normalizada (NO crea tablas ya que el esquema
// fue provisionado con el script SQL que incluye productos, tags, producto_tags, etc.)
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = (int)(getenv('DB_PORT') ?: 3306);
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$DB_NAME = getenv('DB_NAME') ?: 'calzado_oxlaj';

// Overrides locales opcionales (no versionado)
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig; // puede redefinir credenciales
}

function db() {
    global $DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $DB_NAME;    
    static $pdo = null;
    if ($pdo === null) {
        $portsToTry = [$DB_PORT];
        // Intentar puertos comunes si estás en entorno local y el primero falla
        foreach ([3307, 33060] as $alt){ if(!in_array($alt, $portsToTry, true)) $portsToTry[] = $alt; }
        $lastError = null;
        foreach($portsToTry as $p){
            try {
                $dsn = "mysql:host={$DB_HOST};port={$p};dbname={$DB_NAME};charset=utf8mb4";
                $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                // Si conectó con un puerto alternativo, actualizar en memoria para siguientes requests
                $DB_PORT = $p;
                break;
            } catch (PDOException $e) {
                $lastError = $e;
                $pdo = null; // asegurar reintento
            }
        }
        if($pdo === null){
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'=>'No se pudo conectar a la base de datos',
                'host'=>$DB_HOST,
                'puertos_intentados'=>$portsToTry,
                'detalle'=>$lastError? $lastError->getMessage() : 'desconocido'
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
    return $pdo;
}

function json_response($data, $code=200){
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }

function current_user(){ return $_SESSION['user'] ?? null; }
function require_role($role){ $u=current_user(); if(!$u || $u['rol']!==$role){ json_response(['error'=>'No autorizado'],401);} }

// Método de request con soporte para override (para hostings que bloquean PUT/DELETE)
function request_method(){
    $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($m === 'POST'){
        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']
            ?? ($_POST['_method'] ?? $_GET['_method'] ?? null);
        if ($override){
            $om = strtoupper($override);
            if (in_array($om, ['GET','POST','PUT','PATCH','DELETE'], true)) return $om;
        }
    }
    return $m;
}

// Función auxiliar para validar que la tabla exista (opcional en endpoints)
function ensure_table($name){
    $pdo = db();
    try { $pdo->query("SELECT 1 FROM `".$name."` LIMIT 1"); }
    catch(Exception $e){ json_response(['error'=>'Tabla faltante: '.$name,'detalle'=>$e->getMessage()],500); }
}
