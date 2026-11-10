<#
.SYNOPSIS
    Respaldo de Salud UNEFA: base de datos (pg_dump comprimido) y adjuntos clínicos.

.DESCRIPTION
    Ejecutar desde cualquier carpeta con los contenedores en marcha:
        powershell -ExecutionPolicy Bypass -File scripts\backup.ps1
    Cada respaldo queda en backups\AAAAMMDD-HHMMSS\ con db.dump y adjuntos.tgz.
    Copie la carpeta fuera del servidor: contiene datos clínicos confidenciales.

.PARAMETER BackupDir
    Carpeta de destino (por defecto .\backups en la raíz del repositorio).

.PARAMETER Keep
    Cantidad de respaldos que se conservan (por defecto 14; 0 = no borrar ninguno).

.PARAMETER ComposeOptions
    Opciones adicionales para docker compose (p. ej. '-p otro-proyecto -f otro.yml').
#>
param(
    [string]$BackupDir = '',
    [ValidateRange(0, 10000)][int]$Keep = 14,
    [string]$ComposeOptions = ''
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root
if (-not $BackupDir) { $BackupDir = Join-Path $root 'backups' }

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$target = Join-Path $BackupDir $stamp
New-Item -ItemType Directory -Force -Path $target | Out-Null

# Copia binaria de la salida estándar de un comando (PowerShell 5.1 corrompe binarios con ">").
function Save-CommandOutput([string]$Arguments, [string]$Destination) {
    $info = New-Object System.Diagnostics.ProcessStartInfo
    $info.FileName = 'docker'
    $info.Arguments = $Arguments
    $info.WorkingDirectory = $root
    $info.UseShellExecute = $false
    $info.RedirectStandardOutput = $true
    $info.RedirectStandardError = $true
    $process = [System.Diagnostics.Process]::Start($info)
    $errorTask = $process.StandardError.ReadToEndAsync()
    $file = [System.IO.File]::Create($Destination)
    try { $process.StandardOutput.BaseStream.CopyTo($file) } finally { $file.Dispose() }
    $process.WaitForExit()
    if ($process.ExitCode -ne 0) { throw "El comando 'docker $Arguments' falló: $($errorTask.Result)" }
}

try {
    Write-Host "Respaldando la base de datos en $target\db.dump ..."
    Save-CommandOutput ("compose $ComposeOptions " + 'exec -T db sh -c "pg_dump -U \"$POSTGRES_USER\" -d \"$POSTGRES_DB\" -Fc"') (Join-Path $target 'db.dump')
    if ((Get-Item (Join-Path $target 'db.dump')).Length -eq 0) { throw 'El volcado de la base de datos está vacío.' }

    Write-Host "Respaldando los adjuntos en $target\adjuntos.tgz ..."
    Save-CommandOutput "compose $ComposeOptions exec -T web tar -C /var/www/storage -czf - attachments" (Join-Path $target 'adjuntos.tgz')
} catch {
    Remove-Item -Recurse -Force -Path $target -ErrorAction SilentlyContinue
    Write-Error "Error: no se completó el respaldo. ¿Están en marcha los contenedores? $($_.Exception.Message)"
    exit 1
}

# Rotación: conserva solo los $Keep respaldos más recientes.
if ($Keep -gt 0) {
    Get-ChildItem -Path $BackupDir -Directory |
        Where-Object { $_.Name -match '^\d{8}-\d{6}$' } |
        Sort-Object Name -Descending |
        Select-Object -Skip $Keep |
        ForEach-Object {
            Write-Host "Eliminando respaldo antiguo: $($_.FullName)"
            Remove-Item -Recurse -Force -Path $_.FullName
        }
}

Write-Host "Respaldo completado: $target"
