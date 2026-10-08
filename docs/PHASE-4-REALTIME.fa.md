# فاز چهارم Omega Chess — Production Realtime

فاز چهارم انتقال live play از polling به WebSocket واقعی را شروع می‌کند.

## قابلیت‌های این فاز

- Laravel Reverb WebSocket server
- Broadcasting با Pusher protocol
- Laravel Echo و pusher-js در frontend
- Private channel برای هر بازی
- event `game.updated`
- احراز دسترسی کانال بر اساس بازیکنان بازی
- fallback polling در صورت قطع WebSocket
- اجرای Reverb روی پورت `8080` در Local
- نگه‌داشتن عملکرد بازی حتی اگر WebSocket موقتاً در دسترس نباشد

## نصب

بعد از دریافت آخرین نسخه:

```powershell
git pull --ff-only origin arena/01a10c3f-omega-chess
.\install.ps1
```

Installer اکنون سه پنجره PowerShell باز می‌کند:

```text
Laravel API: 8000
Next.js:     3000
Reverb:      8080
```

اگر دستی اجرا می‌کنید:

```powershell
.\setup.ps1 api
```

```powershell
.\setup.ps1 realtime
```

```powershell
.\setup.ps1 web
```

## تنظیمات Reverb

در `apps/api/.env`:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=omega-local
REVERB_APP_KEY=omega-local
REVERB_APP_SECRET=omega-local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

Frontend به‌صورت خودکار به channel زیر متصل می‌شود:

```text
private-games.{game_id}
```

هر تغییر بازی event زیر را منتشر می‌کند:

```text
game.updated
```

## بررسی

در دو browser یا tab، صفحه بازی را باز کنید و دو token مجاز برای همان game داشته باشید. حرکت، draw، takeback و پایان بازی باید بدون refresh کامل در وضعیت جدید ظاهر شوند.

اگر Reverb اجرا نشود، polling به‌عنوان fallback هر چهار ثانیه وضعیت را بررسی می‌کند.

## باقی‌مانده Production

برای آماده‌سازی کامل Production هنوز باید این موارد اضافه شوند:

- SSL/WSS واقعی
- Redis برای scaling چند process
- queue worker
- rate limiting دقیق‌تر
- monitoring و alerting
- deployment automation
- تست End-to-End چندبازیکنه
