# Omega Stoch Hidden Divergence EA (MetaTrader 5)

اکسپرت فارکس برای متاتریدر ۵ – **مرحله ۱: تنظیم اندیکاتورها و تشخیص هیدن دایورجنس**

## فایل‌ها

| مسیر | توضیح |
|---|---|
| `Experts/OmegaStochHiddenDivergence.mq5` | سورس اکسپرت |

## نصب

1. در متاتریدر ۵: `File → Open Data Folder`
2. فایل `OmegaStochHiddenDivergence.mq5` را در `MQL5/Experts/` کپی کنید.
3. در MetaEditor فایل را باز و `Compile` (F7) کنید.
4. اکسپرت را روی چارت بیندازید (نیاز به AutoTrading نیست؛ هنوز معامله‌ای انجام نمی‌دهد).

## اندیکاتورها

| اندیکاتور | تنظیمات پیش‌فرض |
|---|---|
| Stochastic #1 | %K=12, %D=3, Slowing=3 |
| Stochastic #2 | %K=26, %D=3, Slowing=3 |
| Moving Average | دوره 60، پیش‌فرض **EMA** (قابل تغییر به SMA/SMMA/LWMA) |

با فعال بودن `InpShowIndicators` هر سه اندیکاتور به‌صورت خودکار روی چارت نمایش داده می‌شوند.

## منطق هیدن دایورجنس

پیوت‌های اسیلاتور با `PivotLeft` کندل سمت چپ و `PivotRight` کندل سمت راست تأیید می‌شوند
و هر پیوت فقط با **نزدیک‌ترین پیوت قبلی** (در بازه `MinBarsBetween` تا `MaxBarsBetween`) مقایسه می‌شود.

| نوع | قیمت | استوکاستیک | رنگ خط (Stoch1 / Stoch2 / Both) |
|---|---|---|---|
| Hidden Bullish | کفِ بالاتر (HL) | کفِ پایین‌تر (LL) | Lime / DodgerBlue / Green |
| Hidden Bearish | سقفِ پایین‌تر (LH) | سقفِ بالاتر (HH) | Red / Orange / Magenta |

## ورودی‌های مهم

| ورودی | پیش‌فرض | توضیح |
|---|---|---|
| `InpDivMode` | `DIV_EITHER` | `EITHER`: هر استوکاستیک جداگانه خط می‌کشد · `BOTH`: فقط وقتی هر دو تأیید کنند · `STOCH1_ONLY` / `STOCH2_ONLY` |
| `InpStochLine` | `%K` | خط اصلی یا سیگنال برای دایورجنس |
| `InpPriceSource` | `High/Low` | مقایسه قیمت با High/Low یا Close |
| `InpUseMAFilter` | `false` | اگر فعال شود: Bullish فقط بالای MA60 و Bearish فقط زیر MA60 |
| `InpUseZones` | `false` | فقط پیوت‌های داخل نواحی اشباع (20/80) |
| `InpBothTolerance` | `2` | در حالت BOTH، حداکثر فاصله‌ی دو پیوت استوکاستیک‌ها (کندل) |
| `InpDrawTrendLines` | `false` | رسم خط اتصال دو پیوت روی قیمت برای بررسی بصری |
| `InpLookbackBars` | `1000` | تعداد کندل تاریخچه برای اسکن اولیه |
| `InpAlertPopup / Push / Sound` | | هشدار برای سیگنال‌های زنده |

## مرحله‌های بعدی (پیشنهادی)

- [ ] منطق ورود معامله (Buy روی Hidden Bullish / Sell روی Hidden Bearish)
- [ ] فیلتر روند با MA60 به‌صورت پیش‌فرض + شیب MA
- [ ] حد ضرر/حد سود (ATR یا پیوت قبلی)، تریلینگ استاپ
- [ ] مدیریت سرمایه (درصد ریسک)
- [ ] فیلتر زمان/سشن و اسپرد
