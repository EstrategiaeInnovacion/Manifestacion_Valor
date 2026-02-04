# Script para ver logs de eDocument en tiempo real
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  LOGS DE EDOCUMENT - ULTIMAS ENTRADAS" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

$logFile = Get-ChildItem "storage\logs" -Filter "laravel-*.log" | Sort-Object LastWriteTime -Descending | Select-Object -First 1

if ($logFile) {
    Write-Host "Archivo: $($logFile.Name)`n" -ForegroundColor Yellow
    
    # Leer las últimas 500 líneas y filtrar por EDOCUMENT
    Get-Content $logFile.FullName -Tail 500 | Select-String -Pattern "\[EDOCUMENT" | ForEach-Object {
        $line = $_.Line
        
        # Colorear según el tipo de log
        if ($line -match "===") {
            Write-Host $line -ForegroundColor Magenta
        }
        elseif ($line -match "ERROR|error|Error") {
            Write-Host $line -ForegroundColor Red
        }
        elseif ($line -match "WARNING|warning|Warning") {
            Write-Host $line -ForegroundColor Yellow
        }
        elseif ($line -match "INFO|info") {
            Write-Host $line -ForegroundColor Green
        }
        else {
            Write-Host $line
        }
    }
    
    Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "  FIN DE LOGS" -ForegroundColor Cyan
    Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
} else {
    Write-Host "No se encontraron archivos de log." -ForegroundColor Red
}
