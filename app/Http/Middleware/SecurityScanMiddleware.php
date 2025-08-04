<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SecurityLog;
use App\Services\SecurityAlertService;

class SecurityScanMiddleware
{
    /**
     * خدمة تنبيهات الأمان
     *
     * @var SecurityAlertService
     */
    protected $securityAlertService;

    /**
     * إنشاء مثيل جديد للوسيط.
     *
     * @param SecurityAlertService $securityAlertService
     * @return void
     */
    public function __construct(SecurityAlertService $securityAlertService)
    {
        $this->securityAlertService = $securityAlertService;
    }

    /**
     * معالجة الطلب الوارد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // تجاهل فحص الحذف من السجلات الأمنية
        if (
            ($request->isMethod('DELETE') || ($request->isMethod('POST') && $request->input('_method') === 'DELETE')) &&
            ($request->route() && (
                str_contains($request->route()->getName() ?? '', 'logs.destroy') ||
                str_contains($request->getPathInfo(), 'dashboard/security/logs')
            ))
        ) {
            return $next($request);
        }

        // فحص الطلب بحثًا عن أنماط هجمات محتملة
        if ($this->detectSqlInjection($request) || $this->detectXssAttack($request)) {
            // تسجيل الحدث الأمني
            $log = $this->logSecurityEvent($request);
            
            // معالجة الحدث الأمني وإرسال التنبيهات
            $this->securityAlertService->processSecurityEvent($log);
            
            // إعادة رسالة خطأ عامة للمستخدم
            return response()->json([
                'error' => 'تم اكتشاف محتوى غير آمن في الطلب.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * اكتشاف محاولات حقن SQL.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function detectSqlInjection(Request $request): bool
    {
        $patterns = [
            '/\b(union\s+select|select\s+.*\s+from|insert\s+into|update\s+.*\s+set)\b/i', // حذف delete from
            '/[\'";](\s*)(union|select|insert|update|drop|truncate|alter|exec|execute|sp_|xp_)/i', // حذف delete

            '/--\s+/',
            '/;\s*$/',
            '/\/\*.*\*\//',
            '/@@(version|servername|hostname)/i',
            '/waitfor\s+delay\s+/i',
            '/cast\(.+as\s+\w+\)/i',
            '/convert\(.+using\s+\w+\)/i',
        ];

        return $this->checkPatterns($request, $patterns, 'sql_injection_attempt');
    }

    /**
     * اكتشاف هجمات XSS.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function detectXssAttack(Request $request): bool
    {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/i',
            '/javascript\s*:/i',
            '/on(load|click|mouseover|submit|focus|blur|change|error|select)\s*=/i',
            '/<\s*img[^>]+src\s*=\s*[\'"]?\s*(javascript|data|vbscript):/i',
            '/<\s*iframe/i',
            '/<\s*object/i',
            '/<\s*embed/i',
            '/<\s*form/i',
            '/document\.(cookie|write|location|open|eval)/i',
            '/eval\s*\(/i',
            '/expression\s*\(/i',
            '/base64/i',
            '/alert\s*\(/i',
            '/confirm\s*\(/i',
            '/prompt\s*\(/i',
        ];

        return $this->checkPatterns($request, $patterns, 'xss_attempt');
    }

    /**
     * فحص أنماط الهجمات في الطلب.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $patterns
     * @param  string  $attackType
     * @return bool
     */
    protected function checkPatterns(Request $request, array $patterns, string $attackType): bool
    {
        // استثناء المفاتيح الآمنة
        $inputs = $request->except(['_token', '_method', 'page', 'per_page', 'sort', 'direction']);
        $inputString = json_encode($inputs);
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $inputString)) {
                // تسجيل تفاصيل النمط المكتشف
                Log::channel('security')->warning("تم اكتشاف نمط {$attackType}: " . $pattern, [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'route' => $request->route() ? $request->route()->getName() : $request->path(),
                    'pattern' => $pattern,
                    'input' => $inputString,
                ]);
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * تسجيل حدث أمني.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return SecurityLog
     */
    protected function logSecurityEvent(Request $request): SecurityLog
    {
        $eventType = $this->detectSqlInjection($request) ? 'sql_injection_attempt' : 'xss_attempt';
        
        // تنظيف البيانات قبل الحفظ
        $requestData = $request->all();
        foreach ($requestData as $key => $value) {
            if (is_array($value)) {
                $requestData[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_object($value)) {
                $requestData[$key] = json_encode((array)$value, JSON_UNESCAPED_UNICODE);
            }
        }
        
        return SecurityLog::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => $eventType,
            'description' => "تم اكتشاف محاولة {$eventType} من عنوان IP: {$request->ip()}",
            'user_id' => auth()->id(),
            'route' => $request->route() ? $request->route()->getName() : $request->path(),
            'request_data' => json_encode($requestData, JSON_UNESCAPED_UNICODE),
            'severity' => 'danger',
            'is_resolved' => false,
            'risk_score' => 80
        ]);
    }
}
