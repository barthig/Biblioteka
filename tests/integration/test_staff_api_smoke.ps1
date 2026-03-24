$ErrorActionPreference = 'Stop'

$baseUrl = 'http://localhost:8000'
$headers = @{ 'x-api-secret' = 'dev_api_secret' }

$checks = @(
  @{ path = '/health'; expected = 200; auth = $false }
  @{ path = '/api/books/new?limit=4'; expected = 200; auth = $true }
  @{ path = '/api/announcements?limit=10'; expected = 200; auth = $true }
  @{ path = '/api/library/hours'; expected = 200; auth = $true }
  @{ path = '/api/statistics/dashboard'; expected = 200; auth = $true }
  @{ path = '/api/settings'; expected = 200; auth = $true }
  @{ path = '/api/loans'; expected = 200; auth = $true }
  @{ path = '/api/reservations?history=true&limit=100'; expected = 200; auth = $true }
  @{ path = '/api/fines?limit=50'; expected = 200; auth = $true }
  @{ path = '/api/collections'; expected = 200; auth = $true }
  @{ path = '/api/users'; expected = 200; auth = $true }
  @{ path = '/api/admin/system/settings'; expected = 200; auth = $true }
  @{ path = '/api/admin/system/integrations'; expected = 200; auth = $true }
  @{ path = '/api/admin/system/roles'; expected = 200; auth = $true }
  @{ path = '/api/audit-logs?limit=25'; expected = 200; auth = $true }
  @{ path = '/api/reports/usage'; expected = 200; auth = $true }
  @{ path = '/api/reports/financial'; expected = 200; auth = $true }
)

$results = foreach ($check in $checks) {
  try {
    $requestHeaders = if ($check.auth) { $headers } else { @{} }
    $response = Invoke-WebRequest -UseBasicParsing -Uri ($baseUrl + $check.path) -Headers $requestHeaders
    [PSCustomObject]@{
      Path = $check.path
      Status = [int]$response.StatusCode
      Expected = $check.expected
      Ok = ([int]$response.StatusCode -eq [int]$check.expected)
    }
  } catch {
    $statusCode = if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode.value__ } else { -1 }
    [PSCustomObject]@{
      Path = $check.path
      Status = $statusCode
      Expected = $check.expected
      Ok = ($statusCode -eq [int]$check.expected)
    }
  }
}

$results | Format-Table -AutoSize

$failed = $results | Where-Object { -not $_.Ok }
if ($failed) {
  throw "Smoke test failed for: $($failed.Path -join ', ')"
}
