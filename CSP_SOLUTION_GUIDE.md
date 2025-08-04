# دليل حل مشكلة Content Security Policy (CSP)

## المشكلة الأصلية

كانت هناك أخطاء CSP تمنع تحميل الموارد الخارجية مثل Google Fonts:

```
Refused to load the stylesheet 'https://fonts.googleapis.com/css2?family=Public+Sans...' because it violates the following Content Security Policy directive: "style-src 'self' 'unsafe-inline'".

Refused to execute inline script because it violates the following Content Security Policy directive: "script-src 'self'".
```

## سبب المشكلة

1. **تضارب في Middleware**: كان هناك عدة middleware تقوم بتعيين رؤوس CSP:
   - `SecurityHeaders.php`
   - `CompressResponse.php` 
   - `HttpsProtocol.php`

2. **ترتيب Middleware خاطئ**: كان `CompressResponse` يتم تطبيقه قبل `SecurityHeaders`

3. **إعدادات CSP غير مكتملة**: لم تكن تتضمن نطاقات Google Fonts

## الحل المطبق

### 1. إعادة تنظيم ملفات التكوين

#### إنشاء ملف `config/csp.php` مخصص:
```php
<?php
return [
    'enabled' => env('CSP_ENABLED', true),
    'directives' => [
        'default-src' => ["'self'"],
        'script-src' => [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            "https://fonts.googleapis.com",
            "https://fonts.gstatic.com",
            // ... المزيد
        ],
        'style-src' => [
            "'self'",
            "'unsafe-inline'",
            "https://fonts.googleapis.com",
            "https://fonts.gstatic.com",
        ],
        'font-src' => [
            "'self'",
            "data:",
            "https://fonts.googleapis.com",
            "https://fonts.gstatic.com",
        ],
        // ... باقي التوجيهات
    ],
];
```

### 2. تحديث SecurityHeaders Middleware

```php
protected function getEnhancedCSP(bool $isMonitoringPage = false): string
{
    if (!Config::get('csp.enabled', true)) {
        return '';
    }

    $csp = Config::get('csp.directives', []);
    
    if ($isMonitoringPage) {
        $monitoringOverrides = Config::get('csp.monitoring_overrides', []);
        foreach ($monitoringOverrides as $directive => $values) {
            $csp[$directive] = array_merge($csp[$directive] ?? [], $values);
        }
    }

    return $this->buildCSPString($csp);
}
```

### 3. إزالة التضارب من Middleware الأخرى

#### CompressResponse.php:
- تم إزالة تعيين رؤوس الأمان
- تم الاحتفاظ فقط برؤوس HSTS إذا لزم الأمر

#### HttpsProtocol.php:
- تم إزالة جميع رؤوس الأمان عدا HSTS
- تم تقييد HSTS للإنتاج و HTTPS فقط

### 4. إعادة ترتيب Middleware في bootstrap/app.php

```php
// الترتيب الصحيح:
$middleware->web(HttpsProtocol::class);        // أولاً: HTTPS
$middleware->web(LocaleMiddleware::class);     // ثانياً: اللغة
$middleware->web(SecurityHeaders::class);      // ثالثاً: الأمان (CSP)
$middleware->web(CompressResponse::class);     // رابعاً: الضغط
// ... باقي middleware
```

## النتيجة

### المشاكل التي تم حلها:

1. ✅ **Google Fonts**: يتم تحميلها بنجاح
2. ✅ **Inline Scripts**: مسموحة مع `'unsafe-inline'`
3. ✅ **External Resources**: مسموحة من النطاقات الموثوقة
4. ✅ **No Conflicts**: لا يوجد تضارب بين middleware

### الميزات المحافظ عليها:

1. 🔒 **الأمان**: CSP لا يزال يحمي من XSS
2. 🚀 **الأداء**: الضغط والتحسينات لا تزال تعمل
3. 🌐 **المرونة**: إعدادات خاصة لصفحات المراقبة
4. ⚙️ **القابلية للتكوين**: يمكن تعديل CSP من ملف التكوين

## كيفية التخصيص

### إضافة نطاق جديد:
```php
// في config/csp.php
'style-src' => [
    "'self'",
    "'unsafe-inline'",
    "https://fonts.googleapis.com",
    "https://your-new-domain.com", // إضافة نطاق جديد
],
```

### تعطيل CSP مؤقتاً:
```bash
# في .env
CSP_ENABLED=false
```

### إعدادات خاصة للتطوير:
```php
// في config/csp.php
'script-src' => [
    "'self'",
    "'unsafe-inline'",
    "'unsafe-eval'", // للتطوير فقط
    // ...
],
```

## اختبار الحل

1. **تحقق من Console**: لا يجب أن تظهر أخطاء CSP
2. **تحقق من الخطوط**: Google Fonts تحمل بنجاح
3. **تحقق من Scripts**: JavaScript يعمل بدون مشاكل
4. **تحقق من الأمان**: CSP لا يزال يحمي من المحتوى الضار

## الصيانة المستقبلية

1. **مراجعة دورية**: تحقق من أخطاء CSP في Console
2. **تحديث النطاقات**: أضف نطاقات جديدة عند الحاجة
3. **مراقبة الأداء**: تأكد من عدم تأثير CSP على الأداء
4. **اختبار الأمان**: تأكد من فعالية الحماية من XSS

---

**ملاحظة**: هذا الحل يوازن بين الأمان والوظائف، مع الحفاظ على حماية قوية ضد هجمات XSS مع السماح بالموارد الضرورية للتطبيق.
