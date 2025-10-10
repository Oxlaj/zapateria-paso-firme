param(
  [string]$HtmlPath = "c:\\Users\\VieryOxlaj\\Desktop\\pagina web\\docs\\diagrama_red_macro.html",
  [string]$OutPng = "c:\\Users\\VieryOxlaj\\Desktop\\pagina web\\docs\\diagrama_red_macro.png",
  [int]$Width = 2400,
  [int]$Height = 1600,
  [int]$VirtualTimeBudgetMs = 10000
)

$edgeCandidates = @()
if ($Env:ProgramFiles) {
  $edgeCandidates += (Join-Path -Path $Env:ProgramFiles -ChildPath 'Microsoft\Edge\Application\msedge.exe')
}
if ($Env:ProgramFiles -and (Test-Path (Join-Path -Path $Env:ProgramFiles -ChildPath 'Microsoft\Edge\Application\msedge.exe'))) {
  # already added
}
if ($Env:ProgramFiles -and (Test-Path (Join-Path -Path $Env:ProgramFiles -ChildPath 'Microsoft\Edge\Application\msedge.exe'))) {
  # noop
}
if ($Env:ProgramFiles -and (Test-Path (Join-Path -Path $Env:ProgramFiles -ChildPath 'Microsoft\Edge\Application\msedge.exe'))) {
  # noop
}
if (${Env:ProgramFiles(x86)}) {
  $edgeCandidates += (Join-Path -Path ${Env:ProgramFiles(x86)} -ChildPath 'Microsoft\Edge\Application\msedge.exe')
}

$edge = $edgeCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $edge) {
  Write-Error 'Microsoft Edge no está instalado. Instale Edge o renderice la imagen manualmente.'
  exit 1
}

if (-not (Test-Path $HtmlPath)) {
  Write-Error "No existe el HTML: $HtmlPath"
  exit 1
}

$uri = "file:///" + ($HtmlPath -replace '\\','/')
& "$edge" --headless --disable-gpu --screenshot="$OutPng" --virtual-time-budget=$VirtualTimeBudgetMs --window-size=$Width,$Height "$uri"

if (Test-Path $OutPng) {
  Write-Host "PNG generado: $OutPng"
  exit 0
} else {
  Write-Error 'No se generó el PNG. Verifique acceso a Internet para cargar Mermaid desde CDN.'
  exit 1
}