# وحدة واتساب — Laravel + Node.js (whatsapp-web.js)

وحدة جاهزة لربط تطبيق Laravel مع [whatsapp-web.js](https://github.com/pedroslopez/whatsapp-web.js) عبر خدمة **Express** صغيرة. تشمل: إعدادات قاعدة البيانات، القوالب، سجل الرسائل، واجهة **المحادثات** (`/whatsapp`)، واجهة **الإعدادات** (`/whatsapp/settings`)، وربط **QR**، وإمكانية تسجيل **الرسائل الواردة** في Laravel عبر Webhook داخلي.

> **مشروع NeoGym Pro:** الملفات مدمجة أصلاً تحت جذر المشروع (`app/`, `routes/whatsapp.php`, `Database/migrations/…`). مجلد `whatsapp-module/laravel/` يبقى **مرجعاً** لنسخ التحديثات أو لدمج الوحدة في مشروع آخر.

---

## جدول المحتويات

1. [المتطلبات](#المتطلبات)
2. [هيكل المجلد](#هيكل-المجلد)
3. [مخطط التشغيل السريع](#مخطط-التشغيل-السريع)
4. [الخطوة 1 — خدمة Node](#الخطوة-1--خدمة-node)
5. [الخطوة 2 — Laravel (قاعدة البيانات والمسارات)](#الخطوة-2--laravel-قاعدة-البيانات-والمسارات)
6. [الخطوة 3 — ملفات البيئة والسرّ المشترك](#الخطوة-3--ملفات-البيئة-والسرّ-المشترك)
7. [الخطوة 4 — الإعدادات من الواجهة](#الخطوة-4--الإعدادات-من-الواجهة)
8. [الخطوة 5 — ربط الحساب (QR) والعمل اليومي](#الخطوة-5--ربط-الحساب-qr-والعمل-اليومي)
9. [الرسائل الواردة (Webhook)](#الرسائل-الواردة-webhook)
10. [الاستخدام من الكود (PHP)](#الاستخدام-من-الكود-php)
11. [نقاط تقنية مهمة](#نقاط-تقنية-مهمة)
12. [استكشاف الأخطاء](#استكشاف-الأخطاء)
13. [الإنتاج و PM2](#الإنتاج-و-pm2)
14. [الأمان](#الأمان)

---

## المتطلبات

| المكوّن | الحد الأدنى |
|--------|-------------|
| **PHP** | 8.0+ (متوافق Laravel 9) |
| **Laravel** | 9.x |
| **Node.js** | ≥ 16 (يُفضّل LTS 18 أو أحدث) |
| **npm** | يعمل مع Node |
| **MySQL** | حسب مشروعك (جداول الوحدة عبر الهجرات) |
| **Chromium** | يُثبَّت تلقائياً مع **Puppeteer**؛ على Linux قد تحتاج حزماً إضافية (انظر أسفل README الأصلي لأمثلة `apt`) |

**التحقق:**

```bash
php -v
node --version
npm --version
```

---

## هيكل المجلد

```
whatsapp-module/
├── README.md                 # هذا الملف
├── node-service/             # خادم Node (Express + whatsapp-web.js)
│   ├── index.js
│   ├── package.json
│   ├── .env.example
│   └── session/              # يُنشأ تلقائياً — لا ترفعه للمستودع
└── laravel/                  # مرجع لدمج الوحدة في مشروع Laravel
    ├── app/
    │   ├── Http/Controllers/WhatsApp/WhatsAppController.php
    │   ├── Models/           (WhatsAppLog, WhatsAppSetting, WhatsAppTemplate)
    │   └── Services/WhatsAppService.php
    ├── config/whatsapp.php
    ├── database/migrations/  (whats_app_*)
    ├── resources/views/whatsapp/
    │   ├── index.blade.php   # شاشة المحادثات
    │   └── settings.blade.php
    └── routes/whatsapp.php
```

في **NeoGym Pro** تُحمَّل المسارات عبر `routes/web.php`:

```php
require base_path('routes/whatsapp.php');
```

وتوجد نقطة **الوارد** في `routes/api.php`:

```php
Route::post('/whatsapp/incoming', [WhatsAppController::class, 'incomingMessage']);
```

---

## مخطط التشغيل السريع

1. تشغيل **Node** على منفذ ثابت (مثلاً `3001`) مع `API_SECRET_KEY`.
2. تشغيل **Laravel** على عنوانك الفعلي (مثلاً `http://127.0.0.1:9090`).
3. في واجهة **إعدادات واتساب**: تعبئة **رابط الخدمة** و**مفتاح API** ليطابقا Node.
4. إضافة **`WHATSAPP_INTERNAL_SECRET`** في `.env` لـ Laravel ونفس القيمة في `.env` لـ Node + **`LARAVEL_INCOMING_WEBHOOK_URL`** إن أردت الرسائل الواردة.
5. **`php artisan migrate`** ثم من الإعدادات: **تهيئة / مسح QR** ومسح الرمز من الهاتف.
6. التأكد أن **نسخة واحدة فقط** من `node index.js` تعمل (تجنّب `EADDRINUSE`).

---

## الخطوة 1 — خدمة Node

### 1.1 التثبيت والتشغيل

```bash
cd whatsapp-module/node-service
copy .env.example .env    # Windows
# أو: cp .env.example .env
npm install
npm start
# أو: node index.js
```

عند النجاح يظهر في الطرفية شيء مثل: `WhatsApp node service listening on port 3001`.

### 1.2 متغيرات `node-service/.env`

| المتغير | الوصف |
|---------|--------|
| `PORT` | منفذ الخدمة (افتراضي `3001`) |
| `API_SECRET_KEY` | سرّ قوي؛ يجب أن يطابق **مفتاح API** في إعدادات Laravel |
| `SESSION_PATH` | مجلد حفظ جلسة `LocalAuth` (مثلاً `./session`) |
| `ALLOWED_ORIGIN` | أصل CORS؛ غالباً عنوان واجهة Laravel (مثلاً `http://127.0.0.1:9090`) أو `*` للتجربة |
| `DEFAULT_COUNTRY_CODE` | كود الدولة بدون `+` (مثلاً `20` لمصر) |
| `LARAVEL_INCOMING_WEBHOOK_URL` | (اختياري) عنوان كامل لـ Laravel لاستقبال الوارد، مثال: `http://127.0.0.1:9090/api/whatsapp/incoming` |
| `WHATSAPP_INTERNAL_SECRET` | (اختياري لكن مُستحسن للوارد) نفس قيمة `WHATSAPP_INTERNAL_SECRET` في `.env` لـ Laravel |
| `WA_WEB_VERSION_REMOTE` | (اختياري) رابط HTML لنسخة واتساب ويب البعيدة؛ إذا **تركتها فارغة** تُستخدم الذاكرة المحلية الافتراضية للمكتبة (بدون اعتماد على GitHub) |

**مصادقة الطلبات:** كل المسارات ما عدا التحقق اليدوي تتطلب الترويسة:

```http
x-api-key: <قيمة API_SECRET_KEY>
```

Laravel يرسلها تلقائياً عبر `WhatsAppService`.

### 1.3 نقاط API المهمة (Node)

| الطريقة | المسار | الوظيفة |
|--------|--------|---------|
| `GET` | `/health` | فحص أن العملية حية |
| `GET` | `/api/status` | `{ status, qr, info, last_error }` — حالة الجلسة وصورة QR إن وُجدت |
| `POST` | `/api/initialize` | جسم JSON: `{ "reset_session": true }` لمسح ملفات الجلسة وإجبار QR جديد، أو `false`/غياب للتهيئة العادية |
| `POST` | `/api/logout` | تسجيل خروج واتساب وحذف محتويات مجلد الجلسة |
| `POST` | `/api/send` | إرسال رسالة نصية |
| `POST` | `/api/send-bulk` | إرسال جماعي |

**ملاحظة:** `initialize` يعيد `{ success: true, message: "initializing" }` بسرعة؛ ظهور QR يحصل بعد ثوانٍ داخل المتصفح المضمن (Puppeteer)، لذا الواجهة تعيد الاستعلام عن `/api/status` دورياً.

---

## الخطوة 2 — Laravel (قاعدة البيانات والمسارات)

### 2.1 دمج الملفات (مشروع جديد)

إن لم تكن الوحدة مدمجة:

1. ادمج محتويات `whatsapp-module/laravel/app` في `app/` (المتحكم، النماذج، الخدمة).
2. انسخ `config/whatsapp.php` إلى مشروعك (أو طابق الموجود في المشروع).
3. انسخ الهجرات من `whatsapp-module/laravel/database/migrations` إلى `database/migrations` (أو `Database/migrations` حسب هيكل مشروعك).
4. انسخ `resources/views/whatsapp/*` إلى مشروعك.
5. سجّل المسارات: انسخ محتوى `routes/whatsapp.php` أو استخدم `require` كما في NeoGym Pro داخل مجموعة `web` المناسبة للوحة التحكم.

### 2.2 الهجرات

```bash
php artisan migrate
```

الجداول النموذجية: `whats_app_settings`, `whats_app_templates`, `whats_app_logs` (الأسماء قد تختلف قليلاً حسب الهجرة؛ النماذج تستخدم `$table` الصحيح).

### 2.3 الصلاحيات

`routes/whatsapp.php` موثّق لاستخدام Gate مثل `manage-whatsapp`. في مشروعك إمّا:

- تعريف `Gate::define('manage-whatsapp', …)` في `AuthServiceProvider`، أو  
- تعديل وسيط المسارات/المتحكم ليتوافق مع نظام الصلاحيات لديك (مثلاً Spatie).

### 2.4 التخطيط (Layout) والواجهة

- في **NeoGym Pro** تستخدم الصفحات `layouts.master_table` مع دعم **Bootstrap 5** (مع توافق جزئي لعناصر قديمة حيث يلزم).
- إن نسخت الوحدة لمشروع يعتمد `layouts.app` فقط، تأكد من وجود `@stack('styles')` و`@stack('scripts')` إذا استخدمتها الواجهات المرفقة.

---

## الخطوة 3 — ملفات البيئة والسرّ المشترك

### 3.1 Laravel — جذر المشروع `.env`

```env
# سرّ مشترك بين Laravel و Node؛ يُرسل مع الطلبات الواردة في الترويسة X-WhatsApp-Internal
WHATSAPP_INTERNAL_SECRET=ضع_هنا_نصاً_عشوائياً_طويلاً_قوياً
```

القيمة تُقرأ من `config('whatsapp.internal_webhook_secret')`.

### 3.2 Node — `whatsapp-module/node-service/.env`

```env
API_SECRET_KEY=نفس_مفتاح_الإعدادات_في_الواجهة
LARAVEL_INCOMING_WEBHOOK_URL=http://127.0.0.1:9090/api/whatsapp/incoming
WHATSAPP_INTERNAL_SECRET=نفس_قيمة_WHATSAPP_INTERNAL_SECRET_في_Laravel
```

**مهم جداً:**

- استبدل `127.0.0.1:9090` بـ **العنوان والمنفذ الفعليين** حيث يعمل Laravel من جهة الخادم الذي يشغّل Node (من Node يجب أن يصل الطلب إلى Laravel).
- بعد تعديل `.env` في Laravel نفّذ: `php artisan config:clear`.
- بعد تعديل `.env` في Node **أعد تشغيل** عملية Node.

---

## الخطوة 4 — الإعدادات من الواجهة

افتح: **`/whatsapp/settings`** (بعد تسجيل الدخول وصلاحياتك).

1. **رابط خدمة الواتساب (`service_url`)** — مثلاً `http://127.0.0.1:3001` (بدون شرطة مائلة أخيرة).
2. **مفتاح API (`api_key`)** — مطابق لـ `API_SECRET_KEY` في Node.
3. **كود الدولة، مهلة الاتصال، التأخير الجماعي، الحد الأقصى للجماعي** — حسب احتياجك.
4. احفظ الإعدادات.

زر **فحص الخدمة** يتحقق من `GET /health` على Node.

---

## الخطوة 5 — ربط الحساب (QR) والعمل اليومي

### 5.1 من صفحة الإعدادات

1. اضغط **«تهيئة / مسح QR»**.  
   - يرسل Laravel إلى Node: `POST /api/initialize` مع **`reset_session: true`** (مسح جلسة محلية ثم بدء متصفح جديد).  
2. انتظر حتى تظهر بطاقة **امسح رمز QR** (قد يستغرق الأمر من عشر ثوانٍ إلى نحو دقيقة).  
3. على الهاتف: **واتساب ← الإعدادات ← الأجهزة المرتبطة ← ربط جهاز** وامسح الرمز.  
4. عند النجاح تصبح الحالة **جاهز** ويظهر اسم/رقم الجهاز إن توفّر.

### 5.2 من صفحة المحادثات `/whatsapp`

- زر **QR** يفتح نافذة الربط؛ إن كانت الحالة **غير متصل** يُطلب التهيئة تلقائياً مع **`reset_session: true`** ثم يُستأنف الاستعلام حتى يظهر الرمز.  
- إن كانت الجلسة **جاهزة** يُعرض تنبيه أن الربط غير لازم.

### 5.3 تسجيل الخروج

**«تسجيل خروج»** يقطع جلسة واتساب ويمسح ملفات الجلسة على القرص. لإظهار QR من جديد بعدها استخدم مرة أخرى **تهيئة / مسح QR**.

### 5.4 إن لم يظهر QR أو بقيت الحالة `disconnected`

- راجع حقل **`last_error`** في استجابة `GET /api/status` (تُمرَّر عبر Laravel إلى الواجهة وتُعرض عند الإمكان).  
- راجع سجل الطرفية لـ Node (`initialize error`).  
- تأكد من عدم تشغيل **أكثر من نسخة** Node على نفس المنفذ.

---

## الرسائل الواردة (Webhook)

عند ضبط `LARAVEL_INCOMING_WEBHOOK_URL` و`WHATSAPP_INTERNAL_SECRET` في Node وLaravel:

1. Node يستقبل رسائل واتساب الواردة (غير المجموعات) ويرسل **POST** JSON إلى Laravel.  
2. المسار الافتراضي في هذا المشروع: **`POST /api/whatsapp/incoming`**.  
3. الترويسة المطلوبة: **`X-WhatsApp-Internal: <نفس السرّ>`**.  
4. تُسجَّل الرسالة في السجلات بحالة مناسبة وتظهر في واجهة المحادثة عند تحميل محادثة الرقم.

بدون هذا الضبط، الإرسال قد يعمل لكن **الوارد** لا يُسجَّل في النظام.

---

## الاستخدام من الكود (PHP)

```php
use App\Services\WhatsAppService;

$wa = new WhatsAppService();

// رسالة نصية
$wa->sendMessage('01001234567', 'مرحباً بك في النادي');

// قالب من قاعدة البيانات / الإعدادات
$wa->sendTemplate('birthday', '01001234567', [
    'name' => 'أحمد',
]);
```

تأكد أن **الإرسال مفعّل** من إعدادات الواتساب في الواجهة وأن الجلسة في Node **جاهزة**.

---

## نقاط تقنية مهمة

| الموضوع | التفاصيل |
|---------|-----------|
| **حد المعدّل (Rate limit)** على Node | حوالي **500** طلب لكل 15 دقيقة لكل IP؛ طلبات **`GET /health`** و**`GET /api/status`** مُستثناة من العدّ لتفادي أخطاء «Too many requests» أثناء الاستطلاع. |
| **التهيئة من Laravel** | `WhatsAppService::initialize(bool $resetSession)` يرسل JSON `{ "reset_session": true/false }` إلى Node. |
| **نسخة واتساب ويب** | الافتراضي: ذاكرة محلية للمكتبة. للضبط اليدوي عرّف `WA_WEB_VERSION_REMOTE` في `.env` لـ Node. |
| **منفذ واحد** | خطأ `EADDRINUSE` يعني أن المنفذ (مثلاً 3001) مستخدم؛ أوقف العملية القديمة قبل تشغيل أخرى. |
| **Puppeteer / «detached Frame»** | غالباً جلسة متصفح تالفة أو أكثر من نسخة؛ أعد التهيئة مع مسح الجلسة، أو أعد تشغيل Node، وتأكد من عدم تعارض العمليات. |

---

## استكشاف الأخطاء

| العرض | ما يمكن فعله |
|--------|----------------|
| الاتصال ناجح لكن `status: disconnected` و`qr: null` | انتظر بعد **تهيئة**؛ إن استمر الوضع اقرأ **`last_error`** في JSON الحالة أو سجل Node. |
| لا يظهر QR بعد «مسح الجلسة» | استخدم **تهيئة / مسح QR** (يمرّر `reset_session: true`)؛ أعد تشغيل Node بعد تعديل `.env`. |
| `Unauthorized` من Node | طابق `API_SECRET_KEY` (Node) و`api_key` (إعدادات Laravel). |
| Laravel لا يصل إلى Node | تحقق من `service_url`، الجدار الناري، وأن Node يستمع على `0.0.0.0` أو العنوان الصحيح. |
| الوارد لا يُسجَّل | صحح `LARAVEL_INCOMING_WEBHOOK_URL` (من جهة Node يجب أن يصل إلى Laravel)، وطابق السرّ في الطرفين، و`php artisan config:clear`. |

---

## الإنتاج و PM2

```bash
npm install -g pm2
cd whatsapp-module/node-service
pm2 start index.js --name whatsapp-wweb
pm2 save && pm2 startup
```

استخدم متغيرات بيئة حقيقية، HTTPS أمام الواجهة، ولا تفضح منفذ Node للعامة بدون حماية.

---

## الأمان

- لا ترفع **`node-service/.env`** أو مجلد **`SESSION_PATH`** إلى مستودع عام.  
- استخدم **`API_SECRET_KEY`** و**`WHATSAPP_INTERNAL_SECRET`** قويين.  
- خدمة Node تتحكم بحساب واتساب؛ عرضها يجب أن يكون داخل شبكة موثوقة أو خلف VPN/جدار نار.

---

## التوافق المرجعي

- Laravel **9.x**، PHP **8.0+**  
- Node.js **≥ 16**  
- المكتبة: **whatsapp-web.js** (يُحدَّث عبر `package.json` في `node-service`)

لأي دمج جديد، طابق أولاً الملفات داخل `whatsapp-module/laravel/` مع نسخ المشروع الفعلية (NeoGym Pro) ثم حدّث هذا README إذا اختلف مسار الهجرات أو أسماء الجداول.
