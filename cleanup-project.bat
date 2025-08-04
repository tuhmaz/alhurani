@echo off
REM Laravel Project Cleanup Script for Production (Windows)

echo Starting project cleanup for production deployment...

REM Remove cache analysis and documentation files
echo Removing cache analysis and documentation files...
del /Q cache_analysis_report.md 2>nul
del /Q cache_improvements.md 2>nul
del /Q cache_problem_analysis.md 2>nul
del /Q cache_solution_final_report.md 2>nul
del /Q cache_solutions.md 2>nul
del /Q cache_system_analysis.md 2>nul
del /Q final_project_report.md 2>nul
del /Q final_report.md 2>nul
del /Q implementation_plan.md 2>nul
del /Q project_improvements.md 2>nul
del /Q project_issues_report.md 2>nul
del /Q project_solutions.md 2>nul
del /Q CLEANUP_REPORT.txt 2>nul

REM Remove test files
echo Removing test files...
del /Q test_advanced_cache_complete.php 2>nul
del /Q test_cache_detailed.php 2>nul
del /Q test_cache_final.php 2>nul
del /Q test_cache_last.php 2>nul
del /Q test_cache_new.php 2>nul
del /Q test_cache_registration.php 2>nul
del /Q test_cache_store.php 2>nul
del /Q test_cache.php 2>nul
del /Q test_service_provider.php 2>nul

REM Remove development configuration files
echo Removing development configuration files...
del /Q .eslintrc.json 2>nul
del /Q .eslintignore 2>nul
del /Q .prettierignore 2>nul
del /Q .prettierrc.json 2>nul
del /Q .node-version 2>nul
del /Q vite.config.js 2>nul
del /Q package.json 2>nul
del /Q yarn.lock 2>nul

REM Remove frontend library files
echo Removing frontend library files...
del /Q animate.min.css 2>nul
del /Q apexcharts.js 2>nul
del /Q boxicons.min.css 2>nul

REM Remove node_modules directory
echo Removing node_modules directory...
rmdir /S /Q node_modules 2>nul

REM Clear Laravel cache files
echo Clearing Laravel cache files...
rmdir /S /Q storage\framework\cache\data 2>nul
rmdir /S /Q storage\framework\views 2>nul
rmdir /S /Q storage\framework\sessions 2>nul
rmdir /S /Q storage\logs 2>nul

REM Recreate cache directories
mkdir storage\framework\cache\data 2>nul
mkdir storage\framework\views 2>nul
mkdir storage\framework\sessions 2>nul
mkdir storage\logs 2>nul

REM Remove Git directory
echo Removing Git directory...
rmdir /S /Q .git 2>nul

echo Project cleanup completed!
echo.
echo Files and directories removed:
echo 1. Cache analysis and documentation files
echo 2. Test files
echo 3. Development configuration files
echo 4. Node.js files and node_modules directory
echo 5. Frontend library files
echo 6. Git directory
echo 7. Laravel cache files
echo.
echo Files kept for production:
echo - .env (environment configuration)
echo - .htaccess (server configuration)
echo - artisan (Laravel CLI tool)
echo - composer.json/composer.lock (dependency management)
echo - All Laravel application directories
echo.
echo To run this cleanup manually, execute each command individually.
pause
