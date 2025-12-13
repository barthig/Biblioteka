#!/usr/bin/env pwsh
<#!
.SYNOPSIS
Starts the Biblioteka stack via Docker Compose.

.DESCRIPTION
Selects the appropriate compose file (dev or prod), validates Docker
prerequisites, and prints useful service URLs once the stack is running.

.EXAMPLE
./scripts/start-app.ps1            # start development stack
./scripts/start-app.ps1 -Mode prod # start production stack
#>

[CmdletBinding()]
param(
    [ValidateSet('dev','prod','production')]
    [string]$Mode = 'dev',
    [switch]$SkipHealth
)

$ErrorActionPreference = 'Stop'
$rootDir = Resolve-Path (Join-Path $PSScriptRoot '..')

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Error '[ERROR] Docker is required. Please install Docker Desktop or Docker Engine.'
    exit 1
}

try {
    docker compose version *> $null
} catch {
    Write-Error '[ERROR] Docker Compose v2 (docker compose) is required.'
    exit 1
}

switch ($Mode.ToLowerInvariant()) {
    'dev' {
        $composeFile = Join-Path $rootDir 'docker-compose.dev.yml'
        $frontendUrl = 'http://localhost:5173'
        $backendUrl  = 'http://localhost:8000'
        $stackName   = 'development'
    }
    'prod' { goto production }
    'production' { :production
        $composeFile = Join-Path $rootDir 'docker-compose.yml'
        $frontendUrl = 'http://localhost:3000'
        $backendUrl  = 'http://localhost:8000'
        $stackName   = 'production'
    }
    default {
        Write-Error "[ERROR] Unsupported mode '$Mode'. Use 'dev' (default) or 'prod'."
        exit 1
    }
}

if (-not (Test-Path $composeFile)) {
    Write-Error "[ERROR] Compose file not found: $composeFile"
    exit 1
}

Write-Host "[INFO] Starting Biblioteka stack ($stackName) using $composeFile..."
docker compose -f $composeFile up -d --build

if (-not $SkipHealth) {
    Write-Host '[INFO] Waiting for containers to report healthy...'
    docker compose -f $composeFile ps
}

Write-Host "`n[READY] Biblioteka is running:"
Write-Host " - Backend API:    $backendUrl"
Write-Host " - Frontend:       $frontendUrl"
Write-Host " - RabbitMQ panel: http://localhost:15672 (if enabled in this stack)"

Write-Host "Use 'docker compose -f $composeFile logs -f' to stream logs or 'docker compose -f $composeFile down' to stop."
