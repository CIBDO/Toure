# Installe une tache planifiee Windows pour le scheduler Laravel (toutes les minutes)
$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$php = (Get-Command php -ErrorAction SilentlyContinue).Source

if (-not $php) {
    Write-Error "PHP introuvable dans le PATH. Installez PHP ou ajustez ce script."
    exit 1
}

$taskName = 'CONTRAT-Laravel-Scheduler'
$action = New-ScheduledTaskAction -Execute $php -Argument "artisan schedule:run" -WorkingDirectory $projectRoot
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Force | Out-Null

Write-Host "Tache planifiee '$taskName' installee (schedule:run chaque minute)."
Write-Host "Verifier : Get-ScheduledTask -TaskName '$taskName'"
