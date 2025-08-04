#!/bin/bash
# Laravel Project Cleanup Script for Production

echo "Starting project cleanup for production deployment..."

# Remove cache analysis and documentation files
echo "Removing cache analysis and documentation files..."
rm -f cache_analysis_report.md
rm -f cache_improvements.md
rm -f cache_problem_analysis.md
rm -f cache_solution_final_report.md
rm -f cache_solutions.md
rm -f cache_system_analysis.md
rm -f final_project_report.md
rm -f final_report.md
rm -f implementation_plan.md
rm -f project_improvements.md
rm -f project_issues_report.md
rm -f project_solutions.md
rm -f CLEANUP_REPORT.txt

# Remove test files
echo "Removing test files..."
rm -f test_advanced_cache_complete.php
rm -f test_cache_detailed.php
rm -f test_cache_final.php
rm -f test_cache_last.php
rm -f test_cache_new.php
rm -f test_cache_registration.php
rm -f test_cache_store.php
rm -f test_cache.php
rm -f test_service_provider.php

# Remove development configuration files
echo "Removing development configuration files..."
rm -f .eslintrc.json
rm -f .eslintignore
rm -f .prettierignore
rm -f .prettierrc.json
rm -f .node-version
rm -f vite.config.js
rm -f package.json
rm -f yarn.lock

# Remove frontend library files (these should be loaded via CDN or built assets)
echo "Removing frontend library files..."
rm -f animate.min.css
rm -f apexcharts.js
rm -f boxicons.min.css

# Remove node_modules directory
echo "Removing node_modules directory..."
rm -rf node_modules

# Clear Laravel cache files (these will be regenerated on the server)
echo "Clearing Laravel cache files..."
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*
rm -rf storage/logs/*

# Remove Git directory (not needed for production)
echo "Removing Git directory..."
rm -rf .git

# Note: We're keeping these important files:
# - .env (contains environment configuration)
# - .htaccess (contains server configuration)
# - artisan (Laravel CLI tool)
# - composer.json/composer.lock (for dependency management)
# - All Laravel application directories (app, bootstrap, config, database, etc.)

echo "Project cleanup completed!"
echo ""
echo "Files and directories removed:"
echo "1. Cache analysis and documentation files"
echo "2. Test files"
echo "3. Development configuration files"
echo "4. Node.js files and node_modules directory"
echo "5. Frontend library files"
echo "6. Git directory"
echo "7. Laravel cache files"
echo ""
echo "Files kept for production:"
echo "- .env (environment configuration)"
echo "- .htaccess (server configuration)"
echo "- artisan (Laravel CLI tool)"
echo "- composer.json/composer.lock (dependency management)"
echo "- All Laravel application directories"
echo ""
echo "To run this cleanup manually, execute each 'rm' command individually."
