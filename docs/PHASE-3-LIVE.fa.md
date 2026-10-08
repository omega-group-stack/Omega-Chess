# فاز سوم Omega Chess — Live Play Foundation

فاز سوم زیرساخت بازی زنده را برای اجرای Local اضافه می‌کند.

## قابلیت‌های این فاز

- Lobby
- Seek و matchmaking پایه
- اتصال دو کاربر به یک Game
- کنترل نوبت برای بازی‌های غیر Practice
- clock با initial time و increment
- timeout
- draw offer، accept و decline
- takeback request و accept
- rematch
- resign با winner صحیح
- polling برای reconnect و دریافت تغییرات بازی
- نسخه state برای جلوگیری از دریافت تکراری وضعیت

WebSocket واقعی و Redis Pub/Sub در فاز زیرساخت Production قرار می‌گیرد؛ در این فاز polling دوثانیه‌ای برای Local استفاده می‌شود تا بدون Redis هم قابل اجرا باشد.

## اجرا

```powershell
.\install.ps1
```

سپس در دو PowerShell:

```powershell
.\setup.ps1 api
```

```powershell
.\setup.ps1 web
```

آدرس‌ها:

```text
http://localhost:3000/play
http://localhost:3000/lobby
```

## APIهای جدید

```text
GET    /api/lobby
POST   /api/lobby/seeks
DELETE /api/lobby/seeks/{seek}
POST   /api/lobby/seeks/{seek}/join

GET  /api/games/{game}/events
POST /api/games/{game}/draw/offer
POST /api/games/{game}/draw/accept
POST /api/games/{game}/draw/decline
POST /api/games/{game}/takeback/request
POST /api/games/{game}/takeback/accept
POST /api/games/{game}/rematch
```

## Time control

برای ساخت seek پنج دقیقه‌ای بدون increment:

```json
{
  "mode": "casual",
  "color": "random",
  "initial_time_ms": 300000,
  "increment_ms": 0
}
```

## محدودیت باقی‌مانده

برای realtime Production هنوز باید WebSocket server، Redis Pub/Sub، reconnect قوی‌تر، rate limiting، notification و monitoring اضافه شوند.
