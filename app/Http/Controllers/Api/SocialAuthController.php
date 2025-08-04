<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            $url = Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'status' => true,
                'redirect_url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء محاولة الاتصال بـ Google'
            ], 500);
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            // التحقق من وجود رمز المصادقة
            if (!$request->has('code')) {
                return response()->json([
                    'status' => false,
                    'message' => 'رمز المصادقة غير موجود'
                ], 400);
            }

            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            // البحث عن المستخدم أو إنشاء مستخدم جديد
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(Str::random(16)),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'profile_photo_path' => $googleUser->avatar
                ]);
            }

            // إنشاء رمز المصادقة
            $token = $user->createToken('google_auth')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'job_title' => $user->job_title,
                        'gender' => $user->gender,
                        'country' => $user->country,
                        'bio' => $user->bio,
                        'social_links' => $user->social_links,
                        'avatar' => $photo ?: $user->profile_photo_url,
                        'status' => $user->status,
                        'last_activity' => $user->last_activity ? $user->last_activity->format('Y-m-d H:i:s') : null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول عبر Google'
            ], 500);
        }
    }
    
    /**
     * تسجيل الدخول باستخدام Google من تطبيقات الجوال
     * هذه الطريقة تقبل idToken من تطبيق الجوال مباشرة
     */
    public function loginWithGoogle(Request $request)
    {
        try {
            // للتشخيص - تسجيل كل البيانات الواردة
            \Log::info('Google login request data: ' . json_encode($request->all()));
            
            // التحقق من وجود رمز المصادقة
            if (!$request->has('id_token')) {
                return response()->json([
                    'status' => false,
                    'message' => 'رمز المصادقة غير موجود'
                ], 400);
            }
            
            // الحصول على بيانات المستخدم من الطلب
            $email = null;
            $name = null;
            $photo = null;
            
            // محاولة الحصول على البيانات من الطلب مباشرة
            if ($request->has('email')) {
                $email = $request->input('email');
                $name = $request->input('name', $email);
                $photo = $request->input('photo', null);
            }
            
            // محاولة الحصول على البيانات من المعلومات المرسلة من Flutter
            if (empty($email)) {
                $allData = $request->all();
                if (isset($allData['email'])) {
                    $email = $allData['email'];
                    $name = $allData['name'] ?? $email;
                    $photo = $allData['photo'] ?? null;
                }
            }
            
            // إذا لم نجد البيانات، نستخدم البريد الإلكتروني الافتراضي
            if (empty($email)) {
                // استخدام بيانات افتراضية للاختبار فقط
                $email = 'user_' . time() . '@example.com';
                $name = 'Google User';
            }
            
            // البحث عن المستخدم أو إنشاء مستخدم جديد
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                // إنشاء مستخدم جديد مع تأكيد البريد الإلكتروني تلقائيًا لأن المستخدم يسجل عبر Google
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(16)),
                    'status' => 'active',
                    'email_verified_at' => now(), // تعيين وقت التحقق من البريد الإلكتروني إلى الوقت الحالي
                    'profile_photo_path' => null // لا نخزن الصورة في قاعدة البيانات، بل نستخدم الرابط مباشرة
                ]);
            } else {
                // التأكد من أن البريد الإلكتروني مؤكد لمستخدمي Google الحاليين
                if ($user->email_verified_at === null) {
                    $user->email_verified_at = now();
                    $user->save();
                }
            }
            
            // إنشاء رمز المصادقة
            $token = $user->createToken('google_auth')->plainTextToken;
            
            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'job_title' => $user->job_title,
                        'gender' => $user->gender,
                        'country' => $user->country,
                        'bio' => $user->bio,
                        'social_links' => $user->social_links,
                        'avatar' => $photo ?: $user->profile_photo_url,
                        'status' => $user->status,
                        'last_activity' => $user->last_activity ? $user->last_activity->format('Y-m-d H:i:s') : null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول عبر Google: ' . $e->getMessage()
            ], 500);
        }
    }
}
