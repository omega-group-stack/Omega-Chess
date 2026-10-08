[CmdletBinding()]
param(
  [ValidateSet('install', 'start', 'api', 'web', 'realtime', 'test', 'help')]
  [string]$Command = 'install'
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$Api = Join-Path $Root 'apps\api'
$Web = Join-Path $Root 'apps\web'

function Require-Command([string]$Name) {
  if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
    throw "$Name was not found. Install the required Windows dependency first."
  }
}

function Install-Local {
  Require-Command 'php'
  Require-Command 'composer'
  Require-Command 'node'
  Require-Command 'npm'

  Push-Location $Api
  try {
    composer install
    if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env' }
    if (-not (Test-Path 'database\database.sqlite')) { New-Item -ItemType File 'database\database.sqlite' | Out-Null }
    php artisan key:generate
    php artisan migrate
  } finally { Pop-Location }

  Push-Location $Web
  try { npm install } finally { Pop-Location }
  Write-Host 'Local installation completed.' -ForegroundColor Green
}

function Start-Api {
  Push-Location $Api
  try { php artisan serve --host=127.0.0.1 --port=8000 } finally { Pop-Location }
}

function Start-Web {
  Push-Location $Web
  try { npm run dev -- --hostname 127.0.0.1 --port 3000 } finally { Pop-Location }
}

function Start-Realtime {
  Push-Location $Api
  try { php artisan reverb:start --host=127.0.0.1 --port=8080 } finally { Pop-Location }
}

switch ($Command) {
  'install' { Install-Local }
  'api' { Require-Command 'php'; Start-Api }
  'web' { Require-Command 'npm'; Start-Web }
  'realtime' { Require-Command 'php'; Start-Realtime }
  'start' {
    Write-Host 'Open three PowerShell windows and run:'
    Write-Host '  .\setup.ps1 api'
    Write-Host '  .\setup.ps1 web'
    Write-Host '  .\setup.ps1 realtime'
    Write-Host 'Then visit http://localhost:3000'
  }
  'test' {
    Require-Command 'php'; Require-Command 'npm'
    Push-Location $Api; try { php artisan test } finally { Pop-Location }
    Push-Location $Web; try { npm run typecheck; npm run build } finally { Pop-Location }
  }
  'help' { Get-Help $MyInvocation.MyCommand.Path -Detailed }
}
