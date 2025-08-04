<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('content.authentications.auth-login-cover');
    }

    public function showRegistrationForm()
    {
        return view('content.authentications.auth-register-cover');
    }

    public function register(Request $request)
{
    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'g-recaptcha-response' => 'required|captcha',
        ], [
            'g-recaptcha-response.required' => 'يرجى التأكد من أنك لست روبوت.',
            'g-recaptcha-response.captcha' => 'فشل التحقق من reCAPTCHA!',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('User');

        Log::info('User created successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => 'User'
        ]);

        try {
            $user->notify(new CustomVerifyEmail);
            Log::info('Verification email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', __('تم إنشاء حسابك بنجاح. يرجى التحقق من بريدك الإلكتروني لتفعيل حسابك.'));

    } catch (\Exception $e) {
        Log::error('Registration failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return back()
            ->withInput()
            ->withErrors(['error' => __('حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.')]);
    }
}

    

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $credentials = $request->only('email', 'password');

            // Log authentication attempt
            Log::info('Login attempt', [
                'email' => $request->email,
                'guard' => config('auth.defaults.guard')
            ]);

            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();
                
                $user = Auth::user();
                Log::info('Login successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'guard' => Auth::getDefaultDriver()
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => true,
                        'message' => 'تم تسجيل الدخول بنجاح',
                        'user' => $user
                    ]);
                }

                return redirect()->intended('/');
            }

            Log::warning('Login failed', [
                'email' => $request->email,
                'reason' => 'Invalid credentials'
            ]);

            return back()->withErrors([
                'email' => 'بيانات الاعتماد المقدمة غير صحيحة.'
            ]);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => 'حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.'
            ]);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Get user before logging out
            $user = Auth::user();

            if ($user) {
                // Set user as offline
                $user->update([
                    'is_online' => false,
                    'status' => 'offline',
                    'last_activity' => now(),
                    'last_seen' => now()
                ]);

                // Log the logout action
                Log::info('User logged out', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                // Clear user-specific cache using a simple key
                cache()->forget('user.' . $user->id . '.permissions');
                cache()->forget('user.' . $user->id . '.settings');
                cache()->forget('user.' . $user->id . '.preferences');
            }

            // Clear authentication
            Auth::logout();

            // Clear and invalidate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Clear all cookies
            $cookies = $request->cookies->all();
            $response = redirect('/');
            foreach ($cookies as $cookie => $value) {
                $response->withCookie(cookie()->forget($cookie));
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'تم تسجيل الخروج بنجاح'
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'حدث خطأ أثناء تسجيل الخروج'
                ], 500);
            }

            return redirect('/')->withErrors([
                'error' => 'حدث خطأ أثناء تسجيل الخروج'
            ]);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($request->wantsJson()) {
            return $status === Password::RESET_LINK_SENT
                ? response()->json(['status' => true, 'message' => __($status)])
                : response()->json(['status' => false, 'message' => __($status)], 400);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showForgotPasswordForm()
    {
        return view('content.authentications.auth-forgot-password-cover');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($request->wantsJson()) {
            return $status === Password::PASSWORD_RESET
                ? response()->json(['status' => true, 'message' => __($status)])
                : response()->json(['status' => false, 'message' => __($status)], 400);
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function showResetPasswordForm($token)
    {
        return view('content.authentications.auth-reset-password-cover', ['token' => $token]);
    }

    public function verify(Request $request)
    {
        try {
            $user = User::find($request->route('id'));

            if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
                throw new AuthorizationException;
            }

            if ($user->hasVerifiedEmail()) {
                return redirect()->intended(route('dashboard.index').'?verified=1');
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return redirect()->intended(route('dashboard.index').'?verified=1');

        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'user_id' => $request->route('id'),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('verification.notice')
                ->with('error', __('فشل التحقق من البريد الإلكتروني. يرجى المحاولة مرة أخرى.'));
        }
    }

    public function verificationNotice()
    {
        return view('content.authentications.auth-verify-email-cover');
    }

    public function verificationResend(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => __('البريد الإلكتروني مؤكد بالفعل.')
                    ], 400);
                }
                return back()->with('error', __('البريد الإلكتروني مؤكد بالفعل.'));
            }

            // التحقق من معدل الإرسال
            $key = 'verify-email-' . $request->user()->id;
            $maxAttempts = 3;
            $decayMinutes = 1;

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة.")
                    ], 429);
                }
                return back()->with('error', __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة."));
            }

            RateLimiter::hit($key, $decayMinutes * 60);

            $request->user()->notify(new CustomVerifyEmail);

            Log::info('Verification email resent', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => __('تم إرسال رابط التحقق بنجاح.')
                ]);
            }

            return back()->with('success', __('تم إرسال رابط التحقق بنجاح.'));
        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'user_id' => $request->user()->id ?? 'unknown',
                'email' => $request->user()->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => __('حدث خطأ أثناء إرسال رابط التحقق.')
                ], 500);
            }

            return back()->with('error', __('حدث خطأ أثناء إرسال رابط التحقق.'));
        }
    }

    public function showVerificationNotice()
    {
        return view('content.authentications.auth-verify-email-cover');
    }

    public function resendVerificationEmail(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => __('البريد الإلكتروني مؤكد بالفعل.')
                ], 400);
            }

            // التحقق من معدل الإرسال
            $key = 'verify-email-' . $request->user()->id;
            $maxAttempts = 3;
            $decayMinutes = 1;

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'status' => false,
                    'message' => __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة.")
                ], 429);
            }

            RateLimiter::hit($key, $decayMinutes * 60);

            $request->user()->notify(new CustomVerifyEmail);

            Log::info('Verification email resent', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);

            return response()->json([
                'status' => true,
                'message' => __('تم إرسال رابط التحقق بنجاح.')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => __('حدث خطأ أثناء إرسال رابط التحقق.')
            ], 500);
        }
    }

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function googleCallback()
    {
        try {
            // Log the beginning of the callback process
            Log::info('Google callback initiated', ['request_data' => request()->all()]);
            
            // Get the Google user - use stateless to avoid session issues
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Log successful retrieval of Google user data
            Log::info('Google user data retrieved', [
                'google_id' => $googleUser->id,
                'email' => $googleUser->email,
                'name' => $googleUser->name
            ]);
            
            // Check if user exists
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                // Create a new user
                Log::info('Creating new user from Google login', ['email' => $googleUser->email]);
                
                // Prepare user data with required fields
                $userData = [
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                    'google_id' => $googleUser->id,
                ];
                
                // Add profile photo only if it exists
                if (!empty($googleUser->avatar)) {
                    $userData['profile_photo_path'] = $googleUser->avatar;
                }
                
                // Create user with required fields
                $user = User::create($userData);
                
                // Verify role exists before assigning
                try {
                    $user->assignRole('User');
                    Log::info('Role assigned to new user', ['user_id' => $user->id, 'role' => 'User']);
                } catch (\Exception $roleException) {
                    Log::warning('Could not assign role to user', [
                        'user_id' => $user->id, 
                        'error' => $roleException->getMessage()
                    ]);
                }
                
                Log::info('New user created from Google login', ['user_id' => $user->id]);
            } else {
                // Update existing user's Google ID and avatar if not set
                Log::info('Updating existing user from Google login', ['user_id' => $user->id]);
                
                $updateData = ['google_id' => $googleUser->id];
                
                // Add profile photo only if it exists
                if (!empty($googleUser->avatar)) {
                    $updateData['profile_photo_path'] = $googleUser->avatar;
                }
                
                $user->update($updateData);
            }

            // تأكيد البريد الإلكتروني إذا لم يكن مؤكداً بالفعل
            if (!$user->hasVerifiedEmail()) {
                $user->forceFill([
                    'email_verified_at' => now()
                ])->save();
                
                Log::info('Email automatically verified for Google user', ['user_id' => $user->id]);
            }
            
            // تسجيل دخول المستخدم بشكل صحيح مع تجديد الجلسة
            Auth::login($user);
            request()->session()->regenerate();
            
            Log::info('User logged in via Google', ['user_id' => $user->id]);

            // التأكد من إعادة توجيه المستخدم بشكل صحيح
            if (Auth::check()) {
                Log::info('User successfully authenticated after Google login', ['user_id' => $user->id]);
                return redirect()->intended('/dashboard')
                    ->with('success', 'تم تسجيل الدخول بنجاح باستخدام حساب Google');
            } else {
                Log::error('User not authenticated after Google login', ['user_id' => $user->id]);
                return redirect()->route('login')
                    ->with('error', 'حدث خطأ أثناء تسجيل الدخول. الرجاء المحاولة مرة أخرى.');
            }

        } catch (\Exception $e) {
            // Log the error with detailed information
            Log::error('Google login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => request()->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return redirect()->route('login')
                ->with('error', 'حدث خطأ أثناء تسجيل الدخول باستخدام Google. الرجاء المحاولة مرة أخرى.');
        }
    }
}
