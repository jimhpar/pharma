# ==============================================================================
# Adorzotno All-In-One Launcher & Auto-Setup Script
# Portable runner for USB / Pendrive & new computer environments
# ==============================================================================

$RootDir = $PSScriptRoot
if (-not $RootDir) { $RootDir = (Get-Location).Path }

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "           Starting Adorzotno Full Stack...           " -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""

# ------------------------------------------------------------------------------
# 1. Automatic Environment Detection (Laragon, XAMPP, System PATH)
# ------------------------------------------------------------------------------
$driveLetters = @("C:", "D:", "E:", "F:")

# Detect PHP
$phpCmd = "php"
if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) {
    foreach ($drive in $driveLetters) {
        $laragonPhp = Join-Path $drive "laragon\bin\php"
        if (Test-Path $laragonPhp) {
            $foundDir = Get-ChildItem $laragonPhp -Directory -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
            if ($foundDir -and (Test-Path "$foundDir\php.exe")) {
                $phpCmd = "$foundDir\php.exe"
                $env:PATH = "$foundDir;$env:PATH"
                break
            }
        }
        $xamppPhp = Join-Path $drive "xampp\php\php.exe"
        if (Test-Path $xamppPhp) {
            $phpCmd = $xamppPhp
            $env:PATH = "$drive\xampp\php;$env:PATH"
            break
        }
    }
}

# Detect MySQL Client & Server
$mysqlCmd = "mysql"
$mysqldCmd = $null
$mysqlIni = $null

if (-not (Get-Command "mysql" -ErrorAction SilentlyContinue)) {
    foreach ($drive in $driveLetters) {
        $laragonMysql = Join-Path $drive "laragon\bin\mysql"
        if (Test-Path $laragonMysql) {
            $foundDir = Get-ChildItem $laragonMysql -Directory -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
            if ($foundDir) {
                if (Test-Path "$foundDir\bin\mysql.exe") {
                    $mysqlCmd = "$foundDir\bin\mysql.exe"
                    $env:PATH = "$foundDir\bin;$env:PATH"
                }
                if (Test-Path "$foundDir\bin\mysqld.exe") {
                    $mysqldCmd = "$foundDir\bin\mysqld.exe"
                    $mysqlIni = "$foundDir\my.ini"
                }
                break
            }
        }
        $xamppMysql = Join-Path $drive "xampp\mysql"
        if (Test-Path $xamppMysql) {
            if (Test-Path "$xamppMysql\bin\mysql.exe") {
                $mysqlCmd = "$xamppMysql\bin\mysql.exe"
                $env:PATH = "$xamppMysql\bin;$env:PATH"
            }
            if (Test-Path "$xamppMysql\bin\mysqld.exe") {
                $mysqldCmd = "$xamppMysql\bin\mysqld.exe"
                $mysqlIni = "$xamppMysql\bin\my.ini"
            }
            break
        }
    }
} else {
    # If mysql is in PATH, try finding mysqld too
    foreach ($drive in $driveLetters) {
        $laragonMysql = Join-Path $drive "laragon\bin\mysql"
        if (Test-Path $laragonMysql) {
            $foundDir = Get-ChildItem $laragonMysql -Directory -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
            if ($foundDir -and (Test-Path "$foundDir\bin\mysqld.exe")) {
                $mysqldCmd = "$foundDir\bin\mysqld.exe"
                $mysqlIni = "$foundDir\my.ini"
                break
            }
        }
    }
}

# Detect Node.js
if (-not (Get-Command "node" -ErrorAction SilentlyContinue)) {
    foreach ($drive in $driveLetters) {
        $nodePath = Join-Path $drive "Program Files\nodejs"
        if (Test-Path "$nodePath\node.exe") {
            $env:PATH = "$nodePath;$env:PATH"
            break
        }
    }
}

# Check minimum prerequisites
$hasPhp = (Get-Command $phpCmd -ErrorAction SilentlyContinue) -ne $null -or (Test-Path $phpCmd)
$hasNode = (Get-Command "node" -ErrorAction SilentlyContinue) -ne $null

if (-not $hasPhp -or -not $hasNode) {
    Write-Host "[WARNING] Required runtime tools not found!" -ForegroundColor Red
    Write-Host ""
    if (-not $hasPhp) { Write-Host " - PHP is missing. Please install Laragon (recommended) or PHP 8.2+" -ForegroundColor Yellow }
    if (-not $hasNode) { Write-Host " - Node.js is missing. Please install Node.js (LTS version) from https://nodejs.org" -ForegroundColor Yellow }
    Write-Host ""
    Write-Host "Recommendation: Install Laragon Full (https://laragon.org/download/) & Node.js, then run again." -ForegroundColor Cyan
    Write-Host ""
    Read-Host "Press Enter to exit..."
    Exit 1
}

# ------------------------------------------------------------------------------
# 2. Paths Configuration & .env Validation
# ------------------------------------------------------------------------------
$apiDir = Join-Path $RootDir "adorzotno-pos\ecommerce-api"
$posDir = Join-Path $RootDir "adorzotno-pos\pos"
$frontDir = Join-Path $RootDir "adorzotno"

# Ensure .env files exist
if (-not (Test-Path "$apiDir\.env") -and (Test-Path "$apiDir\.env.example")) {
    Copy-Item "$apiDir\.env.example" "$apiDir\.env" -Force
}
if (-not (Test-Path "$posDir\.env") -and (Test-Path "$posDir\.env.example")) {
    Copy-Item "$posDir\.env.example" "$posDir\.env" -Force
}
if (-not (Test-Path "$frontDir\.env.local") -and (Test-Path "$frontDir\.env.example")) {
    Copy-Item "$frontDir\.env.example" "$frontDir\.env.local" -Force
}

# Port check function
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

# ------------------------------------------------------------------------------
# 3. Start MySQL & Auto-Import Database If Needed
# ------------------------------------------------------------------------------
if (Test-PortOpen 3306) {
    Write-Host "[1/4] MySQL Database is running on port 3306." -ForegroundColor Green
} else {
    Write-Host "[1/4] Starting MySQL Database on port 3306..." -ForegroundColor Green
    
    # Try starting Windows service first
    $serviceStarted = $false
    $mysqlServices = @("MySQL", "mysql", "MySQL80", "MySQL84", "laragonMySQL")
    foreach ($svcName in $mysqlServices) {
        $svc = Get-Service -Name $svcName -ErrorAction SilentlyContinue
        if ($svc) {
            try {
                Start-Service -Name $svcName -ErrorAction SilentlyContinue
                $serviceStarted = $true
                break
            } catch {}
        }
    }

    # If service not found/started, launch mysqld process
    if (-not $serviceStarted -and $mysqldCmd -and (Test-Path $mysqldCmd)) {
        $args = "--console"
        if ($mysqlIni -and (Test-Path $mysqlIni)) {
            $args = "--defaults-file=`"$mysqlIni`" --console"
        }
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "Write-Host '--- MySQL Server (Port 3306) ---' -ForegroundColor Cyan; & '$mysqldCmd' $args" -WindowStyle Minimized
    }

    # Wait for MySQL to respond on port 3306
    $retries = 20
    while ($retries -gt 0 -and -not (Test-PortOpen 3306)) {
        Start-Sleep -Milliseconds 500
        $retries--
    }

    if (Test-PortOpen 3306) {
        Write-Host "      MySQL server is ready!" -ForegroundColor Green
    } else {
        Write-Host "      [NOTICE] MySQL port 3306 not detected automatically." -ForegroundColor Yellow
        Write-Host "      Please ensure MySQL is started via Laragon ('Start All') or XAMPP." -ForegroundColor Yellow
    }
}

# Auto-check and import database if missing or empty
if (Test-PortOpen 3306) {
    $dbName = "adorzotno"
    $dumpFile = Join-Path $RootDir "database\adorzotno_complete.sql"
    
    try {
        & $mysqlCmd -h 127.0.0.1 -u root -e "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>$null
        $tblCount = (& $mysqlCmd -h 127.0.0.1 -u root -N -s -e "SELECT count(*) FROM information_schema.tables WHERE table_schema = '$dbName';" 2>$null)
        
        if ([string]::IsNullOrWhiteSpace($tblCount) -or [int]$tblCount -eq 0) {
            if (Test-Path $dumpFile) {
                Write-Host "      Database '$dbName' is empty. Auto-importing complete data..." -ForegroundColor Yellow
                Get-Content -Path $dumpFile -Encoding UTF8 | & $mysqlCmd -h 127.0.0.1 -u root $dbName
                Write-Host "      Database auto-imported successfully!" -ForegroundColor Green
            }
        }
    } catch {
        # Silent continue if command fails
    }
}

# ------------------------------------------------------------------------------
# 4. Start Application Services
# ------------------------------------------------------------------------------

# 4.1 Ecommerce API (Port 8000)
if (Test-PortOpen 8000) {
    Write-Host "[2/4] Ecommerce API already running on port 8000" -ForegroundColor Yellow
} else {
    Write-Host "[2/4] Starting Ecommerce API on http://localhost:8000..." -ForegroundColor Green
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$apiDir'; Write-Host '--- Ecommerce API (Port 8000) ---' -ForegroundColor Cyan; & '$phpCmd' artisan serve --host=127.0.0.1 --port=8000" -WindowStyle Minimized
}

# 4.2 POS Admin (Port 8001)
if (Test-PortOpen 8001) {
    Write-Host "[3/4] POS Admin already running on port 8001" -ForegroundColor Yellow
} else {
    Write-Host "[3/4] Starting POS Admin on http://localhost:8001..." -ForegroundColor Green
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$posDir'; Write-Host '--- POS Admin (Port 8001) ---' -ForegroundColor Cyan; & '$phpCmd' artisan serve --host=127.0.0.1 --port=8001" -WindowStyle Minimized
}

# 4.3 Next.js Storefront (Port 3000)
if (Test-PortOpen 3000) {
    Write-Host "[4/4] Storefront frontend already running on port 3000" -ForegroundColor Yellow
} else {
    Write-Host "[4/4] Starting Storefront frontend on http://localhost:3000..." -ForegroundColor Green
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$frontDir'; Write-Host '--- Next.js Storefront (Port 3000) ---' -ForegroundColor Cyan; npm run dev" -WindowStyle Minimized
}

# ------------------------------------------------------------------------------
# 5. Readiness & Summary
# ------------------------------------------------------------------------------
Write-Host ""
Write-Host "Waiting for services to initialize..." -ForegroundColor Gray
Start-Sleep -Seconds 3

Write-Host ""
Write-Host "======================================================" -ForegroundColor Green
Write-Host "              All Services Are Running!               " -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
Write-Host ""
Write-Host " Access URLs:" -ForegroundColor White
Write-Host "  1. Storefront Website: http://localhost:3000" -ForegroundColor Cyan
Write-Host "  2. POS / Admin Panel:  http://localhost:8001" -ForegroundColor Cyan
Write-Host "  3. API Server:         http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host " Admin Credentials:" -ForegroundColor White
Write-Host "  Email:    admin@gmail.com" -ForegroundColor Gray
Write-Host "  Password: 12345678" -ForegroundColor Gray
Write-Host ""
Write-Host " To stop all servers, double click: stop.bat (or run .\stop.ps1)" -ForegroundColor Yellow
Write-Host ""

# Automatically open websites in default browser
Start-Process "http://localhost:8001"
Start-Process "http://localhost:3000"
