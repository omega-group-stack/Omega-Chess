# راهنمای اجرای فاز اول Omega Chess روی Windows

فاز اول برای اجرای Local روی Windows طراحی شده و به VPS یا Docker نیاز ندارد.

## پیش‌نیازها

- Node.js 20 یا جدیدتر
- PHP 8.2 یا جدیدتر
- Composer 2
- Git

برای نصب آسان PHP روی Windows می‌توانید از Laragon استفاده کنید. بعد از نصب، PowerShell را باز کنید و بررسی کنید:

```powershell
node --version
npm --version
php --version
composer --version
```

## نصب با یک کلیک

در Windows روی فایل زیر دوبار کلیک کنید:

```text
install.bat
```

یا در PowerShell ریشه پروژه اجرا کنید:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\install.ps1
```

نصب‌کننده PHP، Composer و Node.js را بررسی می‌کند، SQLite را می‌سازد، وابستگی‌ها را نصب می‌کند، Migrationها را اجرا می‌کند و در پایان برای اجرای API و frontend از شما سؤال می‌کند.

اگر فقط نصب می‌خواهید و نمی‌خواهید سرورها اجرا شوند:

```powershell
.\install.ps1 -NoStart
```

## نصب دستی

اگر نمی‌خواهید از installer استفاده کنید:

```powershell
.\setup.ps1 install
```

## اجرای پروژه

در PowerShell اول:

```powershell
.\setup.ps1 api
```

در PowerShell دوم:

```powershell
.\setup.ps1 web
```

سپس مرورگر:

```text
http://localhost:3000
```

صفحه board preview:

```text
http://localhost:3000/play
```

Health API:

```text
http://localhost:3000/backend/health
```

## دیتابیس Local

فاز اول به‌صورت پیش‌فرض از SQLite استفاده می‌کند:

```text
apps/api/database/database.sqlite
```

تنظیمات آن در `apps/api/.env` قرار دارد:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

برای استفاده از MySQL در آینده، فقط `DB_CONNECTION` و اطلاعات اتصال را در `.env` تغییر می‌دهیم.

## API احراز هویت

ثبت‌نام:

```powershell
curl.exe -X POST http://localhost:8000/api/auth/register `
  -H "Content-Type: application/json" `
  -d '{"username":"player_one","email":"player@example.com","password":"correct-horse-battery-staple","password_confirmation":"correct-horse-battery-staple"}'
```

ورود:

```powershell
curl.exe -X POST http://localhost:8000/api/auth/login `
  -H "Content-Type: application/json" `
  -d '{"email":"player@example.com","password":"correct-horse-battery-staple"}'
```

## تست

```powershell
.\setup.ps1 test
```

این دستور تست Laravel، typecheck و build مربوط به Next.js را اجرا می‌کند.

## محدودیت فاز اول

در این فاز هنوز موارد زیر وجود ندارد:

- Chess engine کامل
- FEN و PGN
- بازی ذخیره‌شده
- multiplayer
- WebSocket
- matchmaking
- clock

این موارد در فازهای بعدی اضافه می‌شوند.
