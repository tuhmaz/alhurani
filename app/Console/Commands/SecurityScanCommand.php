<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SecurityUpdateService;
use App\Services\SecurityAlertService;
use App\Models\SecurityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SecurityScanCommand extends Command
{
    /**
     * اسم الأمر وتوصيفه
     *
     * @var string
     */
    protected $signature = 'security:scan {--notify : إرسال إشعار بنتائج الفحص}';

    /**
     * وصف الأمر
     *
     * @var string
     */
    protected $description = 'تنفيذ فحص أمني شامل للتطبيق والتحقق من وجود تحديثات أمنية';

    /**
     * خدمة تحديثات الأمان
     *
     * @var SecurityUpdateService
     */
    protected $securityUpdateService;

    /**
     * خدمة تنبيهات الأمان
     *
     * @var SecurityAlertService
     */
    protected $securityAlertService;

    /**
     * إنشاء مثيل جديد للأمر
     *
     * @param SecurityUpdateService $securityUpdateService
     * @param SecurityAlertService $securityAlertService
     * @return void
     */
    public function __construct(SecurityUpdateService $securityUpdateService, SecurityAlertService $securityAlertService)
    {
        parent::__construct();
        $this->securityUpdateService = $securityUpdateService;
        $this->securityAlertService = $securityAlertService;
    }

    /**
     * تنفيذ الأمر
     *
     * @return int
     */
    public function handle()
    {
        $this->info('بدء الفحص الأمني الشامل...');

        try {
            // الحصول على التقرير الأمني الشامل
            $report = $this->securityUpdateService->getSecurityReport();

            // عرض نتائج الفحص
            $this->displayResults($report);

            // تسجيل نتائج الفحص
            $this->logScanResults($report);

            // إرسال إشعار بالنتائج إذا تم تحديد الخيار
            if ($this->option('notify') || $report['scan']['total_issues'] > 0 || $report['updates']['critical_updates']) {
                $this->sendNotification($report);
            }

            $this->info('تم إكمال الفحص الأمني بنجاح.');
            return 0;
        } catch (\Exception $e) {
            $this->error('حدث خطأ أثناء تنفيذ الفحص الأمني: ' . $e->getMessage());
            Log::error('فشل في تنفيذ الفحص الأمني: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * عرض نتائج الفحص
     *
     * @param array $report
     * @return void
     */
    protected function displayResults(array $report)
    {
        // عرض درجة الأمان الإجمالية
        $this->info('درجة الأمان الإجمالية: ' . $report['overall_security_score'] . '/100');

        // عرض معلومات التحديثات
        $this->line('');
        $this->info('معلومات التحديثات:');
        $this->line('إصدار Laravel الحالي: ' . $report['updates']['laravel_version']['current_version']);
        $this->line('أحدث إصدار Laravel: ' . $report['updates']['laravel_version']['latest_version']);
        $this->line('إصدار PHP الحالي: ' . $report['updates']['php_version']['current_version']);
        $this->line('أحدث إصدار PHP: ' . $report['updates']['php_version']['latest_version']);
        $this->line('عدد الحزم القديمة: ' . count($report['updates']['packages']['outdated']));
        $this->line('عدد تحديثات الأمان: ' . count($report['updates']['packages']['security_updates']));

        // عرض نتائج الفحص الأمني
        $this->line('');
        $this->info('نتائج الفحص الأمني:');
        $this->line('إجمالي المشكلات: ' . $report['scan']['total_issues']);

        if (!empty($report['scan']['vulnerabilities'])) {
            $this->line('');
            $this->info('الثغرات الأمنية المكتشفة:');
            $this->table(
                ['النوع', 'الخطورة', 'الوصف', 'التوصية'],
                collect($report['scan']['vulnerabilities'])->map(function ($issue) {
                    return [
                        $issue['type'],
                        $issue['severity'],
                        $issue['description'],
                        $issue['recommendation']
                    ];
                })->toArray()
            );
        }

        // عرض إحصائيات سجلات الأمان
        $this->line('');
        $this->info('إحصائيات سجلات الأمان:');
        $this->line('إجمالي السجلات: ' . $report['logs']['total_logs']);
        $this->line('السجلات الحرجة: ' . $report['logs']['critical_logs']);
        $this->line('المشكلات غير المحلولة: ' . $report['logs']['unresolved_issues']);
        $this->line('النشاط المشبوه الأخير: ' . $report['logs']['recent_suspicious_activity']);
    }

    /**
     * تسجيل نتائج الفحص
     *
     * @param array $report
     * @return void
     */
    protected function logScanResults(array $report)
    {
        // تسجيل نتائج الفحص في سجل الأمان
        $severity = $report['overall_security_score'] < 70 ? SecurityLog::SEVERITY_LEVELS['DANGER'] :
                   ($report['overall_security_score'] < 90 ? SecurityLog::SEVERITY_LEVELS['WARNING'] : SecurityLog::SEVERITY_LEVELS['INFO']);

        $description = 'تم إجراء فحص أمني شامل. درجة الأمان: ' . $report['overall_security_score'] . '/100. ';
        
        if ($report['updates']['critical_updates']) {
            $description .= 'تم العثور على تحديثات أمنية حرجة. ';
        }
        
        if ($report['scan']['total_issues'] > 0) {
            $description .= 'تم اكتشاف ' . $report['scan']['total_issues'] . ' مشكلة أمنية. ';
        }

        SecurityLog::create([
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => 'SecurityScanCommand',
            'event_type' => 'security_scan',
            'description' => $description,
            'user_id' => null,
            'route' => 'console:security:scan',
            'request_data' => json_encode([
                'scan_date' => $report['report_date'],
                'overall_score' => $report['overall_security_score'],
                'total_issues' => $report['scan']['total_issues'],
                'critical_updates' => $report['updates']['critical_updates'],
            ]),
            'severity' => $severity,
            'is_resolved' => false,
            'risk_score' => 100 - $report['overall_security_score'],
        ]);

        // تسجيل في ملف السجل
        Log::channel('security')->info('تم إجراء فحص أمني شامل', [
            'scan_date' => $report['report_date'],
            'overall_score' => $report['overall_security_score'],
            'total_issues' => $report['scan']['total_issues'],
            'critical_updates' => $report['updates']['critical_updates'],
        ]);
    }

    /**
     * إرسال إشعار بنتائج الفحص
     *
     * @param array $report
     * @return void
     */
    protected function sendNotification(array $report)
    {
        try {
            // إرسال بريد إلكتروني إلى المسؤولين
            $admins = \App\Models\User::role('admin')->get();
            
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new \App\Mail\SecurityScanReport($report));
            }
            
            $this->info('تم إرسال تقرير الفحص الأمني بنجاح إلى ' . $admins->count() . ' مسؤول.');
        } catch (\Exception $e) {
            $this->error('فشل في إرسال إشعار الفحص الأمني: ' . $e->getMessage());
            Log::error('فشل في إرسال إشعار الفحص الأمني: ' . $e->getMessage());
        }
    }
}
