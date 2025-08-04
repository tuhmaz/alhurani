<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SecurityScanReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * تقرير الفحص الأمني
     *
     * @var array
     */
    public $report;

    /**
     * إنشاء مثيل جديد للرسالة
     *
     * @param array $report
     * @return void
     */
    public function __construct(array $report)
    {
        $this->report = $report;
    }

    /**
     * بناء الرسالة
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'تقرير الفحص الأمني: ';
        
        if ($this->report['overall_security_score'] < 70) {
            $subject .= 'مشكلات حرجة تتطلب اهتمامًا فوريًا';
        } elseif ($this->report['overall_security_score'] < 90) {
            $subject .= 'تم اكتشاف مشكلات أمنية';
        } else {
            $subject .= 'نتائج الفحص الدوري';
        }
        
        return $this->subject($subject)
            ->markdown('emails.security.scan-report')
            ->with([
                'report' => $this->report,
                'score' => $this->report['overall_security_score'],
                'scanDate' => $this->report['report_date'],
                'totalIssues' => $this->report['scan']['total_issues'],
                'criticalUpdates' => $this->report['updates']['critical_updates'],
                'vulnerabilities' => $this->report['scan']['vulnerabilities'],
                'updates' => $this->report['updates'],
                'logs' => $this->report['logs'],
            ]);
    }
}
