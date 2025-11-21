[CmdletBinding()]
param(
    [switch]$BackendOnly,
    [switch]$FrontendOnly,
    [switch]$NoBrowser
)

$ErrorActionPreference = 'Stop'

if ($BackendOnly -and $FrontendOnly) {
    throw "Nie można jednocześnie użyć parametrów -BackendOnly i -FrontendOnly."
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$backendDir = Join-Path $repoRoot 'backend'
$frontendDir = Join-Path $repoRoot 'frontend'

function Assert-Command {
    param(
        [Parameter(Mandatory=$true)][string]$Name,
        [Parameter(Mandatory=$true)][string]$InstallHint
    )

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Nie znaleziono polecenia '$Name'. $InstallHint"
    }
}

function Invoke-InDirectory {
    param(
        [Parameter(Mandatory=$true)][string]$Path,
        [Parameter(Mandatory=$true)][scriptblock]$Script
    )

    Push-Location $Path
    try {
        & $Script
    }
    finally {
        Pop-Location
    }
}

function Start-DevProcess {
    param(
        [Parameter(Mandatory=$true)][string]$Title,
        [Parameter(Mandatory=$true)][string]$WorkingDirectory,
        [Parameter(Mandatory=$true)][string]$Command
    )

    $escapedDir = $WorkingDirectory.Replace("'", "''")
    $powershellCommand = "Set-Location '$escapedDir'; $Command"
    Start-Process powershell -ArgumentList '-NoExit', '-Command', $powershellCommand -WindowStyle Normal -WorkingDirectory $WorkingDirectory | Out-Null
    Write-Host "[$Title] wystartował." -ForegroundColor Green
}

$startBackend = -not $FrontendOnly
$startFrontend = -not $BackendOnly

if ($startBackend) {
    Assert-Command -Name 'php' -InstallHint 'Zainstaluj PHP 8.2 i upewnij się, że jest w PATH.'
    if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
        Write-Warning 'Nie znaleziono polecenia composer. Pomiń instalację zależności backendu lub dodaj composer do PATH.'
    }
    if (-not (Test-Path (Join-Path $backendDir 'public'))) {
        throw "Nie znaleziono katalogu 'public' w $backendDir."
    }
}

if ($startFrontend) {
    Assert-Command -Name 'npm' -InstallHint 'Zainstaluj Node.js (18+) wraz z npm.'
    if (-not (Test-Path (Join-Path $frontendDir 'package.json'))) {
        throw "Nie znaleziono pliku package.json w $frontendDir."
    }
}

if ($startBackend) {
    $vendorDir = Join-Path $backendDir 'vendor'
    if (-not (Test-Path $vendorDir) -and (Get-Command composer -ErrorAction SilentlyContinue)) {
        Write-Host 'Instaluję zależności backendu (composer install)...' -ForegroundColor Yellow
        Invoke-InDirectory -Path $backendDir -Script { composer install --no-interaction }
    }

    $backendCommand = 'php -S 127.0.0.1:8000 -t public public/index.php'
    Start-DevProcess -Title 'Backend (Symfony)' -WorkingDirectory $backendDir -Command $backendCommand
}

if ($startFrontend) {
    $frontendEnv = Join-Path $frontendDir '.env.local'
    if (-not (Test-Path $frontendEnv)) {
        $defaultEnv = "VITE_API_URL=http://127.0.0.1:8000/api`nVITE_API_SECRET=change_me"
        Set-Content -Path $frontendEnv -Value $defaultEnv -Encoding UTF8
        Write-Warning "Utworzono plik frontend/.env.local z wartościami domyślnymi. Zaktualizuj VITE_API_SECRET."
    }

    $nodeModules = Join-Path $frontendDir 'node_modules'
    if (-not (Test-Path $nodeModules)) {
        Write-Host 'Instaluję zależności frontendowe (npm install)...' -ForegroundColor Yellow
        Invoke-InDirectory -Path $frontendDir -Script { npm install }
    }

    $frontendCommand = 'npm run dev -- --host 127.0.0.1'
    Start-DevProcess -Title 'Frontend (Vite)' -WorkingDirectory $frontendDir -Command $frontendCommand
}

if (-not $NoBrowser -and $startFrontend) {
    Start-Process 'http://127.0.0.1:5173/' | Out-Null
}

Write-Host "Gotowe! Zamknij okna PowerShell, aby zatrzymać serwery." -ForegroundColor Cyan
