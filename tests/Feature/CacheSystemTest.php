<?php

namespace Tests\Feature;

use App\Models\CachePerformanceLog;
use App\Services\CacheAnalyticsService;
use App\Services\CacheMonitoringService;
use App\Services\CacheOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CacheSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $cacheMonitoring;
    protected $cacheOptimization;
    protected $cacheAnalytics;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheMonitoring = app(CacheMonitoringService::class);
        $this->cacheOptimization = app(CacheOptimizationService::class);
        $this->cacheAnalytics = app(CacheAnalyticsService::class);
    }

    /** @test */
    public function it_can_connect_to_redis()
    {
        $redis = Redis::connection('cache');
        $this->assertTrue($redis->ping());
    }

    /** @test */
    public function it_can_store_and_retrieve_cache_data()
    {
        $key = 'test_cache_key';
        $value = 'test_cache_value';
        
        Cache::put($key, $value, 3600);
        $retrieved = Cache::get($key);
        
        $this->assertEquals($value, $retrieved);
    }

    /** @test */
    public function it_logs_cache_operations()
    {
        $key = 'test_log_key';
        $operation = 'test_operation';
        
        $this->cacheMonitoring->logCacheOperation($key, $operation, 100, 512);
        
        $this->assertDatabaseHas('redis_logs', [
            'key' => $key,
            'action' => $operation,
        ]);
    }

    /** @test */
    public function it_gets_cache_statistics()
    {
        $stats = $this->cacheMonitoring->getCacheStats();
        
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('keys_count', $stats);
        $this->assertArrayHasKey('hit_ratio', $stats);
        $this->assertArrayHasKey('avg_ttl', $stats);
    }

    /** @test */
    public function it_optimizes_redis_connection()
    {
        $result = $this->cacheOptimization->optimizeRedisConnection();
        $this->assertTrue($result);
    }

    /** @test */
    public function it_consolidates_connections()
    {
        $result = $this->cacheOptimization->consolidateConnections();
        $this->assertTrue($result);
    }

    /** @test */
    public function it_generates_performance_report()
    {
        // إنشاء بيانات تجريبية
        CachePerformanceLog::create([
            'cache_key' => 'test_key_1',
            'operation' => 'hit',
            'response_time_ms' => 50,
            'memory_usage_kb' => 256,
            'ttl' => 3600,
            'user_id' => '1',
        ]);

        CachePerformanceLog::create([
            'cache_key' => 'test_key_2',
            'operation' => 'miss',
            'response_time_ms' => 100,
            'memory_usage_kb' => 512,
            'ttl' => 3600,
            'user_id' => '1',
        ]);

        $report = $this->cacheAnalytics->generatePerformanceReport(24);
        
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('top_keys', $report);
        $this->assertArrayHasKey('performance_trends', $report);
        $this->assertArrayHasKey('memory_analysis', $report);
        
        $this->assertEquals(2, $report['summary']['total_operations']);
        $this->assertEquals(1, $report['summary']['cache_hits']);
        $this->assertEquals(1, $report['summary']['cache_misses']);
        $this->assertEquals(50, $report['summary']['hit_ratio']);
    }

    /** @test */
    public function it_identifies_problematic_keys()
    {
        // إنشاء مفتاح بطيء
        CachePerformanceLog::create([
            'cache_key' => 'slow_key',
            'operation' => 'hit',
            'response_time_ms' => 200,
            'memory_usage_kb' => 256,
            'ttl' => 3600,
            'user_id' => '1',
        ]);

        $problematicKeys = $this->cacheAnalytics->identifyProblematicKeys(150);
        
        $this->assertCount(1, $problematicKeys);
        $this->assertEquals('slow_key', $problematicKeys->first()->cache_key);
    }

    /** @test */
    public function it_provides_optimization_recommendations()
    {
        // إنشاء بيانات تجريبية للتوصيات
        CachePerformanceLog::create([
            'cache_key' => 'memory_heavy_key',
            'operation' => 'hit',
            'response_time_ms' => 50,
            'memory_usage_kb' => 2048, // 2MB
            'ttl' => 3600,
            'user_id' => '1',
        ]);

        $recommendations = $this->cacheAnalytics->optimizeCache();
        
        $this->assertIsArray($recommendations);
        
        if (!empty($recommendations)) {
            $this->assertArrayHasKey('type', $recommendations[0]);
            $this->assertArrayHasKey('message', $recommendations[0]);
            $this->assertArrayHasKey('action', $recommendations[0]);
        }
    }

    /** @test */
    public function it_handles_cache_performance_logging()
    {
        $log = CachePerformanceLog::create([
            'cache_key' => 'performance_test_key',
            'operation' => 'set',
            'response_time_ms' => 75,
            'memory_usage_kb' => 128,
            'ttl' => 1800,
            'user_id' => 'test_user',
        ]);

        $this->assertDatabaseHas('cache_performance_logs', [
            'cache_key' => 'performance_test_key',
            'operation' => 'set',
            'response_time_ms' => 75,
        ]);

        // اختبار النطاقات
        $recentLogs = CachePerformanceLog::recent(1)->get();
        $this->assertCount(1, $recentLogs);

        $hitLogs = CachePerformanceLog::byOperation('set')->get();
        $this->assertCount(1, $hitLogs);
    }

    /** @test */
    public function it_calculates_memory_analysis_correctly()
    {
        // إنشاء بيانات متنوعة للذاكرة
        CachePerformanceLog::create([
            'cache_key' => 'small_key',
            'operation' => 'hit',
            'response_time_ms' => 25,
            'memory_usage_kb' => 64,
            'ttl' => 3600,
            'user_id' => '1',
        ]);

        CachePerformanceLog::create([
            'cache_key' => 'large_key',
            'operation' => 'hit',
            'response_time_ms' => 50,
            'memory_usage_kb' => 2048,
            'ttl' => 3600,
            'user_id' => '1',
        ]);

        $report = $this->cacheAnalytics->generatePerformanceReport(24);
        $memoryAnalysis = $report['memory_analysis'];

        $this->assertArrayHasKey('total_memory_usage', $memoryAnalysis);
        $this->assertArrayHasKey('high_memory_keys', $memoryAnalysis);
        $this->assertEquals(2112, $memoryAnalysis['total_memory_usage']); // 64 + 2048
    }

    /** @test */
    public function it_handles_cache_cleanup_operations()
    {
        // إضافة بعض البيانات للكاش
        Cache::put('test_key_1', 'value1', 3600);
        Cache::put('test_key_2', 'value2', 3600);
        
        // تنظيف الكاش
        $result = $this->cacheOptimization->optimizeRedisConnection();
        
        $this->assertTrue($result);
        
        // التحقق من أن الكاش تم تنظيفه
        $this->assertNull(Cache::get('test_key_1'));
        $this->assertNull(Cache::get('test_key_2'));
    }
}
