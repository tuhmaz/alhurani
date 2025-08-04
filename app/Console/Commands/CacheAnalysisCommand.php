<?php

namespace App\Console\Commands;

use App\Services\CacheAnalyticsService;
use App\Services\CacheOptimizationService;
use Illuminate\Console\Command;

class CacheAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:analyze {--hours=24 : Hours to analyze} {--optimize : Apply optimizations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze cache performance and provide optimization recommendations';

    protected $analyticsService;
    protected $optimizationService;

    public function __construct(CacheAnalyticsService $analyticsService, CacheOptimizationService $optimizationService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
        $this->optimizationService = $optimizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $optimize = $this->option('optimize');

        $this->info("🔍 تحليل أداء الكاش للـ {$hours} ساعة الماضية...");
        
        // تقرير الأداء
        $report = $this->analyticsService->generatePerformanceReport($hours);
        
        $this->displayPerformanceReport($report);
        
        // توصيات التحسين
        $this->info("\n📊 توصيات التحسين:");
        $recommendations = $this->analyticsService->optimizeCache();
        
        if (empty($recommendations)) {
            $this->info("✅ لا توجد مشاكل في الأداء حاليًا");
        } else {
            foreach ($recommendations as $recommendation) {
                $this->displayRecommendation($recommendation);
            }
        }
        
        // تطبيق التحسينات
        if ($optimize && !empty($recommendations)) {
            if ($this->confirm('هل تريد تطبيق التحسينات المقترحة؟')) {
                $this->applyOptimizations($recommendations);
            }
        }
        
        return 0;
    }

    private function displayPerformanceReport($report)
    {
        $summary = $report['summary'];
        
        $this->info("\n📈 ملخص الأداء:");
        $this->table(
            ['المؤشر', 'القيمة'],
            [
                ['إجمالي العمليات', number_format($summary['total_operations'])],
                ['نجاح الكاش', number_format($summary['cache_hits'])],
                ['فشل الكاش', number_format($summary['cache_misses'])],
                ['نسبة النجاح', $summary['hit_ratio'] . '%'],
                ['متوسط زمن الاستجابة', round($summary['avg_response_time'], 2) . ' ms'],
                ['استهلاك الذاكرة', number_format($summary['total_memory_usage']) . ' KB'],
            ]
        );

        if (!empty($report['top_keys'])) {
            $this->info("\n🔑 أهم المفاتيح:");
            $this->table(
                ['المفتاح', 'العمليات', 'متوسط الزمن', 'الذاكرة'],
                collect($report['top_keys'])->map(function ($key) {
                    return [
                        substr($key['key'], 0, 50) . '...',
                        $key['operations'],
                        round($key['avg_response_time'], 2) . ' ms',
                        number_format($key['total_memory']) . ' KB'
                    ];
                })->toArray()
            );
        }
    }

    private function displayRecommendation($recommendation)
    {
        $icon = match($recommendation['type']) {
            'cleanup' => '🧹',
            'performance' => '⚡',
            'memory' => '💾',
            default => '📋'
        };
        
        $this->warn("{$icon} {$recommendation['message']}");
        
        if (!empty($recommendation['keys'])) {
            foreach (array_slice($recommendation['keys'], 0, 3) as $key) {
                if (is_array($key)) {
                    $this->line("  - " . ($key['key'] ?? $key['cache_key'] ?? 'Unknown'));
                } else {
                    $this->line("  - " . $key);
                }
            }
        }
    }

    private function applyOptimizations($recommendations)
    {
        foreach ($recommendations as $recommendation) {
            switch ($recommendation['action']) {
                case 'delete_unused_keys':
                    $this->info("🧹 حذف المفاتيح غير المستخدمة...");
                    $this->optimizationService->consolidateConnections();
                    break;
                    
                case 'optimize_slow_keys':
                    $this->info("⚡ تحسين المفاتيح البطيئة...");
                    $this->optimizationService->optimizeRedisConnection();
                    break;
                    
                case 'optimize_memory_usage':
                    $this->info("💾 تحسين استهلاك الذاكرة...");
                    $this->optimizationService->setupOptimizedCache();
                    break;
            }
        }
        
        $this->info("✅ تم تطبيق التحسينات بنجاح");
    }
}
