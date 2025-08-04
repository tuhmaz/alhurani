<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 'active'
            ]);

            // إنشاء رمز المصادقة
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء الحساب بنجاح',
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
                        'avatar' => $user->profile_photo_url,
                        'status' => $user->status,
                        'last_activity' => $user->last_activity ? $user->last_activity->format('Y-m-d H:i:s') : null
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء الحساب'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            // محاولة تسجيل الدخول
            $credentials = $request->only('email', 'password');
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'
                ], 401);
            }
            
            // الحصول على بيانات المستخدم
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }
            
            // إنشاء رمز جديد
            $token = $user->createToken('auth_token')->plainTextToken;

            // تحضير بيانات المستخدم للرد مع إضافة الحقول المطلوبة
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'job_title' => $user->job_title ?? null,
                'gender' => $user->gender ?? null,
                'country' => $user->country ?? null,
                'bio' => $user->bio ?? null,
                'social_links' => $user->social_links ?? null,
                'status' => $user->status ?? null,
                'avatar' => $user->profile_photo_url ?? asset('assets/img/avatars/1.png')
            ];

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'token' => $token,
                    'user' => $userData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // الحصول على المستخدم قبل تسجيل الخروج
            $user = $request->user();
            
            if ($user) {
                // محاولة تحديث حالة المستخدم بشكل آمن
                try {
                    $user->status = 'offline';
                    if ($user->isDirty('last_activity')) {
                        $user->last_activity = now();
                    }
                    $user->save();
                } catch (\Exception $e) {
                    Log::warning('لم يتم تحديث حالة المستخدم عند تسجيل الخروج', ['error' => $e->getMessage()]);
                }
                
                // تسجيل عملية تسجيل الخروج
                Log::info('تم تسجيل خروج المستخدم', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            }
            
            // حذف الرمز الحالي
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تسجيل الخروج: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج'
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'المستخدم غير مصرح له',
                    'data' => null
                ], 401);
            }

            return response()->json([
                'status' => true,
                'message' => null,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->profile_photo_url,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المستخدم',
                'data' => null
            ], 500);
        }
    }

    /**
     * إرسال رابط إعادة تعيين كلمة المرور
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'يرجى إدخال البريد الإلكتروني',
                'email.email' => 'يرجى إدخال بريد إلكتروني صالح',
                'email.exists' => 'البريد الإلكتروني غير مسجل في النظام'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();
            
            // إنشاء رمز إعادة تعيين كلمة المرور
            $token = Str::random(60);
            
            // حفظ رمز إعادة التعيين في قاعدة البيانات
            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );
            
            // إرسال بريد إلكتروني لإعادة تعيين كلمة المرور
            // في بيئة الإنتاج، يجب استخدام Mail::to($user->email)->send(new ResetPasswordMail($token));
            // لكن هنا سنكتفي بإرجاع استجابة ناجحة
            
            return response()->json([
                'status' => true,
                'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني',
                'data' => [
                    'reset_token' => $token // في الإنتاج لا ينبغي إرجاع الرمز، هذا فقط للاختبار
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في إعادة تعيين كلمة المرور: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء معالجة طلب إعادة تعيين كلمة المرور'
            ], 500);
        }
    }
    
    /**
     * تأكيد إعادة تعيين كلمة المرور
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'email.required' => 'يرجى إدخال البريد الإلكتروني',
                'email.email' => 'يرجى إدخال بريد إلكتروني صالح',
                'email.exists' => 'البريد الإلكتروني غير مسجل في النظام',
                'token.required' => 'رمز إعادة التعيين مطلوب',
                'password.required' => 'كلمة المرور الجديدة مطلوبة',
                'password.min' => 'يجب أن تكون كلمة المرور 8 أحرف على الأقل',
                'password.confirmed' => 'تأكيد كلمة المرور غير متطابق'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            // البحث عن رمز إعادة التعيين في قاعدة البيانات
            $resetRecord = \DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'status' => false,
                    'message' => 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية'
                ], 400);
            }

            // التحقق من صحة الرمز
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'status' => false,
                    'message' => 'رمز إعادة التعيين غير صحيح'
                ], 400);
            }

            // التحقق من صلاحية الرمز (24 ساعة)
            $tokenCreatedAt = \Carbon\Carbon::parse($resetRecord->created_at);
            if (now()->diffInHours($tokenCreatedAt) > 24) {
                return response()->json([
                    'status' => false,
                    'message' => 'رمز إعادة التعيين منتهي الصلاحية'
                ], 400);
            }

            // تحديث كلمة المرور
            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();

            // حذف رمز إعادة التعيين بعد الاستخدام
            \DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم إعادة تعيين كلمة المرور بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تأكيد إعادة تعيين كلمة المرور: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء معالجة طلب تأكيد إعادة تعيين كلمة المرور'
            ], 500);
        }
    }
	
}
