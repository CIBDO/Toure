# Lance le scheduler Laravel (equivalent cron : * * * * * php artisan schedule:run)
Set-Location $PSScriptRoot\..

while ($true) {
    php artisan schedule:run --no-interaction 2>&1 | Out-Null
    Start-Sleep -Seconds 60
}
