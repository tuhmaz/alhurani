# دليل حل مشاكل SMTP - SMTP Troubleshooting Guide

## نظرة عامة - Overview

هذا الدليل يساعدك في حل مشاكل الاتصال بخادم SMTP الشائعة في التطبيق.
This guide helps you resolve common SMTP connection issues in the application.

## الأخطاء الشائعة - Common Errors

### 1. "SMTP Error: Could not connect to SMTP host"

**الأسباب المحتملة - Possible Causes:**
- خادم SMTP غير متاح أو معطل
- إعدادات المنفذ (Port) خاطئة
- مشاكل في الشبكة أو جدار الحماية
- إعدادات SSL/TLS خاطئة

**الحلول - Solutions:**

#### أ) التحقق من إعدادات الخادم - Check Server Settings
```bash
# اختبار الاتصال بالخادم
php artisan smtp:test

# اختبار مع إعدادات مخصصة
php artisan smtp:test --host=smtp.gmail.com --port=587 --encryption=tls
```

#### ب) إعدادات شائعة لمقدمي الخدمة - Common Provider Settings

**Gmail:**
```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

**Outlook/Hotmail:**
```
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@outlook.com
MAIL_PASSWORD=your-password
```

**Yahoo:**
```
MAIL_HOST=smtp.mail.yahoo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@yahoo.com
MAIL_PASSWORD=your-app-password
```

### 2. مشاكل شهادات SSL - SSL Certificate Issues

**الحل - Solution:**
```php
// في ملف .env
MAIL_VERIFY_PEER=false
MAIL_VERIFY_PEER_NAME=false
MAIL_ALLOW_SELF_SIGNED=true
```

### 3. مشاكل المهلة الزمنية - Timeout Issues

**الحل - Solution:**
```php
// زيادة المهلة الزمنية في .env
MAIL_TIMEOUT=60
```

## أدوات التشخيص - Diagnostic Tools

### 1. اختبار SMTP من سطر الأوامر - Command Line SMTP Test

```bash
# اختبار الاتصال الأساسي
php artisan smtp:test

# اختبار مع إرسال بريد إلكتروني
php artisan smtp:test --email=test@example.com

# اختبار مع إعدادات مخصصة
php artisan smtp:test --host=smtp.example.com --port=587 --username=user@example.com --password=password --encryption=tls --email=test@example.com
```

### 2. اختبار من واجهة الويب - Web Interface Test

```javascript
// اختبار الاتصال
fetch('/admin/settings/test-smtp', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
})
.then(response => response.json())
.then(data => console.log(data));

// إرسال بريد تجريبي
fetch('/admin/settings/send-test-email', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({
        test_email: 'test@example.com'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

## إعدادات متقدمة - Advanced Configuration

### 1. إعدادات SSL/TLS مخصصة - Custom SSL/TLS Settings

```php
// في config/mail.php
'stream_options' => [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
        'cafile' => '/path/to/ca-certificates.crt',
        'capath' => '/path/to/ca-certificates/',
    ],
],
```

### 2. إعدادات المصادقة - Authentication Settings

```php
// أنواع المصادقة المختلفة
'auth_mode' => 'login', // أو 'plain' أو 'cram-md5'
```

### 3. إعدادات النطاق المحلي - Local Domain Settings

```php
'local_domain' => env('MAIL_EHLO_DOMAIN', 'yourdomain.com'),
```

## استكشاف الأخطاء خطوة بخطوة - Step-by-Step Troubleshooting

### الخطوة 1: التحقق من الاتصال الأساسي
```bash
# اختبار DNS
nslookup smtp.yourdomain.com

# اختبار الاتصال بالمنفذ
telnet smtp.yourdomain.com 587
```

### الخطوة 2: التحقق من الإعدادات
```bash
php artisan smtp:test
```

### الخطوة 3: فحص السجلات
```bash
tail -f storage/logs/laravel.log
```

### الخطوة 4: اختبار إعدادات مختلفة
```bash
# جرب منافذ مختلفة
php artisan smtp:test --port=25
php artisan smtp:test --port=465 --encryption=ssl
php artisan smtp:test --port=587 --encryption=tls
```

## نصائح الأمان - Security Tips

1. **استخدم كلمات مرور التطبيق** - Use App Passwords
   - Gmail: إنشاء كلمة مرور تطبيق من إعدادات الحساب
   - Outlook: استخدام كلمة مرور التطبيق

2. **تفعيل المصادقة الثنائية** - Enable 2FA
   - تأكد من تفعيل المصادقة الثنائية على حساب البريد الإلكتروني

3. **استخدام TLS** - Use TLS
   - استخدم TLS بدلاً من SSL عند الإمكان

## الدعم الفني - Technical Support

إذا استمرت المشاكل، يرجى:
1. فحص سجلات الخادم
2. التواصل مع مقدم خدمة SMTP
3. التحقق من إعدادات جدار الحماية

---

## ملاحظات إضافية - Additional Notes

- تأكد من أن خادم الويب يمكنه الوصول للإنترنت
- بعض مقدمي الاستضافة يحجبون منافذ SMTP معينة
- استخدم منفذ 587 مع TLS للحصول على أفضل توافق
