# export-png.ps1
# Tải PlantUML JAR (nếu chưa có) và convert tất cả .puml -> .png

$diagramDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$jarPath    = "$diagramDir\plantuml.jar"
$jarUrl     = "https://github.com/plantuml/plantuml/releases/download/v1.2024.6/plantuml-1.2024.6.jar"

# Tải JAR nếu chưa có
if (-not (Test-Path $jarPath)) {
    Write-Host "Dang tai plantuml.jar..." -ForegroundColor Cyan
    Invoke-WebRequest -Uri $jarUrl -OutFile $jarPath -UseBasicParsing
    Write-Host "Tai xong: $jarPath" -ForegroundColor Green
} else {
    Write-Host "plantuml.jar da co san." -ForegroundColor Gray
}

# Convert tất cả .puml -> .png
$pumlFiles = Get-ChildItem -Path $diagramDir -Filter "*.puml"

if ($pumlFiles.Count -eq 0) {
    Write-Host "Khong tim thay file .puml nao." -ForegroundColor Yellow
    exit
}

Write-Host "`nDang convert $($pumlFiles.Count) file..." -ForegroundColor Cyan

foreach ($file in $pumlFiles) {
    Write-Host "  -> $($file.Name)" -NoNewline
    $result = & java -jar $jarPath -tpng -charset UTF-8 $file.FullName 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  [OK]" -ForegroundColor Green
    } else {
        Write-Host "  [LOI] $result" -ForegroundColor Red
    }
}

Write-Host "`nHoan tat! Cac file PNG duoc luu cung thu muc voi file .puml." -ForegroundColor Green
Write-Host "Thu muc: $diagramDir" -ForegroundColor Gray
