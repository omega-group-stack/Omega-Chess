# فاز دوم Omega Chess — Chess Domain و Game API

فاز دوم روی فاز اول Local اضافه می‌شود و شامل بازی Practice با وضعیت معتبر سمت سرور است.

## قابلیت‌ها

- FEN parser و serializer
- تولید legal move
- جلوگیری از حرکت در وضعیت check
- check، checkmate و stalemate
- castling
- en passant
- promotion
- ذخیره بازی در SQLite
- ذخیره تاریخچه حرکت‌ها
- PGN export
- resign
- API server-authoritative
- صفحه `/play` برای حرکت روی برد واقعی

## اجرای Windows

اگر فاز اول نصب نشده است:

```powershell
.\install.ps1
```

اگر dependencyها قبلاً نصب شده‌اند، API و frontend را اجرا کنید:

```powershell
.\setup.ps1 api
```

در PowerShell دوم:

```powershell
.\setup.ps1 web
```

سپس:

```text
http://localhost:3000/play
```

## گرفتن token

ثبت کاربر:

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

مقدار `token` پاسخ را در صفحه `/play` وارد کنید.

## ساختار Domain

```text
apps/api/app/Domain/Chess/ChessGame.php
apps/api/app/Domain/Chess/ChessException.php
apps/api/app/Models/Game.php
apps/api/app/Models/GameMove.php
apps/api/app/Http/Controllers/GameController.php
```

## API حرکت

```json
{
  "from": "e2",
  "to": "e4",
  "promotion": null
}
```

برای promotion مقدار `promotion` یکی از این موارد است:

```text
q, r, b, n
```

## وضعیت فاز دوم

Realtime multiplayer، matchmaking، clock، WebSocket، reconnect و tournament در فاز سوم قرار دارند.
