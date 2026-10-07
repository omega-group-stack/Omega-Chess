# Omega Chess — Phase 1

Omega Chess is being built as an independent, phased chess platform with an HTML/CSS/JavaScript frontend and a Laravel/PHP API.

Phase 1 is a local-first foundation:

- Next.js + TypeScript frontend running on Node.js
- Laravel 11 API running on PHP
- SQLite by default for zero-setup local development
- MySQL connection support for the next deployment step
- Sanctum token authentication: register, login, me, logout
- API health check
- Responsive Omega Chess visual system
- Interactive board preview
- PowerShell setup helper for Windows

The chess rules engine, durable games and multiplayer features are intentionally scheduled for later phases.

## Windows quick start

Install:

- Node.js 20+
- PHP 8.2+
- Composer 2+
- Git

The easiest option is to double-click `install.bat`.

Or open PowerShell in the repository root and run:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\install.ps1
```

The installer checks PHP, Composer and Node.js, prepares SQLite, installs all dependencies, and can start both local servers for you.

Start the API in PowerShell window one:

```powershell
.\setup.ps1 api
```

Start the web app in PowerShell window two:

```powershell
.\setup.ps1 web
```

Open:

- Web: <http://localhost:3000>
- Local game preview: <http://localhost:3000/play>
- API health through the web proxy: <http://localhost:3000/backend/health>
- Direct API health: <http://localhost:8000/api/health>

## Manual commands

API:

```powershell
cd apps\api
composer install
Copy-Item .env.example .env
New-Item -ItemType File database\database.sqlite
php artisan key:generate
php artisan migrate
php artisan serve --port=8000
```

Frontend, in another PowerShell window:

```powershell
cd apps\web
npm install
npm run dev
```

## API examples

Register:

```powershell
curl.exe -X POST http://localhost:8000/api/auth/register `
  -H "Content-Type: application/json" `
  -d '{"username":"player_one","email":"player@example.com","password":"correct-horse-battery-staple","password_confirmation":"correct-horse-battery-staple"}'
```

Login:

```powershell
curl.exe -X POST http://localhost:8000/api/auth/login `
  -H "Content-Type: application/json" `
  -d '{"email":"player@example.com","password":"correct-horse-battery-staple"}'
```

## Tests

```powershell
.\setup.ps1 test
```

Or manually:

```powershell
cd apps\api
php artisan test

cd ..\web
npm run typecheck
npm run build
```

## Phase roadmap

- Phase 1: local foundation, authentication, API health and board preview.
- Phase 2: independent chess domain, FEN, legal moves and durable games.
- Phase 3: multiplayer, matchmaking, clocks and realtime transport.
