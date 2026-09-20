# ==============================================================================
# Stop All Running Adorzotno Services (Ports 8000, 8001, 3000)
# ==============================================================================

Write-Host "Stopping Adorzotno services..." -ForegroundColor Yellow

$ports = @(8000, 8001, 3000)

foreach ($port in $ports) {
    try {
        $netstat = Get-NetTCPConnection -LocalPort $port -ErrorAction SilentlyContinue
        if ($netstat) {
            $pids = $netstat.OwningProcess | Select-Object -Unique
            foreach ($p in $pids) {
                if ($p -gt 4) {
                    Stop-Process -Id $p -Force -ErrorAction SilentlyContinue
                    Write-Host " Stopped process on port $port (PID: $p)" -ForegroundColor Green
                }
            }
        } else {
            Write-Host " Port $port is already free." -ForegroundColor Gray
        }
    } catch {
        Write-Host " Error checking port $port : $_" -ForegroundColor Red
    }
}

Write-Host "All Adorzotno services have been stopped." -ForegroundColor Cyan
