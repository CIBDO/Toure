# Build du frontend CANAM pour la production (Windows)
# À lancer depuis la racine du projet, après avoir défini l'URL de prod.
# Exemple: .\deploy\build-on-windows.ps1 -ApiBaseUrl "https://canam.votredomaine.ml/api"

param(
    [Parameter(Mandatory = $false)]
    [string]$ApiBaseUrl = "https://canam.votredomaine.ml/api"
)

$ErrorActionPreference = "Stop"
$projectRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $projectRoot

Write-Host "=== Build frontend CANAM (production) ===" -ForegroundColor Cyan
Write-Host "VITE_API_BASE_URL = $ApiBaseUrl" -ForegroundColor Gray

$env:VITE_API_BASE_URL = $ApiBaseUrl

Write-Host ">>> npm ci..." -ForegroundColor Yellow
npm ci
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host ">>> npm run build..." -ForegroundColor Yellow
npm run build
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "=== Build terminé ===" -ForegroundColor Green
Write-Host "Les fichiers sont dans public\build. Déployez le projet (y compris ce dossier) sur le serveur." -ForegroundColor Gray
