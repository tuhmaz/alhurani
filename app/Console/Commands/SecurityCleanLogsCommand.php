<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SecurityLog;
use App\Services\SecurityLogService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SecurityCleanLogsCommand extends Command
{
    /**
     * اسم الأمر وتوصيفه
     *
     * @var string
     */
    protected $signature = 'security:clean-logs {--days=180 : عدد الأيام للاحتفاظ بالسجلات العادية} {--critical-days=365 : عدد الأيام للاحتفاظ بالسجلات الحرجة}';

    /**
     * وصف الأمر
     *
     * @var string
     */
    protected $description = 'تنظيف سجلات الأمان القديمة وفقًا لسياسة الاحتفاظ';

    /**
     * خدمة سجلات الأمان
     *
     * @var SecurityLogService
     */
    protected $securityLogService;

    /**
     * إنشاء مثيل جديد للأمر
     *
     * @param SecurityLogService $securityLogService
     * @return void
     */
    public function __construct(SecurityLogService $securityLogService)
    {
        parent::__construct();
        $this->securityLogService = $securityLogService;
    }

    /**
     * تنفيذ الأمر
     *
     * @return int
     */
    public function handle()
    {
        $this->info('بدء تنظيف سجلات الأمان القديمة...');

        try {
            // الحصول على عدد الأيام من الخيارات
            $normalDays = (int) $this->option('days');
            $criticalDays = (int) $this->option('critical-days');

            // التحقق من صحة القيم
            if ($normalDays < 30) {
                $this->warn('تحذير: عدد الأيام للاحتفاظ بالسجلات العادية أقل من 30 يومًا. سيتم استخدام 30 يومًا كحد أدنى.');
                $normalDays = 30;
            }

            if ($criticalDays < 90) {
                $this->warn('تحذير: عدد الأيام للاحتفاظ بالسجلات الحرجة أقل من 90 يومًا. سيتم استخدام 90 يومًا كحد أدنى.');
                $criticalDays = 90;
            }

            // حساب التواريخ
            $normalCutoff = Carbon::now()->subDays($normalDays);
            $criticalCutoff = Carbon::now()->subDays($criticalDays);

            // حذف السجلات العادية القديمة
            $normalCount = SecurityLog::where('created_at', '<', $normalCutoff)
                ->where('severity', '!=', SecurityLog::SEVERITY_LEVELS['CRITICAL'])
                ->where('severity', '!=', SecurityLog::SEVERITY_LEVELS['DANGER'])
                ->count();

            if ($normalCount > 0) {
                $this->info("سيتم حذف {$normalCount} من السجلات العادية القديمة (أقدم من {$normalDays} يومًا).");
                
                SecurityLog::where('created_at', '<', $normalCutoff)
                    ->where('severity', '!=', SecurityLog::SEVERITY_LEVELS['CRITICAL'])
                    ->where('severity', '!=', SecurityLog::SEVERITY_LEVELS['DANGER'])
                    ->chunk(1000, function ($logs) {
                        foreach ($logs as $log) {
                            $log->delete();
                        }
                    });
            } else {
                $this->info('لا توجد سجلات عادية قديمة للحذف.');
            }

            // حذف السجلات الحرجة القديمة
            $criticalCount = SecurityLog::where('created_at', '<', $criticalCutoff)
                ->whereIn('severity', [
                    SecurityLog::SEVERITY_LEVELS['CRITICAL'],
                    SecurityLog::SEVERITY_LEVELS['DANGER']
                ])
                ->count();

            if ($criticalCount > 0) {
                $this->info("سيتم حذف {$criticalCount} من السجلات الحرجة القديمة (أقدم من {$criticalDays} يومًا).");
                
                SecurityLog::where('created_at', '<', $criticalCutoff)
                    ->whereIn('severity', [
                        SecurityLog::SEVERITY_LEVELS['CRITICAL'],
                        SecurityLog::SEVERITY_LEVELS['DANGER']
                    ])
                    ->chunk(1000, function ($logs) {
                        foreach ($logs as $log) {
                            $log->delete();
                        }
                    });
            } else {
                $this->info('لا توجد سجلات حرجة قديمة للحذف.');
            }

            // تنظيف السجلات المحلولة
            $resolvedCount = SecurityLog::where('is_resolved', true)
                ->where('resolved_at', '<', Carbon::now()->subDays(90))
                ->count();

            if ($resolvedCount > 0) {
                $this->info("سيتم حذف {$resolvedCount} من السجلات المحلولة القديمة (تم حلها منذ أكثر من 90 يومًا).");
                
                SecurityLog::where('is_resolved', true)
                    ->where('resolved_at', '<', Carbon::now()->subDays(90))
                    ->chunk(1000, function ($logs) {
                        foreach ($logs as $log) {
                            $log->delete();
                        }
                    });
            } else {
                $this->info('لا توجد سجلات محلولة قديمة للحذف.');
            }

            // تسجيل إجراء التنظيف
            Log::channel('security')->info('تم تنفيذ تنظيف سجلات الأمان القديمة', [
                'normal_logs_deleted' => $normalCount,
                'critical_logs_deleted' => $criticalCount,
                'resolved_logs_deleted' => $resolvedCount,
                'normal_days_threshold' => $normalDays,
                'critical_days_threshold' => $criticalDays,
            ]);

            $this->info('تم إكمال تنظيف سجلات الأمان بنجاح.');
            return 0;
        } catch (\Exception $e) {
            $this->error('حدث خطأ أثناء تنظيف سجلات الأمان: ' . $e->getMessage());
            Log::error('فشل في تنظيف سجلات الأمان القديمة: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
