# ==============================================================================
# Adorzotno Complete Project Setup Script
# Automatically configures Database, Backends, Frontend & Dependencies
# ==============================================================================

$ErrorActionPreference = "Stop"

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "       Adorzotno E-Commerce & POS Automated Setup     " -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""

$RootDir = $PSScriptRoot
if (-not $RootDir) { $RootDir = (Get-Location).Path }

# 1. Detect Laragon / Local tools if not in PATH
if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) {
    if (Test-Path "C:\laragon\bin\php") {
        $phpDir = Get-ChildItem "C:\laragon\bin\php" -Directory | Select-Object -First 1 -ExpandProperty FullName
        if ($phpDir) {
            $env:PATH = "$phpDir;$env:PATH"
            Write-Host "[INFO] Detected Laragon PHP: $phpDir" -ForegroundColor Yellow
        }
    }
}

if (-not (Get-Command "mysql" -ErrorAction SilentlyContinue)) {
    if (Test-Path "C:\laragon\bin\mysql") {
        $mysqlDir = Get-ChildItem "C:\laragon\bin\mysql" -Directory | Select-Object -First 1 -ExpandProperty FullName
        if ($mysqlDir) {
            $env:PATH = "$mysqlDir\bin;$env:PATH"
            Write-Host "[INFO] Detected Laragon MySQL: $mysqlDir\bin" -ForegroundColor Yellow
        }
    }
}

if (-not (Get-Command "composer" -ErrorAction SilentlyContinue)) {
    if (Test-Path "C:\laragon\bin\composer\composer.bat") {
        $env:PATH = "C:\laragon\bin\composer;$env:PATH"
    }
}

# 2. Check Prerequisites
Write-Host "[1/5] Checking Prerequisites..." -ForegroundColor Green
$missingTools = @()

if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) { $missingTools += "PHP (v8.2+)" }
if (-not (Get-Command "composer" -ErrorAction SilentlyContinue)) { $missingTools += "Composer" }
if (-not (Get-Command "node" -ErrorAction SilentlyContinue)) { $missingTools += "Node.js (v18+)" }
if (-not (Get-Command "mysql" -ErrorAction SilentlyContinue)) { $missingTools += "MySQL" }

if ($missingTools.Count -gt 0) {
    Write-Host "[ERROR] Missing required tools:" -ForegroundColor Red
    $missingTools | ForEach-Object { Write-Host " - $_" -ForegroundColor Red }
    Write-Host "Please install them (e.g. using Laragon or standalone installers) and rerun this script." -ForegroundColor Yellow
    Exit 1
}

Write-Host " - PHP: " (php -r "echo PHP_VERSION;") -ForegroundColor Gray
Write-Host " - Node.js: " (node -v) -ForegroundColor Gray
Write-Host " - Composer: " (composer --version | Select-Object -First 1) -ForegroundColor Gray
Write-Host " All prerequisites satisfied!" -ForegroundColor Green
Write-Host ""

# 3. Database Setup & Import
Write-Host "[2/5] Setting up MySQL Database (adorzotno)..." -ForegroundColor Green
$dbHost = "127.0.0.1"
$dbUser = "root"
$dbPass = ""
$dbName = "adorzotno"

$sqlDumpPath = Join-Path $RootDir "database\adorzotno_complete.sql"
if (-not (Test-Path $sqlDumpPath)) {
    $fallbackPath = Join-Path $RootDir "adorzotno-pos\pos\database\schema\adorzotno.sql"
    if (Test-Path $fallbackPath) { $sqlDumpPath = $fallbackPath }
}

$mysqlAuth = "-h $dbHost -u $dbUser"
if ($dbPass) { $mysqlAuth += " -p$dbPass" }

try {
    # Test connection and create database
    & mysql.exe -h $dbHost -u $dbUser -e "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    Write-Host " Database '$dbName' ready." -ForegroundColor Gray

    # Check if database already has tables
    $tableCount = (& mysql.exe -h $dbHost -u $dbUser -N -s -e "SELECT count(*) FROM information_schema.tables WHERE table_schema = '$dbName';")
    if ([int]$tableCount -eq 0 -and (Test-Path $sqlDumpPath)) {
        Write-Host " Importing complete database dump ($sqlDumpPath)..." -ForegroundColor Yellow
        Get-Content -Path $sqlDumpPath -Encoding UTF8 | & mysql.exe -h $dbHost -u $dbUser $dbName
        Write-Host " Database imported successfully with all products & stock!" -ForegroundColor Green
    } else {
        Write-Host " Database '$dbName' already contains $tableCount tables. Skipping dump overwrite." -ForegroundColor Gray
    }
} catch {
    Write-Host "[WARNING] MySQL connection/import issue: $_" -ForegroundColor Yellow
    Write-Host "Please ensure MySQL is running on port 3306." -ForegroundColor Yellow
}
Write-Host ""

# 4. Ecommerce API Setup (Port 8000)
Write-Host "[3/5] Setting up Ecommerce API (Port 8000)..." -ForegroundColor Green
$apiDir = Join-Path $RootDir "adorzotno-pos\ecommerce-api"
Push-Location $apiDir
try {
    if (-not (Test-Path ".env")) {
        Copy-Item ".env.example" ".env"
        Write-Host " Created ecommerce-api .env" -ForegroundColor Gray
    }
    Write-Host " Installing Composer dependencies..." -ForegroundColor Gray
    composer install --no-interaction --quiet
    php artisan key:generate --force
    Write-Host " Ecommerce API successfully configured!" -ForegroundColor Green
} finally {
    Pop-Location
}
Write-Host ""

# 5. POS Backend Setup (Port 8001)
Write-Host "[4/5] Setting up POS Backend & Admin (Port 8001)..." -ForegroundColor Green
$posDir = Join-Path $RootDir "adorzotno-pos\pos"
Push-Location $posDir
try {
    if (-not (Test-Path ".env")) {
        Copy-Item ".env.example" ".env"
        Write-Host " Created pos .env" -ForegroundColor Gray
    }
    Write-Host " Installing Composer dependencies..." -ForegroundColor Gray
    composer install --no-interaction --quiet
    php artisan key:generate --force
    php artisan storage:link 2>$null

    Write-Host " Installing & Building POS assets (npm run build)..." -ForegroundColor Gray
    npm install --silent
    npm run build --silent
    Write-Host " POS Backend successfully configured!" -ForegroundColor Green
} finally {
    Pop-Location
}
Write-Host ""

# 6. Next.js Frontend Setup (Port 3000)
Write-Host "[5/5] Setting up Storefront Frontend (Port 3000)..." -ForegroundColor Green
$frontDir = Join-Path $RootDir "adorzotno"
Push-Location $frontDir
try {
    if (-not (Test-Path ".env.local")) {
        Copy-Item ".env.example" ".env.local"
        Write-Host " Created frontend .env.local" -ForegroundColor Gray
    }
    Write-Host " Installing npm packages..." -ForegroundColor Gray
    npm install --silent
    Write-Host " Storefront Frontend successfully configured!" -ForegroundColor Green
} finally {
    Pop-Location
}
Write-Host ""

# 7. Finished
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "           Setup Completed Successfully!              " -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "To start all applications simultaneously, run:" -ForegroundColor White
Write-Host "  .\run.ps1" -ForegroundColor Yellow
Write-Host "Or double-click: start.bat" -ForegroundColor Yellow
Write-Host ""
Write-Host "Access Points:" -ForegroundColor White
Write-Host " - Storefront:       http://localhost:3000" -ForegroundColor Cyan
Write-Host " - POS / Admin:      http://localhost:8001" -ForegroundColor Cyan
Write-Host " - API Documentation:http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "Default Admin Credentials:" -ForegroundColor White
Write-Host " - Email:    admin@gmail.com" -ForegroundColor Gray
Write-Host " - Password: 12345678" -ForegroundColor Gray
Write-Host ""
