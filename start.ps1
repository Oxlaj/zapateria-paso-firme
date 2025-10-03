param(
  [string]$Port = "8000",
  [switch]$Fresh,
  [string]$PhpExe = ""
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $root

function Resolve-Php {
  param([string]$Preferred)
  if ($Preferred) { return $Preferred }
  try { & php -v | Out-Null; return "php" } catch { }
  $xampp = "C:\xampp\php\php.exe"
  if (Test-Path $xampp) { return $xampp }
  throw "No se encontró PHP en PATH ni en C:\xampp\php\php.exe. Instala PHP o indica la ruta con -PhpExe."
}

$php = Resolve-Php -Preferred $PhpExe
Write-Host "Usando PHP: $php"

Write-Host "Ejecutando migraciones..."
if ($Fresh) { & $php "api\migrate.php" "--fresh" "--fix-ids" }
else { & $php "api\migrate.php" "--fix-ids" }

Write-Host "Levantando servidor en http://localhost:$Port ... (Ctrl+C para detener)"
& $php "-S" "localhost:$Port"
