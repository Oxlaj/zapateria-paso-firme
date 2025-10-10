# Genera un documento Word (.docx) a partir de un Markdown básico.
# Requiere Microsoft Word instalado para salida .docx; si no está, genera un .rtf de respaldo.

param(
  [string]$MarkdownPath = "c:\\Users\\VieryOxlaj\\Desktop\\pagina web\\docs\\proyecto_red_volkswagen_guatemala.md",
  [string]$DocxPath = "c:\\Users\\VieryOxlaj\\Desktop\\pagina web\\docs\\proyecto_red_volkswagen_guatemala.docx",
  [string]$RtfPath = "c:\\Users\\VieryOxlaj\\Desktop\\pagina web\\docs\\proyecto_red_volkswagen_guatemala.rtf",
  [string]$DiagramPngPath = "c:\\Users\\VieryOxlaj\\Desktop\\pagina web\\docs\\diagrama_red_macro.png"
)

function Convert-MarkdownToWord {
  param(
    [string]$Content,
    [object]$WordApp,
    [object]$Document,
    [string]$DiagramPngPath
  )

  $lines = $Content -split "\r?\n"
  $inCode = $false
  $lastWasMermaidFence = $false

  foreach ($line in $lines) {
    if ($line.Trim().StartsWith('```')) {
      # Toggle bloque de código y detectar si es mermaid
      if (-not $inCode) {
        # Apertura
        if ($line -match '^```\s*mermaid') { $lastWasMermaidFence = $true } else { $lastWasMermaidFence = $false }
        $inCode = $true
        continue
      } else {
        # Cierre
        if ($lastWasMermaidFence -and (Test-Path $DiagramPngPath)) {
          $range = $Document.Paragraphs.Add().Range
          $null = $range.InlineShapes.AddPicture($DiagramPngPath)
          $range.InsertParagraphAfter()
        }
        $inCode = $false
        $lastWasMermaidFence = $false
        continue
      }
    }

    $p = $Document.Paragraphs.Add()

    if ($inCode) {
      # Texto preformateado (código/mermaid)
      $p.Range.Text = $line + "`r"
      $p.Range.Font.Name = 'Consolas'
      $p.Range.Font.Size = 9
      continue
    }

    if ($line -match "^#\s+(.*)") {
      $p.Range.Text = $Matches[1] + "`r"
      $p.Range.Font.Bold = $true
      $p.Range.Font.Size = 18
    }
    elseif ($line -match "^##\s+(.*)") {
      $p.Range.Text = $Matches[1] + "`r"
      $p.Range.Font.Bold = $true
      $p.Range.Font.Size = 16
    }
    elseif ($line -match "^###\s+(.*)") {
      $p.Range.Text = $Matches[1] + "`r"
      $p.Range.Font.Bold = $true
      $p.Range.Font.Size = 14
    }
    elseif ($line -match "^\s*[-*]\s+(.*)") {
      # Lista simple
      $p.Range.Text = $Matches[1]
      $p.Range.ListFormat.ApplyBulletDefault()
      $p.Range.InsertParagraphAfter()
    }
    elseif ($line -match "^\|.*\|") {
      # Línea de tabla -> insertar como texto monoespaciado para preservar
      $p.Range.Text = $line + "`r"
      $p.Range.Font.Name = 'Consolas'
      $p.Range.Font.Size = 9
    }
    else {
      $p.Range.Text = $line + "`r"
      $p.Range.Font.Size = 11
    }
  }
}

# Cargar contenido del Markdown
if (-not (Test-Path -Path $MarkdownPath)) {
  Write-Error "No se encontró el archivo Markdown en: $MarkdownPath"
  exit 1
}

$md = Get-Content -Path $MarkdownPath -Raw -Encoding UTF8

# Asegurar carpeta de salida
$outDir = Split-Path -Path $DocxPath -Parent
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

# Intentar con Word (DOCX)
$word = $null
try {
  $word = New-Object -ComObject Word.Application -ErrorAction Stop
  $word.Visible = $false
  $doc = $word.Documents.Add()

  Convert-MarkdownToWord -Content $md -WordApp $word -Document $doc -DiagramPngPath $DiagramPngPath

  $wdFormatXMLDocument = 12
  $doc.SaveAs([ref]$DocxPath, [ref]$wdFormatXMLDocument)
  $doc.Close()
  $word.Quit()

  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($doc)
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($word)

  Write-Host "Documento Word generado: $DocxPath"
  exit 0
}
catch {
  if ($word -ne $null) {
    try { $word.Quit() } catch {}
    [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($word)
  }
  Write-Warning "No se pudo automatizar Word (.docx). Se generará un RTF de respaldo. Detalle: $($_.Exception.Message)"
}

# Fallback: Generar RTF básico compatible con Word
try {
  $sb = New-Object -TypeName System.Text.StringBuilder
  [void]$sb.Append("{\\rtf1\\ansi\\deff0{\\fonttbl{\\f0 Calibri;}{\\f1 Consolas;}}\n")

  $lines = $md -split "\r?\n"
  $inCode = $false
  foreach ($line in $lines) {
    if ($line.Trim().StartsWith('```')) { $inCode = -not $inCode; continue }

    $escaped = ($line -replace '\\', '\\\\') -replace '{','\\{' -replace '}','\\}'

    if ($inCode) {
      [void]$sb.Append("{\\f1 "+$escaped+"}\\line\n")
      continue
    }

    if ($line -match "^#\s+(.*)") {
      [void]$sb.Append("\\b "+$Matches[1]+"\\b0\\line\n")
    }
    elseif ($line -match "^##\s+(.*)") {
      [void]$sb.Append("\\b "+$Matches[1]+"\\b0\\line\n")
    }
    elseif ($line -match "^###\s+(.*)") {
      [void]$sb.Append("\\b "+$Matches[1]+"\\b0\\line\n")
    }
    elseif ($line -match "^\s*[-*]\s+(.*)") {
      [void]$sb.Append("• "+$Matches[1]+"\\line\n")
    }
    else {
      [void]$sb.Append($escaped+"\\line\n")
    }
  }
  [void]$sb.Append("}\n")
  Set-Content -Path $RtfPath -Value $sb.ToString() -Encoding ASCII
  Write-Host "Documento RTF generado: $RtfPath"
  exit 0
}
catch {
  Write-Error "Fallo al generar RTF: $($_.Exception.Message)"
  exit 1
}