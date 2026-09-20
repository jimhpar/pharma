# ==============================================================================
# Adorzotno All-In-One Runner Script
# Launches Ecommerce API (8000), POS Admin (8001), and Next.js Storefront (3000)
# ==============================================================================

$RootDir = $PSScriptRoot
if (-not $RootDir) { $RootDir = (Get-Location).Path }

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "           Starting Adorzotno Full Stack...           " -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""

# Helper to locate PHP and Node
$phpCmd = "php"
if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) {
    if (Test-Path "C:\laragon\bin\php") {
        $phpDir = Get-ChildItem "C:\laragon\bin\php" -Directory | Select-Object -First 1 -ExpandProperty FullName
        if ($phpDir) { $phpCmd = "$phpDir\php.exe" }
    }
}

$apiDir = Join-Path $RootDir "adorzotno-pos\ecommerce-api"
$posDir = Join-Path $RootDir "adorzotno-pos\pos"
$frontDir = Join-Path $RootDir "adorzotno"

# Function to check port listening
function Test-PortOpen([int]$port) {
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $client.Connect("127.0.0.1", $port)
        $client.Close()
        return $true
    } catch {
        return $false
    }
}

# 1. Start Ecommerce API on Port 8000
if (Test-PortOpen 8000) {
    Write-Host "[1/3] Ecommerce API already running on port 8000" -ForegroundColor Yellow
} else {
    Write-Host "[1/3] Starting Ecommerce API on http://localhost:8000..." -ForegroundColor Green
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$apiDir'; Write-Host '--- Ecommerce API (Port 8000) ---' -ForegroundColor Cyan; & '$phpCmd' artisan serve --host=127.0.0.1 --port=8000" -WindowStyle Minimized
}

# 2. Start POS Backend on Port 8001
if (Test-PortOpen 8001) {
    Write-Host "[2/3] POS Admin already running on port 8001" -ForegroundColor Yellow
} else {
    Write-Host "[2/3] Starting POS Admin on http://localhost:8001..." -ForegroundColor Green
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$posDir'; Write-Host '--- POS Admin (Port 8001) ---' -ForegroundColor Cyan; & '$phpCmd' artisan serve --host=127.0.0.1 --port=8001" -WindowStyle Minimized
}

# 3. Start Next.js Frontend on Port 3000
if (Test-PortOpen 3000) {
    Write-Host "[3/3] Storefront frontend already running on port 3000" -ForegroundColor Yellow
} else {
    Write-Host "[3/3] Starting Storefront frontend on http://localhost:3000..." -ForegroundColor Green
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$frontDir'; Write-Host '--- Next.js Storefront (Port 3000) ---' -ForegroundColor Cyan; npm run dev" -WindowStyle Minimized
}

Write-Host ""
Write-Host "Waiting for services to become ready..." -ForegroundColor Gray
Start-Sleep -Seconds 3

Write-Host ""
Write-Host "======================================================" -ForegroundColor Green
Write-Host "              All Services Are Running!               " -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
Write-Host ""
Write-Host " URLs:" -ForegroundColor White
Write-Host "  Storefront Website: http://localhost:3000" -ForegroundColor Cyan
Write-Host "  POS / Admin Panel:  http://localhost:8001" -ForegroundColor Cyan
Write-Host "  API Server:         http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host " Admin Credentials:" -ForegroundColor White
Write-Host "  Email:    admin@gmail.com" -ForegroundColor Gray
Write-Host "  Password: 12345678" -ForegroundColor Gray
Write-Host ""
Write-Host "To stop all servers, run: .\stop.ps1" -ForegroundColor Yellow
Write-Host ""

# Open storefront in default browser
Start-Process "http://localhost:3000"
