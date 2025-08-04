# دليل نشر مشروع الحراني التعليمي

## نظرة عامة
تم نقل المشروع بنجاح إلى GitHub Repository: https://github.com/tuhmaz/alhurani.git

## المتطلبات الأساسية

### متطلبات الخادم
- PHP 8.1 أو أحدث
- Composer
- Node.js و npm/yarn
- MySQL 8.0 أو أحدث
- Redis Server
- Nginx أو Apache
- SSL Certificate

### امتدادات PHP المطلوبة
```bash
php-fpm
php-mysql
php-redis
php-gd
php-xml
php-mbstring
php-curl
php-zip
php-intl
php-bcmath
```

## خطوات النشر

### 1. استنساخ المشروع
```bash
git clone https://github.com/tuhmaz/alhurani.git
cd alhurani
```

### 2. تثبيت التبعيات
```bash
# تثبيت تبعيات PHP
composer install --optimize-autoloader --no-dev

# تثبيت تبعيات Node.js
npm install
npm run build
```

### 3. إعداد البيئة
```bash
# نسخ ملف البيئة
cp .env.example .env

# توليد مفتاح التطبيق
php artisan key:generate
```

### 4. تكوين قاعدة البيانات
قم بتحديث ملف `.env` بإعدادات قاعدة البيانات:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=alhurani
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. تكوين Redis
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_SESSION_DB=3
```

### 6. تكوين الكاش
```env
CACHE_DRIVER=redis
CACHE_PREFIX=alhurani_cache_
CACHE_TTL=3600
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 7. تشغيل المايجريشن
```bash
php artisan migrate --force
php artisan db:seed --force
```

### 8. تحسين الأداء
```bash
# تحسين التطبيق للإنتاج
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# تحسين Composer
composer dump-autoload --optimize
```

### 9. إعداد الصلاحيات
```bash
# إعداد صلاحيات المجلدات
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### 10. إعداد Cron Jobs
أضف إلى crontab:
```bash
* * * * * cd /path/to/alhurani && php artisan schedule:run >> /dev/null 2>&1
```

## إعداد خادم الويب

### Nginx Configuration
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name alhurani.com www.alhurani.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name alhurani.com www.alhurani.com;
    root /var/www/alhurani/public;

    # SSL Configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## إعداد Redis

### تكوين Redis
```bash
# تحرير ملف التكوين
sudo nano /etc/redis/redis.conf

# الإعدادات المهمة
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### إعادة تشغيل Redis
```bash
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

## مراقبة النظام

### إعداد مراقبة الكاش
يمكن الوصول لصفحة مراقبة الكاش من:
```
https://alhurani.com/dashboard/redis/monitoring
```

### إعداد التنبيهات
قم بتكوين إعدادات التنبيهات في ملف `.env`:
```env
CACHE_MONITORING_ENABLED=true
CACHE_ALERTS_ENABLED=true
CACHE_ALERT_EMAIL=admin@alhurani.com
```

## النسخ الاحتياطي

### نسخ احتياطي لقاعدة البيانات
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u username -p database_name > backup_$DATE.sql
```

### نسخ احتياطي للملفات
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf alhurani_backup_$DATE.tar.gz /var/www/alhurani
```

## استكشاف الأخطاء

### فحص السجلات
```bash
# سجلات Laravel
tail -f storage/logs/laravel.log

# سجلات Nginx
tail -f /var/log/nginx/error.log

# سجلات Redis
tail -f /var/log/redis/redis-server.log
```

### أوامر مفيدة للصيانة
```bash
# مسح الكاش
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# إعادة تحسين
php artisan optimize

# فحص حالة النظام
php artisan about
```

## الأمان

### إعدادات الأمان المهمة
1. تأكد من تشفير SSL
2. إعداد Firewall
3. تحديث النظام بانتظام
4. مراقبة السجلات الأمنية
5. استخدام كلمات مرور قوية

### مراقبة الأمان
يمكن الوصول لصفحة مراقبة الأمان من:
```
https://alhurani.com/dashboard/security/monitor
```

## الدعم والصيانة

### تحديث المشروع
```bash
git pull origin main
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan migrate --force
php artisan optimize
```

### مراقبة الأداء
- استخدم صفحة مراقبة الكاش لتتبع الأداء
- راقب استخدام الذاكرة والمعالج
- تحقق من سرعة الاستجابة

## معلومات الاتصال
للدعم التقني، يرجى التواصل مع فريق التطوير.

---
تم إنشاء هذا الدليل لضمان نشر ناجح وآمن لمشروع الحراني التعليمي.
