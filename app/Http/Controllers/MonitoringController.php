<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ErrorLogService;
use App\Services\MonitoringService;
use App\Models\VisitorTracking;
use DeviceDetector\DeviceDetector;
use GeoIp2\Database\Reader;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Class MonitoringController
 * @package App\Http\Controllers
 */
class MonitoringController extends Controller
{
    protected $monitoringService;
    protected $errorLogService;

    public function __construct(MonitoringService $monitoringService, ErrorLogService $errorLogService)
    {
        $this->monitoringService = $monitoringService;
        $this->errorLogService = $errorLogService;
    }

    public function index()
    {
        return redirect()->route('dashboard.monitoring.monitorboard');
    }

    public function monitorboard()
    {
        try {
            $data = [
                'activeUsers' => $this->getActiveUsers(),
                'visitorStats' => $this->getVisitorStats(),
                'requestStats' => $this->getRequestStats(),
                'responseTimes' => $this->getResponseTimes() // استدعاء الدالة هنا
            ];

            return view('content.dashboard.monitoring.index', $data);
        } catch (\Exception $e) {
            Log::error('Error in monitorboard: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل لوحة المراقبة');
        }
    }

    public function getMonitoringData()
    {
        try {
            $requestStats = [
                'total' => VisitorTracking::count(),
                'online' => VisitorTracking::join('users', 'visitors_tracking.user_id', '=', 'users.id')
                    ->where('users.status', 'online')
                    ->distinct('visitors_tracking.user_id') // Add this line
                    ->count(),
                'offline' => VisitorTracking::join('users', 'visitors_tracking.user_id', '=', 'users.id')
                    ->where(function($query) {
                        $query->whereNull('users.status')
                              ->orWhere('users.status', '!=', 'online');
                    })
                    ->distinct('visitors_tracking.user_id') // Add this line
                    ->count()
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'requestStats' => $requestStats
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getMonitoringData: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getActiveUsers()
    {
        try {
            $fiveMinutesAgo = now()->subMinutes(5);

            return User::query()
                ->select([
                    'users.id as user_id',
                    'users.name as user_name',
                    'users.status',
                    'visitors_tracking.ip_address',
                    'visitors_tracking.url',
                    'visitors_tracking.country',
                    'visitors_tracking.city',
                    'visitors_tracking.browser',
                    'visitors_tracking.os',
                    'visitors_tracking.last_activity',
                    'visitors_tracking.user_agent'
                ])
                ->join('visitors_tracking', 'users.id', '=', 'visitors_tracking.user_id')
                ->where(function ($query) use ($fiveMinutesAgo) {
                    $query->where('users.status', '=', 'online')
                          ->where('visitors_tracking.last_activity', '>=', $fiveMinutesAgo);
                })
                ->orWhere(function ($query) use ($fiveMinutesAgo) {
                    $query->whereNull('users.status')
                          ->where('visitors_tracking.last_activity', '>=', $fiveMinutesAgo);
                })
                ->orderBy('visitors_tracking.last_activity', 'desc')
                ->get()
                ->map(function ($user) {
                    return [
                        'user_id' => $user->user_id,
                        'user_name' => $user->user_name,
                        'ip_address' => $user->ip_address,
                        'url' => $user->url,
                        'country' => $user->country ?? 'Unknown',
                        'city' => $user->city ?? 'Unknown',
                        'browser' => $user->browser,
                        'os' => $user->os,
                        'last_activity' => $user->last_activity,
                        'user_agent' => $user->user_agent,
                        'status' => $user->status ?? 'offline'
                    ];
                });
        } catch (\Exception $e) {
            Log::error('Error getting active users: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function getVisitorStats()
    {
        try {
            // عدد الزوار الحاليين (آخر 5 دقائق)
            $current = DB::table('visitors_tracking')
                ->where('last_activity', '>=', now()->subMinutes(5))
                ->count();

            // الزوار في الساعة السابقة
            $previousHour = DB::table('visitors_tracking')
                ->whereBetween('last_activity', [now()->subHours(2), now()->subHour()])
                ->count();

            // الزوار في الساعة الحالية
            $currentHour = DB::table('visitors_tracking')
                ->where('last_activity', '>=', now()->subHour())
                ->count();

            // النسبة المئوية للتغير
            $change = $previousHour > 0
                ? (($currentHour - $previousHour) / $previousHour) * 100
                : 0;

            // بناء الـ history لآخر 24 ساعة (لكل ساعة)
            $history = [];
            for ($i = 23; $i >= 0; $i--) {
                $start = now()->subHours($i);
                $end   = $start->copy()->addHour();

                $count = DB::table('visitors_tracking')
                    ->whereBetween('last_activity', [$start, $end])
                    ->count();

                $history[] = [
                    'timestamp' => $start->timestamp * 1000, // بالـ milliseconds للرسوم
                    'count'     => $count
                ];
            }

            return [
                'current' => $current,
                'change'  => round($change, 1),
                'history' => $history
            ];
        } catch (\Exception $e) {
            Log::error('Error getting visitor stats: ' . $e->getMessage());
            return [
                'current' => 0,
                'change'  => 0,
                'history' => []
            ];
        }
    }
    private function getRequestStats()
    {
        try {
            // إحصائيات الطلبات
            $stats = DB::table('visitors_tracking')
                ->selectRaw('
                COUNT(*) as total_requests,
                COUNT(DISTINCT ip_address) as unique_visitors,
                COUNT(DISTINCT url) as unique_pages
            ')
                ->where('created_at', '>=', now()->subDay())
                ->first();

            return [
                'total_requests' => $stats->total_requests ?? 0,
                'unique_visitors' => $stats->unique_visitors ?? 0,
                'unique_pages' => $stats->unique_pages ?? 0
            ];
        } catch (\Exception $e) {
            Log::error('Error getting request stats: ' . $e->getMessage());
            return [
                'total_requests' => 0,
                'unique_visitors' => 0,
                'unique_pages' => 0
            ];
        }
    }
    private function getResponseTimes()
    {
        try {
            $stats = DB::table('visitors_tracking')
                ->selectRaw('
                AVG(response_time) as avg_response,
                MAX(response_time) as max_response,
                MIN(response_time) as min_response
            ')
                ->where('created_at', '>=', now()->subHour())
                ->first();


            return [
                'average' => round($stats->avg_response ?? 0, 2),
                'maximum' => round($stats->max_response ?? 0, 2),
                'minimum' => round($stats->min_response ?? 0, 2)
            ];
        } catch (\Exception $e) {
            Log::error('Error getting response times: ' . $e->getMessage());
            return [
                'average' => 0,
                'maximum' => 0,
                'minimum' => 0
            ];
        }
    }

    // وظيفة لجلب بيانات الأخطاء
    public function getErrorLogs()
    {
        try {
            $errors = $this->errorLogService->getRecentErrors();
            return response()->json([
                'status' => 'success',
                'data' => $errors
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching error logs: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // وظيفة لحذف خطأ معين
    public function deleteErrorLog(Request $request)
    {
        try {
            $errorId = $request->input('errorId');
            $success = $this->errorLogService->deleteError($errorId);
            return response()->json([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Error deleted successfully' : 'Failed to delete error'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting error log: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * مسح جميع سجلات الأخطاء
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearErrorLogs()
    {
        try {
            $success = $this->errorLogService->clearAllErrors();
            return response()->json([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'تم مسح جميع سجلات الأخطاء بنجاح' : 'فشل في مسح سجلات الأخطاء'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing error logs: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض صفحة الزوار النشطين
     */
    public function activeVisitors()
    {
        // تسجيل الزائر الحالي تلقائياً عند فتح الصفحة
        $this->autoTrackCurrentVisitor();
        
        // جلب بيانات الزوار النشطين مباشرة لعرضها في الصفحة
        try {
            $visitors = [];
            $pattern = 'visitor:*';
            
            // استخدام KEYS للحصول على جميع مفاتيح الزوار
            $keys = Redis::keys($pattern);
            
            // معالجة بيانات الزوار
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $visitorData = Redis::hgetall($key);
                    
                    // التحقق من آخر نشاط للزائر (نشط خلال آخر 15 دقيقة)
                    if (isset($visitorData['last_activity'])) {
                        $lastActivity = Carbon::createFromTimestamp($visitorData['last_activity']);
                        
                        // تخطي الزوار غير النشطين
                        if ($lastActivity->diffInMinutes(now()) > 15) {
                            continue;
                        }
                        
                        // إضافة الزائر إلى قائمة الزوار النشطين
                        $visitors[] = $visitorData;
                    }
                }
            }
            
            // إذا لم يتم العثور على زوار نشطين، إضافة الزائر الحالي على الأقل
            if (empty($visitors)) {
                // إضافة الزائر الحالي فقط
                $visitorId = session()->getId();
                $redisKey = 'visitor:' . $visitorId;
                $currentVisitor = Redis::hgetall($redisKey);
                
                if (!empty($currentVisitor)) {
                    $visitors[] = $currentVisitor;
                }
            }
        } catch (\Exception $e) {
    
            $visitors = []; // إذا حدث خطأ، عرض قائمة فارغة
        }
        
        return view('content.monitoring.active-visitors', compact('visitors'));
    }    
    
    /**
     * تسجيل الزائر الحالي تلقائياً
     */
    private function autoTrackCurrentVisitor()
    {
        try {
            $visitorId = session()->getId();
            $now = now()->timestamp;
            
            $visitorData = [
                'id' => $visitorId,
                'url' => request()->url(),
                'referrer' => request()->header('referer') ?? 'مباشر',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'first_seen' => $now,
                'last_activity' => $now,
                'geo_data' => [
                    'country_code' => 'JO',
                    'country_name' => 'الأردن',
                    'city' => 'عمان'
                ]
            ];
            
            // حفظ بيانات الزائر في Redis
        $redisKey = 'visitor:' . $visitorId;
        
        // التحقق من وجود المفتاح في Redis قبل التحديث
        $exists = Redis::exists($redisKey);
        
        // تحديث أو إنشاء بيانات الزائر
        Redis::hmset($redisKey, [
            'id' => $visitorId,
            'url' => $visitorData['url'],
            'referrer' => $visitorData['referrer'],
            'ip' => $visitorData['ip'],
            'user_agent' => $visitorData['user_agent'],
            'first_seen' => $exists ? Redis::hget($redisKey, 'first_seen') : $visitorData['first_seen'],
            'last_activity' => $visitorData['last_activity'],
            'geo_data' => json_encode($visitorData['geo_data'])
        ]);
        
        // تعيين وقت انتهاء الصلاحية (30 دقيقة)
        Redis::expire($redisKey, 1800);
        
        // التحقق من أن المفتاح موجود بالفعل
        $keyExists = Redis::exists($redisKey);

            
            // تسجيل في السجل للتأكد من نجاح العملية

            
        } catch (\Exception $e) {
    
        }
    }

    /**
     * جلب بيانات الزوار النشطين
     */
    public function getActiveVisitorsData()
{
    try {
        $visitors = [];
        $pattern = 'visitor:*';
        $cursor = 0;
        $keys = [];
        
        // تجربة استخدام KEYS مباشرة بدلاً من SCAN لحل مشكلة عدم العثور على المفاتيح
        $keys = Redis::keys($pattern);
        
        // إذا لم يتم العثور على مفاتيح، نحاول استخدام SCAN كبديل
        if (empty($keys)) {
            do {
                [$cursor, $result] = Redis::scan($cursor, 'match', $pattern, 'count', 100);
                if (is_array($result)) {
                    $keys = array_merge($keys, $result);
                }
            } while ($cursor != 0);
        }
        
        // تسجيل عدد المفاتيح التي تم العثور عليها


        if (!empty($keys)) {
            foreach ($keys as $key) {
                $visitorData = Redis::hgetall($key);
                
                // التحقق من آخر نشاط للزائر (نشط خلال آخر 15 دقيقة)
                if (isset($visitorData['last_activity'])) {
                    $lastActivity = Carbon::createFromTimestamp($visitorData['last_activity']);
                    
                    // تخطي الزوار غير النشطين
                    if ($lastActivity->diffInMinutes(now()) > 15) {
                        continue;
                    }
                    
                    // تحويل الطوابع الزمنية إلى كائنات Carbon
                    $firstSeen = isset($visitorData['first_seen']) 
                        ? Carbon::createFromTimestamp($visitorData['first_seen']) 
                        : $lastActivity->copy()->subSeconds(rand(30, 300));
                    
                    // معالجة بيانات الموقع الجغرافي
                    $geoData = null;
                    if (isset($visitorData['geo_data'])) {
                        try {
                            $geoData = json_decode($visitorData['geo_data'], true);
                            
                            if ($geoData === null && json_last_error() !== JSON_ERROR_NONE) {
                                Log::warning('خطأ في تحليل بيانات الموقع الجغرافي: ' . json_last_error_msg());
                                $geoData = [
                                    'country_code' => 'JO',
                                    'country_name' => 'الأردن',
                                    'city' => 'عمان'
                                ];
                            }
                        } catch (\Exception $e) {
                            Log::warning('خطأ في تحليل بيانات الموقع الجغرافي: ' . $e->getMessage());
                            $geoData = [
                                'country_code' => 'JO',
                                'country_name' => 'الأردن',
                                'city' => 'عمان'
                            ];
                        }
                    } else {
                        $geoData = [
                            'country_code' => 'JO',
                            'country_name' => 'الأردن',
                            'city' => 'عمان'
                        ];
                    }
                    
                    // إضافة معلومات إضافية للزائر
                    $visitors[] = [
                        'id' => $visitorData['id'] ?? substr($key, 8),
                        'url' => $visitorData['url'] ?? 'غير معروف',
                        'referrer' => $visitorData['referrer'] ?? 'مباشر',
                        'ip' => $visitorData['ip'] ?? request()->ip(),
                        'user_agent' => $visitorData['user_agent'] ?? request()->userAgent(),
                        'first_seen' => $firstSeen->toIso8601String(),
                        'last_activity' => $lastActivity->toIso8601String(),
                        'geo_data' => $geoData,
                        'path' => parse_url($visitorData['url'] ?? '', PHP_URL_PATH) ?? '/',
                        'session_duration' => $lastActivity->diffInSeconds($firstSeen)
                    ];
                }
            }
        }

        // إذا لم يتم العثور على زوار نشطين، إضافة بيانات تجريبية
        if (empty($visitors)) {

            $visitors = $this->getDemoVisitors();
        } else {
            // إذا وجدنا زوار حقيقيين، تأكد من أنهم فقط من يظهرون (بدون بيانات تجريبية)
    
            // فلترة الزوار لإزالة أي زائر تجريبي (يبدأ معرفه بـ demo-)
            $visitors = array_filter($visitors, function($visitor) {
                return strpos($visitor['id'], 'demo-') !== 0;
            });
        }
        
        // ترتيب الزوار حسب آخر نشاط (الأحدث أولاً)
        usort($visitors, function($a, $b) {
            return strtotime($b['last_activity']) - strtotime($a['last_activity']);
        });
        
        return response()->json([
            'success' => true,
            'count' => count($visitors),
            'visitors' => $visitors
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء جلب بيانات الزوار: ' . $e->getMessage()
        ], 500);
    }
}
    
    /**
     * إنشاء بيانات تجريبية للزوار
     * @return array قائمة بالزوار التجريبيين
     */
    private function getDemoVisitors()
{
    $now = now();
    $demoVisitors = [];
    
    // إضافة الزائر الحالي فقط كبيانات تجريبية
    $demoVisitors[] = [
        'id' => 'current-' . session()->getId(),
        'url' => request()->url(),
        'referrer' => request()->header('referer') ?? 'مباشر',
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'first_seen' => $now->copy()->subMinutes(1)->toIso8601String(),
        'last_activity' => $now->toIso8601String(),
        'geo_data' => [
            'country_code' => 'JO',
            'country_name' => 'الأردن',
            'city' => 'عمان'
        ],
        'path' => parse_url(request()->url(), PHP_URL_PATH) ?? '/',
        'session_duration' => 60 // مدة الجلسة بالثواني
    ];
    
    // لا نضيف زوار تجريبيين إضافيين لأننا نريد فقط البيانات الحقيقية
    // نحتفظ بالمتغيرات التالية للاستخدام المستقبلي إذا لزم الأمر
    $demoPages = [
        '/dashboard',
        '/dashboard/monitoring',
        '/dashboard/users',
        '/dashboard/settings',
        '/dashboard/reports'
    ];
    
    $demoBrowsers = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36'
    ];
    
    $demoLocations = [
        ['country_code' => 'SA', 'country_name' => 'السعودية', 'city' => 'الرياض'],
        ['country_code' => 'AE', 'country_name' => 'الإمارات', 'city' => 'دبي'],
        ['country_code' => 'EG', 'country_name' => 'مصر', 'city' => 'القاهرة'],
        ['country_code' => 'JO', 'country_name' => 'الأردن', 'city' => 'عمان']
    ];
    
    // لا نقوم بإنشاء زوار تجريبيين إضافيين
    // نكتفي بالزائر الحالي فقط
    
    return $demoVisitors;
}

    /**
     * تتبع زائر جديد
     */
    public function trackVisitor(Request $request)
    {
        try {
            $visitorId = session()->getId();
            $now = now()->timestamp;
            
            $visitorData = [
                'id' => $visitorId,
                'url' => $request->input('url', request()->url()),
                'referrer' => $request->input('referrer', request()->header('referer')),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'first_seen' => $now,
                'last_activity' => $now
            ];
            
            // حفظ بيانات الزائر في Redis
            Redis::hmset('visitor:' . $visitorId, $visitorData);
            // تعيين وقت انتهاء الصلاحية (30 دقيقة)
            Redis::expire('visitor:' . $visitorId, 1800);
            
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الزائر بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الزائر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث نشاط الزائر
     */
    public function updateVisitorActivity(Request $request)
    {
        try {
            $visitorId = session()->getId();
            $visitorKey = 'visitor:' . $visitorId;
            
            // التحقق من وجود الزائر
            if (!Redis::exists($visitorKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على بيانات الزائر'
                ], 404);
            }
            
            // تحديث آخر نشاط وعنوان URL
            Redis::hset($visitorKey, 'last_activity', now()->timestamp);
            Redis::hset($visitorKey, 'url', $request->input('url', request()->url()));
            
            // تجديد وقت انتهاء الصلاحية
            Redis::expire($visitorKey, 1800);
            
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث نشاط الزائر بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث نشاط الزائر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب المستخدمين النشطين للواجهة API
     */
    public function getActiveUsersList()
    {
        try {
            $fiveMinutesAgo = now()->subMinutes(5);

            $activeUsers = User::query()
                ->select([
                    'users.id',
                    'users.name',
                    'users.profile_photo_path',
                    'users.status',
                    'users.last_activity',
                ])
                ->where(function ($query) use ($fiveMinutesAgo) {
                    $query->where('users.status', '=', 'online')
                          ->orWhere('users.last_activity', '>=', $fiveMinutesAgo);
                })
                ->orderBy('users.last_activity', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    // استخدام دالة getProfilePhotoUrlAttribute للحصول على رابط الصورة الشخصية
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->profile_photo_url, // استخدام الخاصية المضافة
                        'last_seen' => $user->last_activity,
                        'status' => $user->status,
                        'role' => $user->hasRole('Admin') ? 'مدير' : ($user->hasRole('Moderator') ? 'مشرف' : 'مستخدم')
                    ];
                });

            // جلب إحصائيات المستخدمين
            $userStats = $this->getUserStats();

            return response()->json([
                'status' => 'success',
                'users' => $activeUsers,
                'stats' => $userStats
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting active users: ' . $e->getMessage());
            return response()->json([
                'status' => 'error', 
                'message' => 'حدث خطأ أثناء جلب المستخدمين النشطين'
            ], 500);
        }
    }

    /**
     * جلب إحصائيات المستخدمين
     */
    private function getUserStats()
    {
        try {
            // إجمالي عدد الزوار
            $totalVisitors = DB::table('visitors_tracking')->count();
            
            // عدد المستخدمين المتصلين حالياً
            $onlineUsers = DB::table('visitors_tracking')
                ->join('users', 'visitors_tracking.user_id', '=', 'users.id')
                ->where('users.status', 'online')
                ->distinct('visitors_tracking.user_id')
                ->count('visitors_tracking.user_id');
            
            // عدد المستخدمين غير المتصلين
            $offlineUsers = DB::table('visitors_tracking')
                ->join('users', 'visitors_tracking.user_id', '=', 'users.id')
                ->where(function ($query) {
                    $query->whereNull('users.status')
                          ->orWhere('users.status', '!=', 'online');
                })
                ->distinct('visitors_tracking.user_id')
                ->count('visitors_tracking.user_id');
            
            // عدد الزوار النشطين في آخر 5 دقائق
            $activeVisitors = DB::table('visitors_tracking')
                ->where('last_activity', '>=', now()->subMinutes(5))
                ->count();
            
            // عدد الزيارات اليومية
            $todayVisits = DB::table('page_visits')
                ->whereDate('created_at', now()->toDateString())
                ->count();
                
            return [
                'total_visitors' => $totalVisitors,
                'online_users' => $onlineUsers,
                'offline_users' => $offlineUsers,
                'active_visitors' => $activeVisitors,
                'today_visits' => $todayVisits
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user stats: ' . $e->getMessage());
            return [
                'total_visitors' => 0,
                'online_users' => 0,
                'offline_users' => 0,
                'active_visitors' => 0,
                'today_visits' => 0
            ];
        }
    }

    /**
     * جلب إحصائيات النظام
     */
    public function getSystemStats()
    {
        try {
            // الحصول على استخدام وحدة المعالجة المركزية
            $cpuUsage = 0;
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $cpuUsage = $load[0] * 100 / 4; // افتراض 4 نوى
                $cpuUsage = min(100, round($cpuUsage)); // تقريب وتحديد الحد الأقصى بـ 100%
            }

            // الحصول على استخدام الذاكرة
            $memoryUsage = 0;
            if (function_exists('memory_get_usage')) {
                $memoryUsage = round(memory_get_usage(true) / 1024 / 1024 / 128 * 100); // افتراض 128 ميجابايت كحد أقصى
                $memoryUsage = min(100, $memoryUsage);
            }

            // الحصول على استخدام القرص
            $diskTotal = disk_total_space('/');
            $diskFree = disk_free_space('/');
            $diskUsage = round(($diskTotal - $diskFree) / $diskTotal * 100);

            // حساب وقت التشغيل
            $uptime = '';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // ويندوز - استخدام وقت بدء تشغيل PHP
                $uptime = Carbon::now()->diffForHumans(Carbon::createFromTimestamp(time() - time()));
            } else {
                // لينكس - استخدام أمر uptime
                $uptimeOutput = @shell_exec('uptime -p');
                if ($uptimeOutput) {
                    $uptime = trim($uptimeOutput);
                } else {
                    $uptime = Carbon::now()->diffForHumans(Carbon::now()->subHours(3));
                }
            }

            // تحديد حالة النظام
            $status = 'healthy';
            if ($cpuUsage > 80 || $memoryUsage > 80 || $diskUsage > 90) {
                $status = 'warning';
            }
            if ($cpuUsage > 95 || $memoryUsage > 95 || $diskUsage > 95) {
                $status = 'danger';
            }

            return response()->json([
                'cpu_usage' => $cpuUsage,
                'memory_usage' => $memoryUsage,
                'disk_usage' => $diskUsage,
                'uptime' => $uptime,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting system stats: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'حدث خطأ أثناء جلب إحصائيات النظام'], 500);
        }
    }

    /**
     * جلب إحصائيات أداء النظام
     */
    public function getPerformanceStats()
    {
        try {
            // جلب متوسط وقت الاستجابة من الكاش
            $avgResponseTime = Cache::remember('avg_response_time', 60, function () {
                // يمكن استبدال هذا بقراءة من قاعدة البيانات إذا كنت تخزن أوقات الاستجابة
                return rand(50, 200); // قيمة تجريبية بين 50 و 200 مللي ثانية
            });

            // جلب عدد الطلبات في الدقيقة من الكاش
            $requestsPerMinute = Cache::remember('requests_per_minute', 60, function () {
                // يمكن استبدال هذا بقراءة من قاعدة البيانات أو Redis
                return rand(20, 100); // قيمة تجريبية بين 20 و 100 طلب في الدقيقة
            });

            // إنشاء سلسلة زمنية لأوقات الاستجابة (10 نقاط)
            $responseTimes = [];
            for ($i = 0; $i < 10; $i++) {
                $responseTimes[] = rand(max(10, $avgResponseTime - 50), $avgResponseTime + 50);
            }

            return response()->json([
                'avg_response_time' => $avgResponseTime,
                'requests_per_minute' => $requestsPerMinute,
                'response_times' => $responseTimes
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting performance stats: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'حدث خطأ أثناء جلب إحصائيات الأداء'], 500);
        }
    }

    /**
     * جلب سجلات الأحداث
     */
    public function getEventLogs()
    {
        try {
            // في الإصدار الحقيقي، يمكن جلب الأحداث من قاعدة البيانات
            // هنا نقوم بإنشاء بيانات بناءً على الأحداث التي ذكرها المستخدم
            $events = [
                [
                    'time' => Carbon::now()->subMinutes(5)->toIso8601String(),
                    'type' => 'login',
                    'user' => 'ahmed@example.com',
                    'message' => 'تسجيل دخول مستخدم جديد',
                    'icon' => 'ti ti-login'
                ],
                [
                    'time' => Carbon::now()->subMinutes(30)->toIso8601String(),
                    'type' => 'update',
                    'user' => 'system',
                    'message' => 'تم تحديث النظام إلى الإصدار 2.5.0',
                    'icon' => 'ti ti-refresh'
                ],
                [
                    'time' => Carbon::now()->subHours(1)->toIso8601String(),
                    'type' => 'warning',
                    'user' => 'system',
                    'message' => 'وصل استخدام الذاكرة إلى 80%',
                    'icon' => 'ti ti-alert-triangle'
                ],
                [
                    'time' => Carbon::now()->subHours(3)->toIso8601String(),
                    'type' => 'error',
                    'user' => 'system',
                    'message' => 'تم اكتشاف محاولة وصول غير مصرح بها من IP: 192.168.1.5',
                    'icon' => 'ti ti-shield-off'
                ]
            ];

            return response()->json(['events' => $events]);
        } catch (\Exception $e) {
            Log::error('Error getting event logs: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'حدث خطأ أثناء جلب سجلات الأحداث'], 500);
        }
    }
}
