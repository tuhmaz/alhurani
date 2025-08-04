# Laravel Project Installation Guide

This guide provides detailed instructions for deploying your Laravel application to a production server.

## Table of Contents
1. [Server Requirements](#server-requirements)
2. [PHP Installation](#php-installation)
3. [Composer Installation](#composer-installation)
4. [Project Deployment](#project-deployment)
5. [Configuration](#configuration)
6. [File Permissions](#file-permissions)
7. [Additional Configuration](#additional-configuration)
8. [Troubleshooting](#troubleshooting)

## Server Requirements

- Ubuntu 20.04 LTS or higher / CentOS 7 or higher
- PHP 8.2 or higher
- MySQL 8.0 or MariaDB 10.6 or higher
- Apache 2.4 or Nginx
- Composer
- Git

## PHP Installation

### For Ubuntu/Debian:

```bash
# Update package manager
sudo apt update

# Install PHP 8.2 and required extensions
sudo apt install php8.2 php8.2-cli php8.2-common php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql php8.2-sqlite3 php8.2-gd php8.2-zip php8.2-bcmath php8.2-soap php8.2-intl php8.2-readline php8.2-imagick php8.2-gmp php8.2-redis

# Verify installation
php --version
```

### For CentOS/RHEL:

```bash
# Install EPEL repository
sudo yum install epel-release

# Install PHP 8.2 (using Remi repository)
sudo yum install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
sudo yum module reset php
sudo yum module install php:remi-8.2

# Install required extensions
sudo yum install php php-cli php-common php-mbstring php-xml php-curl php-mysql php-gd php-zip php-bcmath php-soap php-intl php-readline php-imagick php-gmp php-redis

# Verify installation
php --version
```

## Composer Installation

```bash
# Download Composer installer
curl -sS https://getcomposer.org/installer | php

# Move composer to a global location
sudo mv composer.phar /usr/local/bin/composer

# Make it executable
sudo chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

## Project Deployment

### 1. Clone or Upload Your Project

```bash
# Clone repository (if using Git)
git clone [your-repository-url] /var/www/your-project

# Or upload your files to the server using SFTP/SCP
```

### 2. Install Dependencies

```bash
# Navigate to project directory
cd /var/www/your-project

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies (if using Node.js)
npm install
npm run build
```

### 3. Environment Configuration

```bash
# Copy .env file
cp .env.example .env

# Generate application key
php artisan key:generate
```

## Configuration

### Database Setup

1. Create a database for your application:
```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

3. Run database migrations:
```bash
php artisan migrate --force
```

4. Run database seeders (if any):
```bash
php artisan db:seed --force
```

## File Permissions

Set proper permissions for Laravel directories:

```bash
# Set ownership to web server user (www-data for Apache/Nginx on Ubuntu)
sudo chown -R www-data:www-data /var/www/your-project/storage
sudo chown -R www-data:www-data /var/www/your-project/bootstrap/cache

# Set directory permissions
sudo chmod -R 755 /var/www/your-project/storage
sudo chmod -R 755 /var/www/your-project/bootstrap/cache

# For specific files that need to be writable
sudo chmod 664 /var/www/your-project/storage/logs/laravel.log
```

## Additional Configuration

### Apache Virtual Host

Create a virtual host configuration file:

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

### Nginx Configuration

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

### Enable SSL (Recommended)

Use Let's Encrypt to enable HTTPS:

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache  # For Apache
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Obtain SSL certificate
sudo certbot --apache -d your-domain.com        # For Apache
sudo certbot --nginx -d your-domain.com         # For Nginx
```

## Troubleshooting

### Common Issues and Solutions

1. **"sh: line 1: php: command not found"**
   - Ensure PHP is installed and in PATH
   - Check PHP installation with `which php`
   - Add PHP to PATH if needed:
     ```bash
     echo 'export PATH="/usr/local/php/bin:$PATH"' >> ~/.bashrc
     source ~/.bashrc
     ```

2. **Composer memory issues**
   ```bash
   COMPOSER_MEMORY_LIMIT=-1 composer install
   ```

3. **Permission denied errors**
   - Check file ownership with `ls -la`
   - Ensure web server user has proper access
   - Use `sudo chown -R www-data:www-data /var/www/your-project` on Ubuntu

4. **Class not found errors**
   ```bash
   composer dump-autoload
   php artisan config:clear
   php artisan cache:clear
   ```

5. **Database connection errors**
   - Verify database credentials in `.env`
   - Check if database service is running:
     ```bash
     sudo systemctl status mysql    # For MySQL
     sudo systemctl status mariadb  # For MariaDB
     ```

### Post-Installation Checklist

- [ ] Application loads without errors
- [ ] Database migrations run successfully
- [ ] User registration/login works
- [ ] All required services are running
- [ ] SSL certificate is properly configured
- [ ] Firewall allows HTTP/HTTPS traffic
- [ ] Backup system is configured
- [ ] Monitoring is set up

## Maintenance Commands

```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Update dependencies
composer update

# Check system status
php artisan up
php artisan down  # Maintenance mode

# Run queue workers (if using queues)
php artisan queue:work
```

## Security Recommendations

1. Set proper file permissions
2. Use HTTPS/SSL
3. Keep PHP and dependencies updated
4. Configure firewall (ufw/firewalld)
5. Regular backups
6. Monitor logs for suspicious activity
7. Use environment variables for sensitive data
8. Disable debug mode in production

This guide should help you successfully deploy your Laravel application to a production server. If you encounter any issues during the installation process, refer to the troubleshooting section or consult the Laravel documentation.
