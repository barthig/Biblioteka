# Biblioteka - Quick Start Script
# Uruchamia lokalnie backend + frontend, Docker tylko dla DB i RabbitMQ

param(
    [switch]$NoBrowser,
    [switch]$StopOnly
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$backendDir = Join-Path $repoRoot 'backend'
$frontendDir = Join-Path $repoRoot 'frontend'

Write-Host "`n=== Biblioteka - Local Development ===" -ForegroundColor Cyan

# Stop existing processes
if ($StopOnly -or $true) {
    Write-Host "`nZatrzymywanie istniejących procesów..." -ForegroundColor Yellow
    
    # Stop Docker containers
    Push-Location $repoRoot
    try {
        docker compose -f docker-compose.local.yml down 2>$null
        docker compose -f docker-compose.dev.yml down 2>$null
    } catch {
        Write-Warning "Nie można zatrzymać kontenerów Docker (może nie były uruchomione)"
    }
    Pop-Location
    
    # Stop PHP server
    Get-Process | Where-Object { $_.CommandLine -like '*php -S*8000*' } | Stop-Process -Force -ErrorAction SilentlyContinue
    
    # Stop npm dev
    Get-Process | Where-Object { $_.CommandLine -like '*vite*' -or $_.ProcessName -eq 'node' } | Stop-Process -Force -ErrorAction SilentlyContinue
    
    if ($StopOnly) {
        Write-Host "`nAplikacja zatrzymana.`n" -ForegroundColor Green
        return
    }
}

# Verify requirements
Write-Host "`nSprawdzanie wymagań..." -ForegroundColor Yellow

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw "Docker nie znaleziony. Zainstaluj Docker Desktop."
}

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw "PHP nie znalezione. Zainstaluj PHP 8.2+ i dodaj do PATH."
}

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    throw "npm nie znaleziony. Zainstaluj Node.js 20+."
}

try {
    docker ps | Out-Null
} catch {
    throw "Docker nie jest uruchomiony. Uruchom Docker Desktop."
}

Write-Host "OK: Docker dostepny" -ForegroundColor Green
Write-Host "OK: PHP $(php -v | Select-Object -First 1)" -ForegroundColor Green
Write-Host "OK: Node $(node -v)" -ForegroundColor Green

# Start Docker services (DB + RabbitMQ)
Write-Host "`nUruchamianie bazy danych i RabbitMQ..." -ForegroundColor Yellow
Push-Location $repoRoot
docker compose -f docker-compose.local.yml up -d
Pop-Location
if ($LASTEXITCODE -ne 0) {
    throw "Nie mozna uruchomic Docker (exit code: $LASTEXITCODE)"
}
Write-Host "OK: PostgreSQL uruchomiony (port 5432)" -ForegroundColor Green
Write-Host "OK: RabbitMQ uruchomiony (port 5672, panel: http://localhost:15672)" -ForegroundColor Green

# Install backend dependencies if needed
if (-not (Test-Path (Join-Path $backendDir 'vendor'))) {
    Write-Host "`nInstalowanie zależności backendu..." -ForegroundColor Yellow
    Push-Location $backendDir
    try {
        composer install --no-interaction
    } finally {
        Pop-Location
    }
}

# Install frontend dependencies if needed
if (-not (Test-Path (Join-Path $frontendDir 'node_modules'))) {
    Write-Host "`nInstalowanie zależności frontendu..." -ForegroundColor Yellow
    Push-Location $frontendDir
    try {
        npm install
    } finally {
        Pop-Location
    }
}

# Start Backend (PHP built-in server)
Write-Host "`nUruchamianie backendu..." -ForegroundColor Yellow
$backendCommand = "Set-Location '$backendDir'; Write-Host 'Backend uruchomiony na http://localhost:8000' -ForegroundColor Green; php -S localhost:8000 -t public"
Start-Process powershell -ArgumentList '-NoExit', '-Command', $backendCommand -WindowStyle Normal
Start-Sleep -Seconds 2
Write-Host "OK: Backend uruchomiony (http://localhost:8000)" -ForegroundColor Green

# Start Frontend (Vite)
Write-Host "`nUruchamianie frontendu..." -ForegroundColor Yellow
$frontendCommand = "Set-Location '$frontendDir'; Write-Host 'Frontend uruchomiony na http://localhost:5173' -ForegroundColor Green; npm run dev"
Start-Process powershell -ArgumentList '-NoExit', '-Command', $frontendCommand -WindowStyle Normal
Start-Sleep -Seconds 3
Write-Host "OK: Frontend uruchomiony (http://localhost:5173)" -ForegroundColor Green

Write-Host "`n=== Aplikacja uruchomiona ===" -ForegroundColor Cyan
Write-Host "Frontend:       http://localhost:5173" -ForegroundColor White
Write-Host "Backend API:    http://localhost:8000" -ForegroundColor White
Write-Host "RabbitMQ Panel: http://localhost:15672 (app/app)" -ForegroundColor White
Write-Host "`nAby zatrzymać wszystko: .\scripts\start-local.ps1 -StopOnly`n" -ForegroundColor Yellow

if (-not $NoBrowser) {
    Start-Sleep -Seconds 2
    Start-Process "http://localhost:5173"
}
