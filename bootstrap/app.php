<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\SwitchDatabase;
use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\UpdateUserLastActivity;
use App\Http\Middleware\PerformanceOptimizer;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VisitorTrackingMiddleware;
use App\Http\Middleware\ImageOptimizer;
use App\Http\Middleware\ApiProtection;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\IpSessionGuard;
use App\Http\Middleware\AbsoluteSessionTimeout;
use App\Http\Middleware\SecureFileUpload;
use App\Http\Middleware\SecurityScanMiddleware;
use App\Http\Middleware\RequestMonitorMiddleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    // إضافة وسطاء الجلسة وCSRF الأساسية
    $middleware->web(EncryptCookies::class);
    $middleware->web(AddQueuedCookiesToResponse::class);
    $middleware->web(StartSession::class);
    $middleware->web(ShareErrorsFromSession::class);
    $middleware->web(VerifyCsrfToken::class);
    $middleware->web(IpSessionGuard::class);
    $middleware->web(AbsoluteSessionTimeout::class . ':240'); // مهلة مطلقة 4 ساعات
    
    // تطبيق middleware على مسارات الويب
    $middleware->web(LocaleMiddleware::class);
    $middleware->web(CompressResponse::class);
    $middleware->web(ImageOptimizer::class);
    $middleware->web(UpdateUserLastActivity::class);
    $middleware->web(PerformanceOptimizer::class);
    $middleware->web(SecurityHeaders::class);
    $middleware->web(SwitchDatabase::class);
    $middleware->web(VisitorTrackingMiddleware::class);
    $middleware->web(SecurityScanMiddleware::class);
    $middleware->web(RequestMonitorMiddleware::class);
    
    // تطبيق middleware على مسارات API
    $middleware->api(ApiProtection::class);
    $middleware->api(SecurityScanMiddleware::class);
    
    // تسجيل وسيط تأمين رفع الملفات
    $middleware->alias([
        'SecureFileUpload' => SecureFileUpload::class
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })->create();
