<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendCodeRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    /**
     * تسجيل مستخدم جديد (بدون باسورد)
     * 
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // ✅ تحديد الـ description الافتراضي حسب النوع
            $defaultDescription = $request->is_asker ?? true 
                ? 'سائل' 
                : 'متخصص في الردود الميدانية للمؤسسات الحكومية بالرياض.';

            // إنشاء المستخدم
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'gender' => $request->gender,
                'is_asker' => $request->is_asker ?? true,
                'description' => $request->description ?? $defaultDescription, // ✅ description
                'is_active' => true,
            ]);

            // إنشاء توكن
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('✅ New user registered: ' . $user->phone);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الحساب بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'gender' => $user->gender,
                        'is_asker' => $user->is_asker,
                        'description' => $user->description, // ✅
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    ],
                    'token' => $token,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('❌ Registration failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الحساب',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * إرسال كود التحقق عبر Twilio
     */
    public function sendVerificationCode(SendCodeRequest $request): JsonResponse
    {
        try {
            $phone = $request->phone;
            $code = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);

            $deleted = VerificationCode::where('phone', $phone)->delete();
            
            if ($deleted > 0) {
                Log::info("🗑️ Deleted {$deleted} old verification codes for: {$phone}");
            }

            $verificationCode = VerificationCode::create([
                'phone' => $phone,
                'code' => $code,
                'expires_at' => Carbon::now()->addMinute(1),
                'is_used' => false,
            ]);

            Log::info("💾 Verification code saved to database", [
                'phone' => $phone,
                'code' => $code,
                'expires_at' => $verificationCode->expires_at->format('Y-m-d H:i:s'),
            ]);

            $twilioSent = false;
            try {
                $verifySid = config('services.twilio.verify_sid');

                if ($verifySid) {
                    $this->twilio->verify->v2->services($verifySid)
                        ->verifications
                        ->create($phone, "sms");

                    $twilioSent = true;
                    Log::info("📱 Verification code sent via Twilio for: {$phone}");
                } else {
                    Log::warning('⚠️ Twilio Verify SID is missing');
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Twilio sending failed', [
                    'phone' => $phone,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال كود التحقق بنجاح. الكود صالح لمدة دقيقة واحدة فقط',
                'data' => [
                    'phone' => $phone,
                    'expires_in_seconds' => 60,
                    'code' => config('app.debug') ? $code : null,
                    'twilio_sent' => $twilioSent
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error sending verification code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال كود التحقق',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * التحقق من الكود وتسجيل الدخول
     */
    public function verifyCodeAndLogin(VerifyCodeRequest $request): JsonResponse
    {
        try {
            $code = $request->code;

            Log::info('🔍 Verifying code', ['code' => $code]);

            $verificationCode = VerificationCode::where('code', $code)
                ->where('is_used', false)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$verificationCode) {
                Log::warning('❌ Invalid or expired code', ['code' => $code]);

                if (config('app.debug')) {
                    $availableCodes = VerificationCode::where('is_used', false)
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get(['code', 'phone', 'expires_at', 'created_at']);
                    Log::info('📋 Available codes:', $availableCodes->toArray());
                }

                return response()->json([
                    'success' => false,
                    'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية',
                ], 401);
            }

            $phone = $verificationCode->phone;
            
            Log::info('✅ Code verified successfully', [
                'code' => $code,
                'phone' => $phone
            ]);

            $verificationCode->update(['is_used' => true]);
            Log::info('🔒 Code marked as used');

            $user = User::where('phone', $phone)->first();

            if (!$user) {
                Log::error('❌ User not found for phone: ' . $phone);

                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                ], 404);
            }

            $deletedTokens = $user->tokens()->count();
            $user->tokens()->delete();

            if ($deletedTokens > 0) {
                Log::info("🗑️ Deleted {$deletedTokens} old tokens");
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('🔑 New authentication token created for user: ' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'gender' => $user->gender,
                        'is_asker' => $user->is_asker,
                        'description' => $user->description, // ✅
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    ],
                    'token' => $token,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error during login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * تسجيل الخروج من الجهاز الحالي
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $request->user()->currentAccessToken()->delete();

            Log::info('👋 User logged out: ' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح',
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Logout failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * تسجيل الخروج من جميع الأجهزة
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $deletedCount = $user->tokens()->count();
            $user->tokens()->delete();

            Log::info("👋 User logged out from all devices ({$deletedCount} tokens): " . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج من جميع الأجهزة بنجاح',
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Logout all failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * الحصول على بيانات المستخدم الحالي
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'is_asker' => $user->is_asker,
                    'description' => $user->description, // ✅
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ]
            ]
        ], 200);
    }

    /**
     * تحديث بيانات الملف الشخصي
     * ✅ يشمل تحديث is_asker و description
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // التحقق من البيانات
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'gender' => 'nullable|in:male,female',
                'phone' => 'nullable|string|unique:users,phone,' . $user->id,
                'is_asker' => 'nullable|boolean', // ✅ إضافة is_asker
                'description' => 'nullable|string|max:1000', // ✅ إضافة description
            ], [
                'name.max' => 'الاسم يجب ألا يتجاوز 255 حرف',
                'email.email' => 'البريد الإلكتروني غير صالح',
                'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
                'gender.in' => 'الجنس يجب أن يكون male أو female',
                'phone.unique' => 'رقم الجوال مستخدم من قبل',
                'is_asker.boolean' => 'نوع المستخدم يجب أن يكون true أو false',
                'description.max' => 'الوصف يجب ألا يتجاوز 1000 حرف',
            ]);

            // ✅ إذا تم تغيير is_asker، حدث الـ description تلقائياً (إذا لم يتم إرساله يدوياً)
            if (isset($validated['is_asker']) && $validated['is_asker'] !== $user->is_asker) {
                if (!isset($validated['description'])) {
                    $validated['description'] = $validated['is_asker'] 
                        ? 'سائل' 
                        : 'متخصص في الردود الميدانية للمؤسسات الحكومية بالرياض.';
                    
                    Log::info('🔄 Description auto-updated due to is_asker change', [
                        'user_id' => $user->id,
                        'new_is_asker' => $validated['is_asker'],
                        'new_description' => $validated['description']
                    ]);
                }
            }

            // تحديث البيانات
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }
            
            if (isset($validated['gender'])) {
                $user->gender = $validated['gender'];
            }
            
            if (isset($validated['phone'])) {
                $user->phone = $validated['phone'];
            }

            if (isset($validated['is_asker'])) {
                $user->is_asker = $validated['is_asker'];
            }

            if (isset($validated['description'])) {
                $user->description = $validated['description'];
            }

            $user->save();

            Log::info('✅ Profile updated successfully for user: ' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الملف الشخصي بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'gender' => $user->gender,
                        'is_asker' => $user->is_asker,
                        'description' => $user->description, // ✅
                        'is_active' => $user->is_active,
                        'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('❌ Error updating profile: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الملف الشخصي',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}