[CmdletBinding()]
param(
  [switch]$NoStart
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$Api = Join-Path $Root 'apps\api'
$Web = Join-Path $Root 'apps\web'
$Setup = Join-Path $Root 'setup.ps1'

function Stop-WithMessage([string]$Message) {
  Write-Host "`n[Omega Chess] $Message" -ForegroundColor Red
  Write-Host 'Install the missing dependency, open a new PowerShell window, and run install.ps1 again.' -ForegroundColor Yellow
  exit 1
}

function Add-DiscoveredToolToPath([string]$Name, [string[]]$Patterns) {
  if (Get-Command $Name -ErrorAction SilentlyContinue) { return }

  foreach ($Pattern in $Patterns) {
    $Candidate = Get-ChildItem -Path $Pattern -File -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($Candidate) {
      $Directory = Split-Path $Candidate.FullName -Parent
      $env:Path = "$Directory;$env:Path"
      Write-Host "Detected $Name in $Directory" -ForegroundColor DarkGreen
      return
    }
  }
}

function Require-Command([string]$Name, [string]$InstallHint) {
  if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
    Stop-WithMessage "$Name was not found. $InstallHint"
  }
}

Add-DiscoveredToolToPath 'php' @(
  'C:\laragon\bin\php\*\php.exe',
  'C:\tools\php\php.exe',
  "$env:ProgramFiles\PHP\*\php.exe"
)
Add-DiscoveredToolToPath 'composer' @(
  'C:\laragon\bin\composer\composer.bat',
  "$env:APPDATA\ComposerSetup\bin\composer.bat",
  "$env:ProgramData\ComposerSetup\bin\composer.bat"
)
Add-DiscoveredToolToPath 'node' @(
  'C:\laragon\bin\nodejs\*\node.exe',
  "$env:ProgramFiles\nodejs\node.exe"
)

Write-Host '========================================' -ForegroundColor Green
Write-Host '        Omega Chess Local Installer' -ForegroundColor Green
Write-Host '========================================' -ForegroundColor Green
Write-Host "Project: $Root`n"

if (-not (Test-Path $Setup)) { Stop-WithMessage 'setup.ps1 was not found. Run this script from the project root.' }
if (-not (Test-Path (Join-Path $Api 'composer.json'))) { Stop-WithMessage 'Laravel API files are missing.' }
if (-not (Test-Path (Join-Path $Web 'package.json'))) { Stop-WithMessage 'Next.js files are missing.' }

Require-Command 'php' 'Install PHP 8.2+ or use Laragon: https://laragon.org/download/'
Require-Command 'composer' 'Install Composer: https://getcomposer.org/download/'
Require-Command 'node' 'Install Node.js 20+: https://nodejs.org/'
Require-Command 'npm' 'npm is installed together with Node.js.'

$phpVersion = php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;'
Write-Host "PHP $phpVersion detected" -ForegroundColor DarkGreen
Write-Host "Node $(node --version) detected" -ForegroundColor DarkGreen
Write-Host "Composer $(composer --version --no-ansi) detected" -ForegroundColor DarkGreen

Write-Host "`nInstalling project dependencies and preparing SQLite..." -ForegroundColor Cyan
& $Setup install
if ($LASTEXITCODE -ne 0) { throw 'The local project setup failed.' }

Write-Host "`nInstallation completed successfully." -ForegroundColor Green
Write-Host 'Frontend: http://localhost:3000'
Write-Host 'API:      http://localhost:8000'
Write-Host 'Reverb:   ws://127.0.0.1:8080'
Write-Host 'Health:   http://localhost:8000/api/health'

if ($NoStart) {
  Write-Host "`nServers were not started because -NoStart was used." -ForegroundColor Yellow
  exit 0
}

$start = Read-Host "`nStart the API and frontend now? [Y/n]"
if ($start -and $start.Trim().ToLower() -ne 'y' -and $start.Trim() -ne '') {
  Write-Host 'Installation finished. Start later with .\setup.ps1 api, .\setup.ps1 realtime and .\setup.ps1 web.' -ForegroundColor Yellow
  exit 0
}

Write-Host "`nStarting API, Reverb and frontend in three new PowerShell windows..." -ForegroundColor Cyan
Start-Process powershell.exe -WorkingDirectory $Api -ArgumentList @('-NoExit', '-Command', 'php artisan serve --host=127.0.0.1 --port=8000')
Start-Process powershell.exe -WorkingDirectory $Api -ArgumentList @('-NoExit', '-Command', 'php artisan reverb:start --host=127.0.0.1 --port=8080')
Start-Process powershell.exe -WorkingDirectory $Web -ArgumentList @('-NoExit', '-Command', 'npm run dev -- --hostname 127.0.0.1 --port 3000')
Start-Sleep -Seconds 3
Start-Process 'http://localhost:3000'
Write-Host "`nOmega Chess is starting at http://localhost:3000" -ForegroundColor Green
Write-Host 'Close the two PowerShell windows to stop the local servers.' -ForegroundColor Yellow
