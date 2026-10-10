//+------------------------------------------------------------------+
//|                                  OmegaStochHiddenDivergence.mq5  |
//|                     Omega Group - Forex Expert Advisor (MT5)     |
//|                                                                  |
//|  مرحله ۱: تنظیم اندیکاتورها و تشخیص هیدن دایورجنس                 |
//|                                                                  |
//|  اندیکاتورها:                                                     |
//|     - Stochastic (12,3,3)                                        |
//|     - Stochastic (26,3,3)                                        |
//|     - Moving Average 60 (پیش‌فرض EMA – قابل تغییر)                |
//|                                                                  |
//|  منطق فعلی:                                                       |
//|     - پیدا کردن سقف/کف‌های اسیلاتور (Pivot)                        |
//|     - مقایسه با پیوت قبلی برای تشخیص هیدن دایورجنس                 |
//|         * Hidden Bullish : قیمت کفِ بالاتر  +  استوکاستیک کفِ پایین‌تر |
//|         * Hidden Bearish : قیمت سقفِ پایین‌تر + استوکاستیک سقفِ بالاتر |
//|     - رسم خط عمودی روی کندل دایورجنس                              |
//|                                                                  |
//|  معاملات هنوز فعال نیست (مرحله بعد).                              |
//+------------------------------------------------------------------+
#property copyright "Omega Group"
#property link      "https://github.com/omega-group-stack"
#property version   "1.00"
#property description "Stochastic(12,3,3) + Stochastic(26,3,3) + MA60 - Hidden Divergence detector"

//+------------------------------------------------------------------+
//| Enumerations                                                     |
//+------------------------------------------------------------------+
enum ENUM_DIV_MODE
  {
   DIV_EITHER      = 0,  // هر کدام از استوکاستیک‌ها (جداگانه)
   DIV_BOTH        = 1,  // فقط وقتی هر دو استوکاستیک تأیید کنند
   DIV_STOCH1_ONLY = 2,  // فقط استوکاستیک 12,3,3
   DIV_STOCH2_ONLY = 3   // فقط استوکاستیک 26,3,3
  };

enum ENUM_STOCH_LINE_SEL
  {
   STOCH_USE_MAIN   = 0, // خط اصلی %K
   STOCH_USE_SIGNAL = 1  // خط سیگنال %D
  };

enum ENUM_PRICE_SRC
  {
   PRICE_SRC_HIGHLOW = 0, // High/Low کندل
   PRICE_SRC_CLOSE   = 1  // Close کندل
  };

//+------------------------------------------------------------------+
//| Inputs                                                           |
//+------------------------------------------------------------------+
input group "=== Stochastic #1 ==="
input int                InpStoch1K        = 12;             // %K Period
input int                InpStoch1D        = 3;              // %D Period
input int                InpStoch1Slow     = 3;              // Slowing
input ENUM_MA_METHOD     InpStoch1Method   = MODE_SMA;       // MA Method
input ENUM_STO_PRICE     InpStoch1Price    = STO_LOWHIGH;    // Price Field

input group "=== Stochastic #2 ==="
input int                InpStoch2K        = 26;             // %K Period
input int                InpStoch2D        = 3;              // %D Period
input int                InpStoch2Slow     = 3;              // Slowing
input ENUM_MA_METHOD     InpStoch2Method   = MODE_SMA;       // MA Method
input ENUM_STO_PRICE     InpStoch2Price    = STO_LOWHIGH;    // Price Field

input group "=== Moving Average ==="
input int                InpMAPeriod       = 60;             // MA Period
input ENUM_MA_METHOD     InpMAMethod       = MODE_EMA;       // MA Method (EMA/SMA/...)
input ENUM_APPLIED_PRICE InpMAPrice        = PRICE_CLOSE;    // MA Applied Price
input bool               InpUseMAFilter    = false;          // فیلتر روند با MA (صعودی بالای MA / نزولی زیر MA)

input group "=== Divergence Detection ==="
input ENUM_DIV_MODE      InpDivMode        = DIV_EITHER;     // حالت تشخیص
input ENUM_STOCH_LINE_SEL InpStochLine     = STOCH_USE_MAIN; // خط استوکاستیک برای دایورجنس
input ENUM_PRICE_SRC     InpPriceSource    = PRICE_SRC_HIGHLOW; // قیمت مرجع
input int                InpPivotLeft      = 3;              // تعداد کندل سمت چپ پیوت
input int                InpPivotRight     = 3;              // تعداد کندل سمت راست پیوت (تأیید)
input int                InpMinBarsBetween = 5;              // حداقل فاصله دو پیوت (کندل)
input int                InpMaxBarsBetween = 80;             // حداکثر فاصله دو پیوت (کندل)
input int                InpBothTolerance  = 2;              // تلورانس هم‌زمانی در حالت BOTH (کندل)
input bool               InpUseZones       = false;          // فقط در نواحی اشباع
input double             InpOversold       = 20.0;           // سطح اشباع فروش
input double             InpOverbought     = 80.0;           // سطح اشباع خرید
input int                InpLookbackBars   = 1000;           // اسکن تاریخچه (کندل)

input group "=== Drawing ==="
input color              InpColorS1Bull    = clrLime;        // رنگ Stoch1 - Hidden Bullish
input color              InpColorS1Bear    = clrRed;         // رنگ Stoch1 - Hidden Bearish
input color              InpColorS2Bull    = clrDodgerBlue;  // رنگ Stoch2 - Hidden Bullish
input color              InpColorS2Bear    = clrOrange;      // رنگ Stoch2 - Hidden Bearish
input color              InpColorBothBull  = clrGreen;       // رنگ BOTH - Hidden Bullish
input color              InpColorBothBear  = clrMagenta;     // رنگ BOTH - Hidden Bearish
input ENUM_LINE_STYLE    InpLineStyle      = STYLE_DOT;      // استایل خط عمودی
input int                InpLineWidth      = 1;              // ضخامت خط عمودی
input bool               InpDrawTrendLines = false;          // رسم خط اتصال دو پیوت روی قیمت
input bool               InpShowIndicators = true;           // نمایش اندیکاتورها روی چارت

input group "=== Alerts ==="
input bool               InpAlertPopup     = true;           // هشدار Popup
input bool               InpAlertPush      = false;          // نوتیفیکیشن موبایل
input bool               InpAlertSound     = false;          // صدا
input string             InpSoundFile      = "alert.wav";    // فایل صدا

//+------------------------------------------------------------------+
//| Globals                                                          |
//+------------------------------------------------------------------+
#define OBJ_PREFIX "OMG_HD_"

int      g_hStoch1 = INVALID_HANDLE;
int      g_hStoch2 = INVALID_HANDLE;
int      g_hMA     = INVALID_HANDLE;
datetime g_lastBar = 0;
int      g_countBull = 0;
int      g_countBear = 0;

// نام اندیکاتورهایی که به چارت اضافه شده‌اند (برای حذف در Deinit)
string   g_addedIndNames[];
int      g_addedIndWins[];

// ساختار سیگنال (برای استفاده در مرحله معاملات)
struct DivSignal
  {
   datetime time;      // زمان کندل دایورجنس
   int      direction; // +1 = Hidden Bullish , -1 = Hidden Bearish
   int      source;    // 1 = Stoch1 , 2 = Stoch2 , 3 = Both
   double   price;     // قیمت پیوت
  };
DivSignal g_lastSignal;

//+------------------------------------------------------------------+
//| Expert initialization                                            |
//+------------------------------------------------------------------+
int OnInit()
  {
   if(InpPivotLeft < 1 || InpPivotRight < 1)
     {
      Print("خطا: PivotLeft و PivotRight باید حداقل 1 باشند.");
      return(INIT_PARAMETERS_INCORRECT);
     }
   if(InpMinBarsBetween < 1 || InpMaxBarsBetween <= InpMinBarsBetween)
     {
      Print("خطا: MaxBarsBetween باید بزرگ‌تر از MinBarsBetween باشد.");
      return(INIT_PARAMETERS_INCORRECT);
     }

   g_hStoch1 = iStochastic(_Symbol, _Period, InpStoch1K, InpStoch1D, InpStoch1Slow, InpStoch1Method, InpStoch1Price);
   g_hStoch2 = iStochastic(_Symbol, _Period, InpStoch2K, InpStoch2D, InpStoch2Slow, InpStoch2Method, InpStoch2Price);
   g_hMA     = iMA(_Symbol, _Period, InpMAPeriod, 0, InpMAMethod, InpMAPrice);

   if(g_hStoch1 == INVALID_HANDLE || g_hStoch2 == INVALID_HANDLE || g_hMA == INVALID_HANDLE)
     {
      Print("خطا در ساخت هندل اندیکاتورها: ", GetLastError());
      return(INIT_FAILED);
     }

   if(InpShowIndicators && !MQLInfoInteger(MQL_TESTER))
      AttachIndicatorsToChart();

   g_lastSignal.time = 0;
   g_lastSignal.direction = 0;
   g_lastSignal.source = 0;
   g_lastSignal.price = 0;

   // اسکن تاریخچه و رسم خطوط قبلی
   ObjectsDeleteAll(0, OBJ_PREFIX);
   ScanRange(FirstCheckIndex(), InpLookbackBars);
   g_lastBar = iTime(_Symbol, _Period, 0);
   UpdateComment();

   Print("OmegaStochHiddenDivergence initialized. Bull=", g_countBull, " Bear=", g_countBear);
   return(INIT_SUCCEEDED);
  }

//+------------------------------------------------------------------+
//| Expert deinitialization                                          |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
  {
   if(g_hStoch1 != INVALID_HANDLE) IndicatorRelease(g_hStoch1);
   if(g_hStoch2 != INVALID_HANDLE) IndicatorRelease(g_hStoch2);
   if(g_hMA     != INVALID_HANDLE) IndicatorRelease(g_hMA);

   // در تغییر تایم‌فریم/پارامتر خطوط را نگه نمی‌داریم چون دوباره اسکن می‌شود
   if(reason != REASON_CHARTCHANGE)
     {
      ObjectsDeleteAll(0, OBJ_PREFIX);
      DetachIndicatorsFromChart();
      Comment("");
     }
  }

//+------------------------------------------------------------------+
//| Expert tick                                                      |
//+------------------------------------------------------------------+
void OnTick()
  {
   datetime curBar = iTime(_Symbol, _Period, 0);
   if(curBar == g_lastBar)
      return;                      // فقط روی کندل جدید کار می‌کنیم
   g_lastBar = curBar;

   int idx = FirstCheckIndex();
   ScanRange(idx, idx);
   UpdateComment();

   //--- TODO (مرحله بعد): منطق ورود/خروج معامله بر اساس g_lastSignal
  }

//+------------------------------------------------------------------+
//| اولین اندیسی که پیوت آن کاملاً تأیید شده است                        |
//+------------------------------------------------------------------+
int FirstCheckIndex()
  {
   int tol = (InpDivMode == DIV_BOTH) ? InpBothTolerance : 0;
   return(InpPivotRight + 1 + tol);
  }

//+------------------------------------------------------------------+
//| اسکن بازه [fromIdx .. toIdx] (اندیس سری، 0 = کندل جاری)             |
//+------------------------------------------------------------------+
void ScanRange(int fromIdx, int toIdx)
  {
   int tol   = (InpDivMode == DIV_BOTH) ? InpBothTolerance : 0;
   int extra = InpMaxBarsBetween + InpPivotLeft + tol + 5;
   int need  = toIdx + extra;

   int avail = Bars(_Symbol, _Period);
   if(need > avail) need = avail;
   if(need <= fromIdx + extra) return;

   double s1[], s2[], ma[], hi[], lo[], cl[];
   datetime tm[];
   ArraySetAsSeries(s1, true); ArraySetAsSeries(s2, true); ArraySetAsSeries(ma, true);
   ArraySetAsSeries(hi, true); ArraySetAsSeries(lo, true); ArraySetAsSeries(cl, true);
   ArraySetAsSeries(tm, true);

   int buf = (InpStochLine == STOCH_USE_MAIN) ? MAIN_LINE : SIGNAL_LINE;

   if(CopyBuffer(g_hStoch1, buf, 0, need, s1) < need) return;
   if(CopyBuffer(g_hStoch2, buf, 0, need, s2) < need) return;
   if(CopyBuffer(g_hMA, 0, 0, need, ma)     < need) return;
   if(CopyHigh(_Symbol, _Period, 0, need, hi)  < need) return;
   if(CopyLow(_Symbol, _Period, 0, need, lo)   < need) return;
   if(CopyClose(_Symbol, _Period, 0, need, cl) < need) return;
   if(CopyTime(_Symbol, _Period, 0, need, tm)  < need) return;

   int total = need;
   int maxIdx = MathMin(toIdx, total - extra - 1);

   for(int idx = maxIdx; idx >= fromIdx; idx--)
     {
      int prev1 = -1, prev2 = -1;
      int d1 = 0, d2 = 0;

      if(InpDivMode == DIV_EITHER || InpDivMode == DIV_STOCH1_ONLY || InpDivMode == DIV_BOTH)
         d1 = CheckDivergence(s1, hi, lo, cl, idx, total, prev1);
      if(InpDivMode == DIV_EITHER || InpDivMode == DIV_STOCH2_ONLY)
         d2 = CheckDivergence(s2, hi, lo, cl, idx, total, prev2);

      // فیلتر روند با MA
      if(InpUseMAFilter)
        {
         if(d1 == 1 && cl[idx] < ma[idx]) d1 = 0;
         if(d1 == -1 && cl[idx] > ma[idx]) d1 = 0;
         if(d2 == 1 && cl[idx] < ma[idx]) d2 = 0;
         if(d2 == -1 && cl[idx] > ma[idx]) d2 = 0;
        }

      if(InpDivMode == DIV_BOTH)
        {
         if(d1 == 0) continue;
         // آیا استوکاستیک دوم هم در بازه تلورانس همان جهت را تأیید می‌کند؟
         bool confirmed = false;
         int  prevB = -1;
         for(int k = idx - tol; k <= idx + tol; k++)
           {
            if(k < InpPivotRight + 1) continue;
            int dk = CheckDivergence(s2, hi, lo, cl, k, total, prevB);
            if(InpUseMAFilter)
              {
               if(dk == 1 && cl[k] < ma[k]) dk = 0;
               if(dk == -1 && cl[k] > ma[k]) dk = 0;
              }
            if(dk == d1) { confirmed = true; break; }
           }
         if(confirmed)
            DrawSignal(3, d1, idx, prev1, tm, hi, lo, cl);
         continue;
        }

      if(d1 != 0) DrawSignal(1, d1, idx, prev1, tm, hi, lo, cl);
      if(d2 != 0) DrawSignal(2, d2, idx, prev2, tm, hi, lo, cl);
     }
  }

//+------------------------------------------------------------------+
//| آیا idx یک پیوت کف اسیلاتور است؟                                   |
//+------------------------------------------------------------------+
bool IsPivotLow(const double &b[], int idx, int total)
  {
   if(idx - InpPivotRight < 0 || idx + InpPivotLeft >= total) return(false);
   double v = b[idx];
   for(int i = 1; i <= InpPivotLeft; i++)  if(b[idx + i] <= v) return(false);
   for(int i = 1; i <= InpPivotRight; i++) if(b[idx - i] <  v) return(false);
   return(true);
  }

//+------------------------------------------------------------------+
//| آیا idx یک پیوت سقف اسیلاتور است؟                                  |
//+------------------------------------------------------------------+
bool IsPivotHigh(const double &b[], int idx, int total)
  {
   if(idx - InpPivotRight < 0 || idx + InpPivotLeft >= total) return(false);
   double v = b[idx];
   for(int i = 1; i <= InpPivotLeft; i++)  if(b[idx + i] >= v) return(false);
   for(int i = 1; i <= InpPivotRight; i++) if(b[idx - i] >  v) return(false);
   return(true);
  }

//+------------------------------------------------------------------+
//| تشخیص هیدن دایورجنس در کندل idx                                   |
//| خروجی: +1 Hidden Bullish | -1 Hidden Bearish | 0 هیچ                |
//| prevIdx: اندیس پیوت قبلی که با آن مقایسه شده                        |
//+------------------------------------------------------------------+
int CheckDivergence(const double &osc[], const double &hi[], const double &lo[], const double &cl[],
                    int idx, int total, int &prevIdx)
  {
   prevIdx = -1;

   //--- Hidden Bullish : پیوت کف اسیلاتور
   if(IsPivotLow(osc, idx, total))
     {
      if(!InpUseZones || osc[idx] <= InpOversold)
        {
         for(int j = idx + InpMinBarsBetween; j <= idx + InpMaxBarsBetween; j++)
           {
            if(j + InpPivotLeft >= total) break;
            if(!IsPivotLow(osc, j, total)) continue;

            double pNow  = (InpPriceSource == PRICE_SRC_CLOSE) ? cl[idx] : lo[idx];
            double pPrev = (InpPriceSource == PRICE_SRC_CLOSE) ? cl[j]   : lo[j];

            // قیمت کف بالاتر  +  اسیلاتور کف پایین‌تر
            if(pNow > pPrev && osc[idx] < osc[j])
              {
               prevIdx = j;
               return(1);
              }
            break; // فقط با نزدیک‌ترین پیوت قبلی مقایسه می‌شود
           }
        }
     }

   //--- Hidden Bearish : پیوت سقف اسیلاتور
   if(IsPivotHigh(osc, idx, total))
     {
      if(!InpUseZones || osc[idx] >= InpOverbought)
        {
         for(int j = idx + InpMinBarsBetween; j <= idx + InpMaxBarsBetween; j++)
           {
            if(j + InpPivotLeft >= total) break;
            if(!IsPivotHigh(osc, j, total)) continue;

            double pNow  = (InpPriceSource == PRICE_SRC_CLOSE) ? cl[idx] : hi[idx];
            double pPrev = (InpPriceSource == PRICE_SRC_CLOSE) ? cl[j]   : hi[j];

            // قیمت سقف پایین‌تر  +  اسیلاتور سقف بالاتر
            if(pNow < pPrev && osc[idx] > osc[j])
              {
               prevIdx = j;
               return(-1);
              }
            break;
           }
        }
     }

   return(0);
  }

//+------------------------------------------------------------------+
//| رسم خط عمودی (و اختیاری خط روند) + هشدار                          |
//+------------------------------------------------------------------+
void DrawSignal(int source, int dir, int idx, int prevIdx,
                const datetime &tm[], const double &hi[], const double &lo[], const double &cl[])
  {
   string srcTag = (source == 1) ? "S1" : (source == 2) ? "S2" : "BOTH";
   string dirTag = (dir > 0) ? "BULL" : "BEAR";
   string name   = OBJ_PREFIX + srcTag + "_" + dirTag + "_" + IntegerToString((long)tm[idx]);

   if(ObjectFind(0, name) >= 0)
      return; // قبلاً رسم شده

   color clr = clrWhite;
   if(source == 1) clr = (dir > 0) ? InpColorS1Bull : InpColorS1Bear;
   if(source == 2) clr = (dir > 0) ? InpColorS2Bull : InpColorS2Bear;
   if(source == 3) clr = (dir > 0) ? InpColorBothBull : InpColorBothBear;

   string srcName = (source == 1) ? "Stoch(" + IntegerToString(InpStoch1K) + "," + IntegerToString(InpStoch1D) + "," + IntegerToString(InpStoch1Slow) + ")" :
                    (source == 2) ? "Stoch(" + IntegerToString(InpStoch2K) + "," + IntegerToString(InpStoch2D) + "," + IntegerToString(InpStoch2Slow) + ")" :
                    "Both Stochastics";
   string desc = (dir > 0 ? "Hidden Bullish Divergence - " : "Hidden Bearish Divergence - ") + srcName;

   if(ObjectCreate(0, name, OBJ_VLINE, 0, tm[idx], 0))
     {
      ObjectSetInteger(0, name, OBJPROP_COLOR, clr);
      ObjectSetInteger(0, name, OBJPROP_STYLE, InpLineStyle);
      ObjectSetInteger(0, name, OBJPROP_WIDTH, (source == 3) ? InpLineWidth + 1 : InpLineWidth);
      ObjectSetInteger(0, name, OBJPROP_BACK, true);
      ObjectSetInteger(0, name, OBJPROP_SELECTABLE, false);
      ObjectSetInteger(0, name, OBJPROP_HIDDEN, true);
      ObjectSetInteger(0, name, OBJPROP_RAY, true);   // در همه پنجره‌ها نمایش داده شود
      ObjectSetString(0, name, OBJPROP_TEXT, desc);
      ObjectSetString(0, name, OBJPROP_TOOLTIP, desc + "\n" + TimeToString(tm[idx], TIME_DATE | TIME_MINUTES));
     }

   // خط اتصال دو پیوت روی نمودار قیمت
   if(InpDrawTrendLines && prevIdx > idx)
     {
      string tlName = name + "_TL";
      double p1, p2;
      if(InpPriceSource == PRICE_SRC_CLOSE) { p1 = cl[prevIdx]; p2 = cl[idx]; }
      else if(dir > 0)                       { p1 = lo[prevIdx]; p2 = lo[idx]; }
      else                                   { p1 = hi[prevIdx]; p2 = hi[idx]; }

      if(ObjectCreate(0, tlName, OBJ_TREND, 0, tm[prevIdx], p1, tm[idx], p2))
        {
         ObjectSetInteger(0, tlName, OBJPROP_COLOR, clr);
         ObjectSetInteger(0, tlName, OBJPROP_STYLE, STYLE_SOLID);
         ObjectSetInteger(0, tlName, OBJPROP_WIDTH, 2);
         ObjectSetInteger(0, tlName, OBJPROP_RAY_RIGHT, false);
         ObjectSetInteger(0, tlName, OBJPROP_SELECTABLE, false);
         ObjectSetInteger(0, tlName, OBJPROP_HIDDEN, true);
         ObjectSetString(0, tlName, OBJPROP_TOOLTIP, desc);
        }
     }

   if(dir > 0) g_countBull++; else g_countBear++;

   // ذخیره آخرین سیگنال (برای مرحله معاملات)
   if(tm[idx] >= g_lastSignal.time)
     {
      g_lastSignal.time      = tm[idx];
      g_lastSignal.direction = dir;
      g_lastSignal.source    = source;
      g_lastSignal.price     = (dir > 0) ? lo[idx] : hi[idx];
     }

   // هشدار فقط برای سیگنال‌های زنده (نه اسکن تاریخچه)
   if(g_lastBar != 0)
     {
      string msg = _Symbol + " " + EnumToString(_Period) + " : " + desc + " @ " + TimeToString(tm[idx], TIME_DATE | TIME_MINUTES);
      Print(msg);
      if(InpAlertPopup) Alert(msg);
      if(InpAlertPush)  SendNotification(msg);
      if(InpAlertSound) PlaySound(InpSoundFile);
     }
  }

//+------------------------------------------------------------------+
//| نمایش خلاصه روی چارت                                               |
//+------------------------------------------------------------------+
void UpdateComment()
  {
   string mode;
   switch(InpDivMode)
     {
      case DIV_BOTH:        mode = "BOTH";   break;
      case DIV_STOCH1_ONLY: mode = "Stoch1"; break;
      case DIV_STOCH2_ONLY: mode = "Stoch2"; break;
      default:              mode = "EITHER"; break;
     }

   string txt = "Omega Stoch Hidden Divergence EA\n";
   txt += "Stoch1(" + IntegerToString(InpStoch1K) + "," + IntegerToString(InpStoch1D) + "," + IntegerToString(InpStoch1Slow) + ")  ";
   txt += "Stoch2(" + IntegerToString(InpStoch2K) + "," + IntegerToString(InpStoch2D) + "," + IntegerToString(InpStoch2Slow) + ")  ";
   txt += "MA(" + IntegerToString(InpMAPeriod) + " " + EnumToString(InpMAMethod) + ")\n";
   txt += "Mode: " + mode + "   MA Filter: " + (InpUseMAFilter ? "ON" : "OFF") + "\n";
   txt += "Hidden Bullish: " + IntegerToString(g_countBull) + "   Hidden Bearish: " + IntegerToString(g_countBear) + "\n";
   if(g_lastSignal.time > 0)
      txt += "Last: " + (g_lastSignal.direction > 0 ? "BULL" : "BEAR") + " @ " + TimeToString(g_lastSignal.time, TIME_DATE | TIME_MINUTES);
   Comment(txt);
  }

//+------------------------------------------------------------------+
//| اضافه کردن اندیکاتورها به چارت                                      |
//+------------------------------------------------------------------+
void AttachIndicatorsToChart()
  {
   AddIndicator(g_hMA, 0);
   int w1 = (int)ChartGetInteger(0, CHART_WINDOWS_TOTAL);
   AddIndicator(g_hStoch1, w1);
   int w2 = (int)ChartGetInteger(0, CHART_WINDOWS_TOTAL);
   AddIndicator(g_hStoch2, w2);
   ChartRedraw(0);
  }

void AddIndicator(int handle, int window)
  {
   if(!ChartIndicatorAdd(0, window, handle))
     {
      Print("ChartIndicatorAdd failed: ", GetLastError());
      return;
     }
   int cnt = ChartIndicatorsTotal(0, window);
   string nm = ChartIndicatorName(0, window, cnt - 1);
   int n = ArraySize(g_addedIndNames);
   ArrayResize(g_addedIndNames, n + 1);
   ArrayResize(g_addedIndWins, n + 1);
   g_addedIndNames[n] = nm;
   g_addedIndWins[n]  = window;
  }

void DetachIndicatorsFromChart()
  {
   for(int i = ArraySize(g_addedIndNames) - 1; i >= 0; i--)
      ChartIndicatorDelete(0, g_addedIndWins[i], g_addedIndNames[i]);
   ArrayResize(g_addedIndNames, 0);
   ArrayResize(g_addedIndWins, 0);
   ChartRedraw(0);
  }
//+------------------------------------------------------------------+
