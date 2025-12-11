[CmdletBinding()]
param(
    [switch]$BackendOnly,
    [switch]$FrontendOnly,
    [switch]$NoBrowser,
    [switch]$UseLocalPHP
)

$ErrorActionPreference = 'Stop'

if ($BackendOnly -and $FrontendOnly) {
    throw "Cannot use -BackendOnly and -FrontendOnly at the same time."
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
        throw "Command '$Name' not found. $InstallHint"
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
    Write-Host "[$Title] started." -ForegroundColor Green
}

$startBackend = -not $FrontendOnly
$startFrontend = -not $BackendOnly

if ($startBackend) {
    if ($UseLocalPHP) {
        Assert-Command -Name 'php' -InstallHint 'Install PHP 8.2 and ensure it is on PATH.'
        if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
            Write-Warning 'composer command not found. Install it or add it to PATH to install backend dependencies.'
        }
        if (-not (Test-Path (Join-Path $backendDir 'public'))) {
            throw "Missing 'public' directory in $backendDir."
        }
    } else {
        Assert-Command -Name 'docker' -InstallHint 'Install Docker Desktop and ensure it is running.'
        try {
            docker ps | Out-Null
        } catch {
            throw "Docker is not running. Start Docker Desktop and try again."
        }
    }
}

if ($startFrontend) {
    Assert-Command -Name 'npm' -InstallHint 'Install Node.js (18+) with npm.'
    if (-not (Test-Path (Join-Path $frontendDir 'package.json'))) {
        throw "package.json not found in $frontendDir."
    }
}

if ($startBackend) {
    if ($UseLocalPHP) {
        $vendorDir = Join-Path $backendDir 'vendor'
        if (-not (Test-Path $vendorDir) -and (Get-Command composer -ErrorAction SilentlyContinue)) {
            Write-Host 'Installing backend dependencies (composer install)...' -ForegroundColor Yellow
            Invoke-InDirectory -Path $backendDir -Script { composer install --no-interaction }
        }

        $portCheck = Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue
        if ($portCheck) {
            throw "Port 8000 is already in use. Stop the process that is using it or start the backend via Docker (omit -UseLocalPHP)."
        }

        $backendCommand = 'php -S 127.0.0.1:8000 -t public public/index.php'
        Start-DevProcess -Title 'Backend (Symfony - Local PHP)' -WorkingDirectory $backendDir -Command $backendCommand
    } else {
        Write-Host 'Checking Docker containers...' -ForegroundColor Yellow
        $dockerCompose = Join-Path $repoRoot 'docker-compose.yml'
        if (-not (Test-Path $dockerCompose)) {
            throw "docker-compose.yml not found in the repository root."
        }

        Write-Host 'Starting Docker containers (docker compose up -d)...' -ForegroundColor Yellow
        Invoke-InDirectory -Path $repoRoot -Script { docker compose up -d }
        Write-Host 'Waiting for the backend to be ready...' -ForegroundColor Yellow
        Start-Sleep -Seconds 3

        $backendStatus = docker ps --filter "name=biblioteka-1-backend-1" --format "{{.Status}}"
        if ($backendStatus -match "Up") {
            Write-Host '[Backend (Docker)] is running at http://localhost:8000' -ForegroundColor Green
        } else {
            Write-Warning 'Backend Docker may not be healthy yet. Check docker logs biblioteka-1-backend-1'
        }
    }
}

if ($startFrontend) {
    $frontendEnv = Join-Path $frontendDir '.env.local'
    $backendEnv = Join-Path $backendDir '.env.local'
    $apiSecret = 'change_me_api'
    if (Test-Path $backendEnv) {
        $backendEnvContent = Get-Content $backendEnv -Raw
        if ($backendEnvContent -match 'API_SECRET=(.+)') {
            $apiSecret = $matches[1].Trim()
        }
    }

    if (-not (Test-Path $frontendEnv)) {
        $defaultEnv = "VITE_API_URL=http://localhost:8000`nVITE_API_SECRET=$apiSecret"
        Set-Content -Path $frontendEnv -Value $defaultEnv -Encoding UTF8
        Write-Host "Created frontend/.env.local with the API secret pulled from the backend." -ForegroundColor Green
    } else {
        $envContent = Get-Content $frontendEnv -Raw
        $needsUpdate = $false
        if ($envContent -match 'VITE_API_URL=http://127\.0\.0\.1:8000/api') {
            $envContent = $envContent -replace 'VITE_API_URL=http://127\.0\.0\.1:8000/api', 'VITE_API_URL=http://localhost:8000'
            $needsUpdate = $true
        }
        if ($envContent -notmatch 'VITE_API_URL=') {
            $envContent = "VITE_API_URL=http://localhost:8000`n" + $envContent
            $needsUpdate = $true
        }
        if ($envContent -match 'VITE_API_SECRET=change_me$') {
            $envContent = $envContent -replace 'VITE_API_SECRET=change_me$', "VITE_API_SECRET=$apiSecret"
            $needsUpdate = $true
        }
        if ($needsUpdate) {
            Set-Content -Path $frontendEnv -Value $envContent -Encoding UTF8 -NoNewline
            Write-Host "Updated frontend/.env.local" -ForegroundColor Yellow
        }
    }

    $nodeModules = Join-Path $frontendDir 'node_modules'
    if (-not (Test-Path $nodeModules)) {
        Write-Host 'Installing frontend dependencies (npm install)...' -ForegroundColor Yellow
        Invoke-InDirectory -Path $frontendDir -Script { npm install }
    }

    $frontendCommand = 'npm run dev'
    Start-DevProcess -Title 'Frontend (Vite)' -WorkingDirectory $frontendDir -Command $frontendCommand
    Start-Sleep -Seconds 2
    Write-Host '[Frontend (Vite)] Check the PowerShell window for the dev server URL (typically http://localhost:5173 or 5174).' -ForegroundColor Green
}

if (-not $NoBrowser -and $startFrontend) {
    Start-Sleep -Seconds 3
    Start-Process 'http://localhost:5173/' | Out-Null
}

Write-Host "Ready! Close the PowerShell windows to stop the servers." -ForegroundColor Cyan
