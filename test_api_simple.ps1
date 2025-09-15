# Simple API Test Script for PowerShell
# Tests key endpoints to ensure they're working

Write-Host "API Testing Script for VTU and SMS Services" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host ""

$baseUrl = "http://localhost:8000/api"
$testResults = @()

function Test-Endpoint {
    param(
        [string]$Method,
        [string]$Endpoint,
        [string]$Description,
        [hashtable]$Data = $null,
        [hashtable]$Headers = @{}
    )
    
    $url = "$baseUrl$Endpoint"
    $status = "❌"
    $message = ""
    
    try {
        $requestParams = @{
            Uri = $url
            Method = $Method
            UseBasicParsing = $true
            TimeoutSec = 30
        }
        
        if ($Headers.Count -gt 0) {
            $requestParams.Headers = $Headers
        }
        
        if ($Data -and ($Method -eq "POST" -or $Method -eq "PUT" -or $Method -eq "PATCH")) {
            $jsonData = $Data | ConvertTo-Json -Depth 10
            $requestParams.Body = $jsonData
            $requestParams.ContentType = "application/json"
        }
        
        $response = Invoke-WebRequest @requestParams
        $statusCode = $response.StatusCode
        
        if ($statusCode -ge 200 -and $statusCode -lt 300) {
            $status = "✅"
        } elseif ($statusCode -ge 400 -and $statusCode -lt 500) {
            $status = "⚠️"
        }
        
        $responseData = $response.Content | ConvertFrom-Json -ErrorAction SilentlyContinue
        if ($responseData) {
            if ($responseData.message) {
                $message = $responseData.message
            }
            if ($responseData.success -ne $null) {
                $message += " (Success: $($responseData.success))"
            }
        }
        
    } catch {
        $status = "❌"
        $message = $_.Exception.Message
        $statusCode = 0
    }
    
    Write-Host "$status $Description" -ForegroundColor $(if ($status -eq "✅") { "Green" } elseif ($status -eq "⚠️") { "Yellow" } else { "Red" })
    Write-Host "   $Method $Endpoint" -ForegroundColor Gray
    Write-Host "   HTTP $statusCode" -ForegroundColor Gray
    if ($message) {
        Write-Host "   Message: $message" -ForegroundColor Gray
    }
    Write-Host ""
    
    $testResults += @{
        Endpoint = "$Method $Endpoint"
        Description = $Description
        Status = $status
        StatusCode = $statusCode
        Message = $message
    }
}

# Test public endpoints
Write-Host "Testing Public Endpoints..." -ForegroundColor Cyan
Write-Host "==============================" -ForegroundColor Cyan
Write-Host ""

Test-Endpoint -Method "GET" -Endpoint "/test" -Description "API Test Endpoint"
Test-Endpoint -Method "GET" -Endpoint "/cors-test" -Description "CORS Test"
Test-Endpoint -Method "GET" -Endpoint "/health/quick" -Description "Quick Health Check"

# Test VTU endpoints
Test-Endpoint -Method "GET" -Endpoint "/vtu/services" -Description "VTU Services List"
Test-Endpoint -Method "GET" -Endpoint "/vtu/airtime/networks" -Description "Airtime Networks"
Test-Endpoint -Method "GET" -Endpoint "/vtu/data/networks" -Description "Data Networks"
Test-Endpoint -Method "GET" -Endpoint "/vtu/variations/data?network=mtn" -Description "MTN Data Bundles"
Test-Endpoint -Method "GET" -Endpoint "/betting/providers" -Description "Betting Providers"
Test-Endpoint -Method "GET" -Endpoint "/electricity/providers" -Description "Electricity Providers"

# Test SMS endpoints
Test-Endpoint -Method "GET" -Endpoint "/sms/providers" -Description "SMS Providers"
Test-Endpoint -Method "GET" -Endpoint "/sms/countries" -Description "SMS Countries"
Test-Endpoint -Method "GET" -Endpoint "/sms/services?country=187" -Description "SMS Services for USA"

# Test authentication
Write-Host "Testing Authentication..." -ForegroundColor Cyan
Write-Host "============================" -ForegroundColor Cyan
Write-Host ""

$loginData = @{
    email = "test@example.com"
    password = "password123"
}

$loginResult = Test-Endpoint -Method "POST" -Endpoint "/login" -Description "User Login" -Data $loginData

# Test health endpoints
Write-Host "Testing Health Check Endpoints..." -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan
Write-Host ""

Test-Endpoint -Method "GET" -Endpoint "/health" -Description "Full Health Check"
Test-Endpoint -Method "GET" -Endpoint "/health/endpoints" -Description "API Endpoints List"

# Summary
Write-Host "API Testing Complete!" -ForegroundColor Green
Write-Host "========================" -ForegroundColor Green
Write-Host ""

$totalTests = $testResults.Count
$successfulTests = ($testResults | Where-Object { $_.Status -eq "✅" }).Count
$warningTests = ($testResults | Where-Object { $_.Status -eq "⚠️" }).Count
$failedTests = ($testResults | Where-Object { $_.Status -eq "❌" }).Count

Write-Host "Summary:" -ForegroundColor Yellow
Write-Host "Total Tests: $totalTests" -ForegroundColor White
Write-Host "Successful: $successfulTests" -ForegroundColor Green
Write-Host "Warnings: $warningTests" -ForegroundColor Yellow
Write-Host "Failed: $failedTests" -ForegroundColor Red
Write-Host "Success Rate: $([math]::Round(($successfulTests / $totalTests) * 100, 2))%" -ForegroundColor White
Write-Host ""

if ($failedTests -gt 0) {
    Write-Host "❌ Failed Tests:" -ForegroundColor Red
    $testResults | Where-Object { $_.Status -eq "❌" } | ForEach-Object {
        Write-Host "- $($_.Description) ($($_.Endpoint))" -ForegroundColor Red
        if ($_.Message) {
            Write-Host "  Error: $($_.Message)" -ForegroundColor Gray
        }
    }
    Write-Host ""
}

Write-Host "Notes:" -ForegroundColor Yellow
Write-Host "- Some endpoints may return warnings due to insufficient balance (expected)" -ForegroundColor White
Write-Host "- Check the health endpoint for detailed service status" -ForegroundColor White
Write-Host "- All endpoints should return proper JSON responses" -ForegroundColor White
Write-Host "- Frontend can use these endpoints with proper error handling" -ForegroundColor White
Write-Host ""

Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Check your .env file for proper API keys" -ForegroundColor White
Write-Host "2. Ensure database is properly configured" -ForegroundColor White
Write-Host "3. Test with real API credentials" -ForegroundColor White
Write-Host "4. Monitor the health endpoint for service status" -ForegroundColor White
