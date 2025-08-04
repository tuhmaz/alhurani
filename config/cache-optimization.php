<?php

return [
    /*
    |--------------------------------------------------------------------------
    | إعدادات تحسين نظام الكاش
    |--------------------------------------------------------------------------
    |
    | هذا الملف يحتوي على إعدادات متقدمة لتحسين أداء نظام الكاش
    |
    */

    'redis' => [
        // إعدادات Redis المتقدمة
        'connection' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_CACHE_DB', 1),
            'read_write_timeout' => 60,
            'persistent' => env('REDIS_PERSISTENT', false),
            'prefix' => env('REDIS_PREFIX', 'alemedu_'),
        ],

        // إعدادات التحسين
        'optimization' => [
            'max_memory' => env('REDIS_MAX_MEMORY', '256mb'),
            'max_memory_policy' => env('REDIS_MAX_MEMORY_POLICY', 'allkeys-lru'),
            'tcp_keepalive' => env('REDIS_TCP_KEEPALIVE', 60),
            'timeout' => env('REDIS_TIMEOUT', 300),
        ],

        // إعدادات التخزين المؤقت
        'cache' => [
            'ttl' => [
                'short' => env('CACHE_TTL_SHORT', 300),      // 5 دقائق
                'medium' => env('CACHE_TTL_MEDIUM', 3600),   // ساعة
                'long' => env('CACHE_TTL_LONG', 86400),      // يوم
                'forever' => env('CACHE_TTL_FOREVER', 604800), // أسبوع
            ],
            'prefixes' => [
                'data' => 'cache:data:',
                'view' => 'cache:view:',
                'route' => 'cache:route:',
                'config' => 'cache:config:',
                'query' => 'cache:query:',
                'api' => 'cache:api:',
            ],
        ],
    ],

    'monitoring' => [
        // تمكين مراقبة الأداء
        'enabled' => env('CACHE_MONITORING_ENABLED', true),

        // تتبع الأداء
        'performance_tracking' => [
            'enabled' => env('PERFORMANCE_TRACKING', true),
            'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // مللي ثانية
            'memory_threshold' => env('MEMORY_THRESHOLD', 80), // نسبة مئوية
            'hit_ratio_threshold' => env('HIT_RATIO_THRESHOLD', 80), // نسبة مئوية
        ],

        // تنبيهات الأداء
        'alerts' => [
            'enabled' => env('CACHE_ALERTS_ENABLED', true),
            'email' => env('CACHE_ALERT_EMAIL', 'admin@alemancenter.com'),
            'slack_webhook' => env('CACHE_SLACK_WEBHOOK'),
        ],
    ],

    'cleanup' => [
        // إعدادات التنظيف التلقائي
        'enabled' => env('CACHE_CLEANUP_ENABLED', true),
        'schedule' => env('CACHE_CLEANUP_SCHEDULE', 'daily'), // daily, hourly, weekly
        'retention_days' => env('CACHE_RETENTION_DAYS', 7),
        'batch_size' => env('CACHE_CLEANUP_BATCH_SIZE', 1000),
        
        // أنواع المفاتيح للتنظيف
        'cleanup_types' => [
            'expired' => true,
            'unused' => true,
            'orphaned' => true,
        ],
    ],

    'optimization' => [
        // تمكين التحسينات
        'enabled' => env('CACHE_OPTIMIZATION_ENABLED', true),

        // إعدادات التحسين
        'strategies' => [
            'compression' => env('CACHE_COMPRESSION_ENABLED', true),
            'serialization' => env('CACHE_SERIALIZATION_ENABLED', true),
            'tagging' => env('CACHE_TAGGING_ENABLED', true),
            'locking' => env('CACHE_LOCKING_ENABLED', true),
        ],

        // حدود الأداء
        'limits' => [
            'max_key_length' => env('CACHE_MAX_KEY_LENGTH', 250),
            'max_value_size' => env('CACHE_MAX_VALUE_SIZE', 1048576), // 1MB
            'max_tags' => env('CACHE_MAX_TAGS', 10),
        ],
    ],

    'logging' => [
        // تمكين التسجيل
        'enabled' => env('CACHE_LOGGING_ENABLED', true),
        
        // مستوى التسجيل
        'level' => env('CACHE_LOG_LEVEL', 'info'),
        
        // أنواع الأحداث للتسجيل
        'events' => [
            'cache_hit' => true,
            'cache_miss' => true,
            'cache_write' => true,
            'cache_delete' => true,
            'cache_clear' => true,
            'cache_error' => true,
        ],
        
        // تفاصيل التسجيل
        'details' => [
            'include_key' => true,
            'include_value_size' => true,
            'include_duration' => true,
            'include_memory_usage' => true,
        ],
    ],

    'advanced' => [
        // إعدادات متقدمة
        'pipeline' => env('CACHE_PIPELINE_ENABLED', true),
        'clustering' => env('CACHE_CLUSTERING_ENABLED', false),
        'replication' => env('CACHE_REPLICATION_ENABLED', false),
        
        // إعدادات التوزيع
        'sharding' => [
            'enabled' => env('CACHE_SHARDING_ENABLED', false),
            'strategy' => env('CACHE_SHARDING_STRATEGY', 'consistent'),
            'nodes' => explode(',', env('CACHE_SHARDING_NODES', '')),
        ],
        
        // إعدادات التخزين المؤقت للاستعلامات
        'query_cache' => [
            'enabled' => env('QUERY_CACHE_ENABLED', true),
            'ttl' => env('QUERY_CACHE_TTL', 3600),
            'blacklist' => explode(',', env('QUERY_CACHE_BLACKLIST', '')),
        ],
    ],
];
