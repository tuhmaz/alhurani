<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy Configuration
    |--------------------------------------------------------------------------
    |
    | هذا الملف يحتوي على إعدادات Content Security Policy (CSP) للتطبيق
    | يمكن تخصيص هذه الإعدادات حسب احتياجات المشروع
    |
    */

    /*
    | تفعيل CSP
    */
    'enabled' => env('CSP_ENABLED', true),

    /*
    | إعدادات CSP الأساسية
    */
    'directives' => [
        'default-src' => ["'self'"],
        
        'script-src' => [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            "https:",
            "http:",
            "https://fonts.googleapis.com",
            "https://fonts.gstatic.com",
            "https://www.gstatic.com/recaptcha/",
            "https://www.google.com/recaptcha/",
            "https://recaptcha.google.com",
        ],
        
        'style-src' => [
            "'self'",
            "'unsafe-inline'",
            "https:",
            "http:",
            "https://fonts.googleapis.com",
            "https://fonts.gstatic.com",
        ],
        
        'img-src' => [
            "'self'",
            "data:",
            "https:",
            "http:",
            "blob:",
        ],
        
        'font-src' => [
            "'self'",
            "data:",
            "https:",
            "http:",
            "https://fonts.googleapis.com",
            "https://fonts.gstatic.com",
        ],
        
        'connect-src' => [
            "'self'",
            "ws:",
            "wss:",
            "https:",
            "http:",
        ],
        
        'frame-src' => [
            "'self'",
            "https://www.google.com",
            "https://www.google.com/recaptcha/",
            "https://recaptcha.google.com",
        ],
        
        'media-src' => ["'self'"],
        'object-src' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'frame-ancestors' => ["'self'"],
    ],

    /*
    | إعدادات خاصة لصفحات المراقبة
    */
    'monitoring_overrides' => [
        'img-src' => ["'self'", "data:", "https:", "http:", "blob:", "*"],
        'connect-src' => ["'self'", "ws:", "wss:", "https:", "http:", "*"],
    ],

    /*
    | نطاقات موثوقة إضافية
    */
    'trusted_domains' => [
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'www.google.com',
        'www.gstatic.com',
        'recaptcha.google.com',
    ],
];
