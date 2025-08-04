# Troubleshooting Composer Install Errors

This guide specifically addresses the "sh: line 1: php: command not found" error when running `composer install` and related issues.

## Table of Contents
1. [Understanding the Error](#understanding-the-error)
2. [Immediate Solutions](#immediate-solutions)
3. [Detailed Troubleshooting Steps](#detailed-troubleshooting-steps)
4. [Platform-Specific Fixes](#platform-specific-fixes)
5. [Prevention for Future Deployments](#prevention-for-future-deployments)

## Understanding the Error

The error "sh: line 1: php: command not found" occurs when:
1. PHP is not installed on your system
2. PHP is installed but not in your system's PATH
3. The PHP executable has a different name (e.g., php8.2 instead of php)
4. You're using a shell that doesn't recognize the PATH

The additional messages:
- "APP_KEY already exists in .env file" - This is actually okay and indicates your .env is configured
- "Key generation completed successfully!" - This also shows partial success
- "Package discovery failed, but continuing..." - This is a direct result of PHP not being found

## Immediate Solutions

### Solution 1: Check if PHP is installed

```bash
# Try these commands to locate PHP
which php
whereis php
php --version
/usr/bin/php --version
/usr/local/bin/php --version
```

### Solution 2: Find where PHP is installed

```bash
# Search for PHP installation
find /usr -name "php*" 2>/dev/null | grep -E "php$" 
find /usr/local -name "php*" 2>/dev/null | grep -E "php$"
```

### Solution 3: Create a symbolic link if PHP exists with a different name

```bash
# If you find php with a version number (e.g., php8.2), create a symlink
sudo ln -s /usr/bin/php8.2 /usr/bin/php
# Or wherever your PHP executable is located
```

## Detailed Troubleshooting Steps

### Step 1: Verify Current Environment

```bash
# Check current PATH
echo $PATH

# Check available shells
echo $SHELL

# Check if PHP is installed anywhere
locate php | grep bin/php
```

### Step 2: Install PHP (if missing)

#### For Ubuntu/Debian:
```bash
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-common php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql php8.2-sqlite3 php8.2-gd php8.2-zip

# If php8.2 is not available, try:
sudo apt install php php-cli php-common php-mbstring php-xml php-curl php-mysql php-sqlite3 php-gd php-zip
```

#### For CentOS/RHEL:
```bash
# Enable EPEL and Remi repositories
sudo yum install epel-release
sudo yum install https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Install PHP 8.2
sudo yum module reset php
sudo yum module install php:remi-8.2

# Install required extensions
sudo yum install php php-cli php-common php-mbstring php-xml php-curl php-mysql php-gd php-zip
```

### Step 3: Add PHP to PATH (if installed but not in PATH)

```bash
# Find where PHP is installed
whereis php

# Add to PATH temporarily
export PATH=$PATH:/usr/local/php/bin

# Add to PATH permanently
echo 'export PATH=$PATH:/usr/local/php/bin' >> ~/.bashrc
source ~/.bashrc
```

### Step 4: Fix Composer Scripts

If PHP is installed but Composer scripts can't find it:

```bash
# Check composer configuration
composer config --list | grep php

# Update composer to use full PHP path
composer config --global process-timeout 2000
```

### Step 5: Run Composer with Explicit PHP Path

```bash
# Instead of 'composer install', try:
/usr/bin/php /usr/local/bin/composer install
# Or wherever your PHP and composer are located

# Find composer location
which composer
```

## Platform-Specific Fixes

### For Shared Hosting Environments

Many shared hosts have PHP installed but not in PATH:

```bash
# Find PHP in common locations
/usr/local/bin/php composer.phar install
/usr/bin/php8.2 composer.phar install
/opt/php82/bin/php composer.phar install

# Or use the full path to composer if available
/usr/local/bin/composer install
```

### For cPanel/Plesk Servers

```bash
# Try these paths commonly used in hosting panels
/opt/cpanel/ea-php82/root/usr/bin/php composer.phar install
/opt/plesk/php/8.2/bin/php composer.phar install
```

### For Docker Environments

If you're using Docker, ensure PHP is in your container:

```dockerfile
# In your Dockerfile
FROM php:8.2-fpm

# Install required extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```

## Prevention for Future Deployments

### 1. Create a Deployment Checklist

Before deploying, always verify:
- [ ] PHP is installed (`php --version`)
- [ ] PHP is in PATH (`which php`)
- [ ] Required PHP extensions are installed (`php -m`)
- [ ] Composer is installed (`composer --version`)
- [ ] Web server can access the files (permissions)
- [ ] Database connection works

### 2. Use a Deployment Script

Create a deployment script that checks prerequisites:

```bash
#!/bin/bash
# deploy.sh

echo "Checking prerequisites..."

# Check PHP
if ! command -v php &> /dev/null
then
    echo "ERROR: PHP is not installed or not in PATH"
    exit 1
else
    echo "PHP version: $(php --version | head -n 1)"
fi

# Check Composer
if ! command -v composer &> /dev/null
then
    echo "ERROR: Composer is not installed or not in PATH"
    exit 1
else
    echo "Composer version: $(composer --version)"
fi

# Proceed with installation
echo "Installing dependencies..."
composer install --optimize-autoloader --no-dev

echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear

echo "Deployment completed successfully!"
```

### 3. Use Environment Modules (for HPC/Advanced Environments)

```bash
# Load PHP module
module load php/8.2

# Verify
php --version

# Run composer
composer install
```

## Additional Diagnostic Commands

If you're still having issues, run these diagnostic commands:

```bash
# Check system information
uname -a

# Check environment variables
env | grep PATH

# Check what shell you're using
echo $SHELL

# Check if php is aliased
alias | grep php

# Check file permissions
ls -la /usr/bin/php*

# Check if composer is a script that calls php
head -n 5 $(which composer)
```

## Contact Support

If none of these solutions work:

1. Contact your hosting provider - they may have specific instructions for using PHP
2. Check your hosting control panel for PHP settings
3. Verify you're using the correct SSH access method
4. Check if you're in a chrooted environment with limited access

## Common Error Messages and Solutions

| Error Message | Solution |
|---------------|----------|
| `sh: line 1: php: command not found` | PHP not installed or not in PATH |
| `bash: php: command not found` | Same as above |
| `/usr/bin/env: php: No such file or directory` | Shebang line in script can't find PHP |
| `The requested PHP extension X is missing` | Install missing PHP extension |
| `Allowed memory size exhausted` | Increase PHP memory limit |

This troubleshooting guide should help you resolve the "php: command not found" error when running `composer install`. The key is to verify that PHP is properly installed and accessible in your system's PATH.
