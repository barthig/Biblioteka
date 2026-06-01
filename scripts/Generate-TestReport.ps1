param(
    [string]$OutputPath = "",
    [switch]$SkipBackend,
    [switch]$SkipFrontend,
    [switch]$SkipE2E,
    [switch]$SkipDocker,
    [switch]$SkipMicroservices
)

$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = Resolve-Path (Join-Path $scriptDir "..")
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

if ([string]::IsNullOrWhiteSpace($OutputPath)) {
    $OutputPath = Join-Path $repoRoot "test-results-$timestamp.txt"
}

$OutputPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($OutputPath)
$reportDir = Split-Path -Parent $OutputPath
if (-not (Test-Path -LiteralPath $reportDir)) {
    New-Item -ItemType Directory -Path $reportDir | Out-Null
}

$results = New-Object System.Collections.Generic.List[object]
$overallStart = Get-Date

function Write-ReportLine {
    param([string]$Text = "")
    Add-Content -LiteralPath $OutputPath -Value $Text -Encoding UTF8
}

function Format-Command {
    param(
        [string]$FilePath,
        [string[]]$Arguments
    )

    $parts = @($FilePath) + $Arguments
    return ($parts | ForEach-Object {
        if ($_ -match "\s") {
            '"' + ($_ -replace '"', '\"') + '"'
        } else {
            $_
        }
    }) -join " "
}

function Run-TestStep {
    param(
        [string]$Name,
        [string]$WorkingDirectory,
        [string]$FilePath,
        [string[]]$Arguments
    )

    $start = Get-Date
    $commandText = Format-Command -FilePath $FilePath -Arguments $Arguments

    Write-Host "==> $Name"
    Write-ReportLine ""
    Write-ReportLine "## $Name"
    Write-ReportLine "Start: $($start.ToString('s'))"
    Write-ReportLine "Working directory: $WorkingDirectory"
    Write-ReportLine "Command: $commandText"
    Write-ReportLine ""
    Write-ReportLine '```'

    Push-Location $WorkingDirectory
    try {
        $output = & $FilePath @Arguments 2>&1
        $exitCode = if ($null -eq $LASTEXITCODE) { 0 } else { $LASTEXITCODE }
    } catch {
        $output = @($_.Exception.ToString())
        $exitCode = 1
    } finally {
        Pop-Location
    }

    foreach ($line in $output) {
        Write-ReportLine ($line.ToString())
    }

    $end = Get-Date
    $duration = New-TimeSpan -Start $start -End $end
    $status = if ($exitCode -eq 0) { "PASS" } else { "FAIL" }

    Write-ReportLine '```'
    Write-ReportLine "Status: $status"
    Write-ReportLine "Exit code: $exitCode"
    Write-ReportLine ("Duration: {0:c}" -f $duration)

    $script:results.Add([pscustomobject]@{
        Name = $Name
        Status = $status
        ExitCode = $exitCode
        Duration = $duration
    }) | Out-Null
}

Set-Content -LiteralPath $OutputPath -Encoding UTF8 -Value @(
    "# Biblioteka test report",
    "Generated: $($overallStart.ToString('s'))",
    "Repository: $repoRoot",
    "Host: $env:COMPUTERNAME",
    "User: $env:USERNAME"
)

if (-not $SkipDocker) {
    Run-TestStep `
        -Name "Docker Compose config" `
        -WorkingDirectory $repoRoot `
        -FilePath "docker" `
        -Arguments @("compose", "--env-file", ".env.example", "config", "--quiet")
}

if (-not $SkipBackend) {
    Run-TestStep `
        -Name "Backend PHPUnit" `
        -WorkingDirectory (Join-Path $repoRoot "backend") `
        -FilePath "php" `
        -Arguments @("vendor\bin\phpunit")
}

if (-not $SkipFrontend) {
    Run-TestStep `
        -Name "Frontend Vitest" `
        -WorkingDirectory (Join-Path $repoRoot "frontend") `
        -FilePath "node" `
        -Arguments @("scripts\vitest-run.mjs", "run", "--reporter=dot")
}

if (-not $SkipFrontend -and -not $SkipE2E) {
    Run-TestStep `
        -Name "Frontend Playwright E2E" `
        -WorkingDirectory (Join-Path $repoRoot "frontend") `
        -FilePath "npx" `
        -Arguments @("playwright", "test", "--reporter=line")
}

if (-not $SkipMicroservices) {
    $dockerPythonCommand = "apt-get update >/dev/null && apt-get install -y --no-install-recommends libpq-dev gcc >/dev/null && pip install --no-cache-dir -r requirements.txt >/dev/null && python -m pytest -q"

    Run-TestStep `
        -Name "Notification service pytest (Docker)" `
        -WorkingDirectory $repoRoot `
        -FilePath "docker" `
        -Arguments @(
            "run",
            "--rm",
            "-e", "DEBIAN_FRONTEND=noninteractive",
            "-v", "${repoRoot}:/workspace",
            "-w", "/workspace/notification-service",
            "python:3.12-slim",
            "sh",
            "-c",
            $dockerPythonCommand
        )

    Run-TestStep `
        -Name "Recommendation service pytest (Docker)" `
        -WorkingDirectory $repoRoot `
        -FilePath "docker" `
        -Arguments @(
            "run",
            "--rm",
            "-e", "DEBIAN_FRONTEND=noninteractive",
            "-v", "${repoRoot}:/workspace",
            "-w", "/workspace/recommendation-service",
            "python:3.12-slim",
            "sh",
            "-c",
            $dockerPythonCommand
        )
}

$overallEnd = Get-Date
$overallDuration = New-TimeSpan -Start $overallStart -End $overallEnd
$failed = @($results | Where-Object { $_.ExitCode -ne 0 })

Write-ReportLine ""
Write-ReportLine "# Summary"
Write-ReportLine ""
Write-ReportLine "| Step | Status | Exit code | Duration |"
Write-ReportLine "| --- | --- | ---: | ---: |"
foreach ($result in $results) {
    Write-ReportLine ("| {0} | {1} | {2} | {3:c} |" -f $result.Name, $result.Status, $result.ExitCode, $result.Duration)
}
Write-ReportLine ""
Write-ReportLine "Total duration: $($overallDuration.ToString())"
Write-ReportLine "Failed steps: $($failed.Count)"

Write-Host ""
Write-Host "Report written to: $OutputPath"

if ($failed.Count -gt 0) {
    exit 1
}

exit 0
