
$ErrorActionPreference = 'Stop'

$PhpExe   = 'C:\PHP\php.exe'                                  
$SitePath = Split-Path $PSScriptRoot -Parent                  
$Script   = Join-Path $SitePath 'scripts\warm-cache.php'
$TaskName = 'AllenhouseKhalasiLine-CacheWarmer'

if (!(Test-Path $PhpExe)) {
    $PhpExe = (Get-Command php -ErrorAction SilentlyContinue).Source
}
if (!(Test-Path $PhpExe)) { throw "php.exe not found - set `$PhpExe manually." }

# --quick = refresh only expired keys (cheap). Runs every minute so CMS edits
# (60s content TTL) surface fast; the lock file lets web requests skip their
# own background refresh entirely.
$action = New-ScheduledTaskAction -Execute $PhpExe -Argument "`"$Script`" --quick" -WorkingDirectory $SitePath
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration (New-TimeSpan -Days 3650)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5) -MultipleInstances IgnoreNew

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger `
    -Settings $settings -Description 'Warms allenhousekhalasiline CMS JSON cache every 1 min (--quick)' -Force

Write-Host "Registered task '$TaskName' -> $PhpExe `"$Script`" --quick every 1 min."
Write-Host 'Manual run: php scripts\warm-cache.php   (full) /  --quick (expired only)'
