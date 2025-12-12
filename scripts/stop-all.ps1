# Zatrzymuje wszystkie usługi Biblioteka

$ErrorActionPreference = 'Continue'
$repoRoot = Split-Path -Parent $PSScriptRoot

Write-Host "`nZatrzymywanie aplikacji Biblioteka..." -ForegroundColor Yellow

# Stop all Docker containers
Push-Location $repoRoot
docker compose -f docker-compose.local.yml down 2>$null
docker compose -f docker-compose.dev.yml down 2>$null
docker compose down 2>$null
Pop-Location

# Stop PHP servers
Get-Process | Where-Object { $_.CommandLine -like '*php -S*8000*' } | Stop-Process -Force -ErrorAction SilentlyContinue

# Stop Node/Vite
Get-Process | Where-Object { $_.CommandLine -like '*vite*' -or ($_.ProcessName -eq 'node' -and $_.CommandLine -like '*biblioteka*') } | Stop-Process -Force -ErrorAction SilentlyContinue

Write-Host "OK: Wszystkie uslugi zatrzymane`n" -ForegroundColor Green
