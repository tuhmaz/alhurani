# Laravel Project Deployment Instructions

## Cleaned Project Deployment Guide

This document provides step-by-step instructions for deploying your cleaned Laravel project to a production server.

## Prerequisites

1. A server with PHP 8.2 or higher
2. Composer installed
3. A database (MySQL, PostgreSQL, or SQLite)
4. SSH access to your server

## Deployment Steps

### 1. Upload Project Files

Upload the cleaned project files to your server using SFTP, SCP, or Git:

```bash
# Using Git (if repository is available)
git clone [your-repository-url] /var/www/your-project

# Using SCP
scp -r /path/to/local/project user@server:/var/www/your-project

# Using rsync
rsync -avz /path/to/local/project/ user@server:/var/www/your-project/
```

### 2. Install PHP Dependencies

Navigate to your project directory and install PHP dependencies:

```bash
cd /var/www/your-project

# Install production dependencies only
composer install --optimize-autoloader --no-dev

# If you encounter the "php: command not found" error:
# Check PHP installation
which php
php --version

# If PHP is not found, install it:
# Ubuntu/Debian:
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-common php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql php8.2-sqlite3 php8.2-gd php8.2-zip

# CentOS/RHEL:
sudo yum install epel-release
sudo yum install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
sudo yum module reset php
sudo yum module install php:remi-8.2
```

### 3. Configure Environment

Ensure your `.env` file is properly configured for production:

```bash
# Check if .env exists
ls -la .env

# If not, create it from example
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env with production settings
nano .env
```

Important `.env` settings for production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_production_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 4. Set File Permissions

Set proper permissions for Laravel directories:

```bash
# Set ownership to web server user (www-data for Apache/Nginx on Ubuntu)
sudo chown -R www-data:www-data storage bootstrap/cache

# Set directory permissions
sudo chmod -R 755 storage bootstrap/cache

# For specific files that need to be writable
sudo chmod 664 storage/logs/laravel.log
```

### 5. Run Database Migrations

If this is a new installation or you have database migrations:

```bash
# Run migrations
php artisan migrate --force

# Run seeders (if any)
php artisan db:seed --force
```

### 6. Configure Web Server

#### For Apache:

Create a virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/your-project/public

    <Directory /var/www/your-project/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/your-project_error.log
    CustomLog ${APACHE_LOG_DIR}/your-project_access.log combined
</VirtualHost>
```

Enable the site and restart Apache:
```bash
sudo a2ensite your-project.conf
sudo systemctl reload apache2
```

#### For Nginx:

Create a server block configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/your-project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Test and reload Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Enable SSL (Recommended)

Use Let's Encrypt to enable HTTPS:

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache  # For Apache
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Obtain SSL certificate
sudo certbot --apache -d your-domain.com        # For Apache
sudo certbot --nginx -d your-domain.com         # For Nginx
```

### 8. Optimize for Production

Run Laravel optimization commands:

```bash
# Clear and cache configurations
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Clear application cache
php artisan cache:clear
```

### 9. Set Up Cron Jobs

Configure Laravel's task scheduler by adding this cron entry:

```bash
# Edit crontab
sudo crontab -u www-data -e

# Add this line
* * * * * cd /var/www/your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 10. Monitor and Maintain

Monitor your application logs:
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check web server logs
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

## Troubleshooting Common Issues

### "sh: line 1: php: command not found"

This error occurs when PHP is not installed or not in PATH:

1. Check if PHP is installed:
   ```bash
   which php
   php --version
   ```

2. If not installed, install PHP:
   ```bash
   # Ubuntu/Debian
   sudo apt install php8.2 php8.2-cli php8.2-common php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql php8.2-sqlite3 php8.2-gd php8.2-zip

   # CentOS/RHEL
   sudo yum install php php-cli php-common php-mbstring php-xml php-curl php-mysql php-gd php-zip
   ```

3. Add PHP to PATH if needed:
   ```bash
   echo 'export PATH="/usr/local/php/bin:$PATH"' >> ~/.bashrc
   source ~/.bashrc
   ```

### Permission Issues

If you encounter permission errors:

1. Check current permissions:
   ```bash
   ls -la storage bootstrap/cache
   ```

2. Set correct permissions:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 755 storage bootstrap/cache
   ```

## Post-Deployment Checklist

- [ ] Application loads without errors
- [ ] Database connections work
- [ ] User registration/login functions
- [ ] All required services are running
- [ ] SSL certificate is properly configured
- [ ] Firewall allows HTTP/HTTPS traffic
- [ ] Backup system is configured
- [ ] Monitoring is set up

## Maintenance Commands

```bash
# Update dependencies
composer update

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check system status
php artisan up
php artisan down  # Maintenance mode

# Run queue workers (if using queues)
php artisan queue:work
```

Following these instructions should result in a successful deployment of your Laravel application to a production environment.
