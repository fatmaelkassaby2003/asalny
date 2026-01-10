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
     * تسجيل مستخدم جديد
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $defaultDescription = $this->getDefaultDescription($request->is_asker ?? true);

            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'gender' => $request->gender,
                'is_asker' => $request->is_asker ?? true,
                'description' => $request->description ?? $defaultDescription,
                'is_active' => true,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('✅ New user registered', ['user_id' => $user->id, 'phone' => $user->phone]);

            return $this->successResponse(
                'تم إنشاء الحساب بنجاح',
                [
                    'user' => $this->formatUserData($user),
                    'token' => $token,
                ],
                201
            );
        } catch (\Exception $e) {
            Log::error('❌ Registration failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('حدث خطأ أثناء إنشاء الحساب', 500);
        }
    }

    /**
     * إرسال كود التحقق
     */
    public function sendVerificationCode(SendCodeRequest $request): JsonResponse
    {
        try {
            $phone = $request->phone;
            
            // توليد كود عشوائي
            $code = $this->generateVerificationCode();
            
            // حذف الأكواد القديمة
            $this->deleteOldCodes($phone);
            
            // حفظ الكود الجديد
            $verificationCode = $this->createVerificationCode($phone, $code);
            
            // إرسال عبر Twilio
            $twilioSent = $this->sendViaTwilio($phone);

            Log::info('💾 Verification code created', [
                'phone' => $phone,
                'expires_at' => $verificationCode->expires_at->format('Y-m-d H:i:s'),
            ]);

            return $this->successResponse(
                'تم إرسال كود التحقق بنجاح',
                [
                    'phone' => $phone,
                    'code' => $code,
                    'expires_in_seconds' => 60,
                    'twilio_sent' => $twilioSent,
                ]
            );

        } catch (\Exception $e) {
            Log::error('❌ Error sending verification code', ['error' => $e->getMessage()]);
            return $this->errorResponse('حدث خطأ أثناء إرسال كود التحقق', 500);
        }
    }

    /**
     * التحقق من الكود وتسجيل الدخول
     */
    public function verifyCodeAndLogin(VerifyCodeRequest $request): JsonResponse
    {
        try {
            $code = $request->code;
            $phone = $request->phone;

            Log::info('🔍 Verifying code', ['code' => $code, 'phone' => $phone]);

            // البحث عن الكود الصالح لهذا الرقم
            $verificationCode = VerificationCode::where('code', $code)
                ->where('phone', $phone)
                ->where('is_used', false)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$verificationCode) {
                Log::warning('❌ Invalid or expired code', ['code' => $code, 'phone' => $phone]);
                return $this->errorResponse('كود التحقق غير صحيح أو منتهي الصلاحية', 401);
            }

            // تعليم الكود كمستخدم
            $verificationCode->update(['is_used' => true]);
            Log::info('✅ Code verified', ['code' => $code, 'phone' => $phone]);

            // البحث عن المستخدم
            $user = User::where('phone', $phone)->first();

            if (!$user) {
                Log::error('❌ User not found', ['phone' => $phone]);
                return $this->errorResponse('المستخدم غير موجود', 404);
            }

            // حذف التوكنات القديمة
            $deletedCount = $user->tokens()->count();
            $user->tokens()->delete();

            if ($deletedCount > 0) {
                Log::info('🗑️ Old tokens deleted', [
                    'user_id' => $user->id,
                    'count' => $deletedCount
                ]);
            }

            // إنشاء توكن جديد
            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('🔑 New token created', ['user_id' => $user->id]);

            return $this->successResponse(
                'تم تسجيل الدخول بنجاح',
                [
                    'user' => $this->formatUserData($user),
                    'token' => $token,
                ]
            );

        } catch (\Exception $e) {
            Log::error('❌ Error during login', ['error' => $e->getMessage()]);
            return $this->errorResponse('حدث خطأ أثناء تسجيل الدخول', 500);
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

            Log::info('👋 User logged out', ['user_id' => $user->id]);

            return $this->successResponse('تم تسجيل الخروج بنجاح');
        } catch (\Exception $e) {
            Log::error('❌ Logout failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('حدث خطأ أثناء تسجيل الخروج', 500);
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

            Log::info('👋 User logged out from all devices', [
                'user_id' => $user->id,
                'tokens_deleted' => $deletedCount
            ]);

            return $this->successResponse('تم تسجيل الخروج من جميع الأجهزة بنجاح');
        } catch (\Exception $e) {
            Log::error('❌ Logout all failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('حدث خطأ أثناء تسجيل الخروج', 500);
        }
    }

    // ==================== Helper Methods ====================


    /**
     * تنسيق بيانات المستخدم للـ Response
     */
    private function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'gender' => $user->gender,
            'is_asker' => $user->is_asker,
            'description' => $user->description,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * الحصول على Description الافتراضي
     */
    private function getDefaultDescription(bool $isAsker): string
    {
        return $isAsker ? 'سائل' : 'مجيب';
    }

    /**
     * توليد كود تحقق عشوائي
     */
    private function generateVerificationCode(): string
    {
        return str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * حذف الأكواد القديمة
     */
    private function deleteOldCodes(string $phone): void
    {
        $deleted = VerificationCode::where('phone', $phone)->delete();
        
        if ($deleted > 0) {
            Log::info('🗑️ Old codes deleted', ['phone' => $phone, 'count' => $deleted]);
        }
    }

    /**
     * إنشاء كود تحقق جديد
     */
    private function createVerificationCode(string $phone, string $code): VerificationCode
    {
        return VerificationCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinute(1),
            'is_used' => false,
        ]);
    }

    /**
     * إرسال الكود عبر Twilio
     */
    private function sendViaTwilio(string $phone): bool
    {
        try {
            $verifySid = config('services.twilio.verify_sid');

            if (!$verifySid) {
                Log::warning('⚠️ Twilio Verify SID is missing');
                return false;
            }

            $this->twilio->verify->v2->services($verifySid)
                ->verifications
                ->create($phone, "sms");

            Log::info('📱 Verification code sent via Twilio', ['phone' => $phone]);
            return true;

        } catch (\Exception $e) {
            Log::warning('⚠️ Twilio sending failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Success Response
     */
    private function successResponse(
        ?string $message = null, 
        ?array $data = null, 
        int $statusCode = 200
    ): JsonResponse {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $statusCode);
    }

    /**
     * Error Response
     */
    private function errorResponse(
        string $message, 
        int $statusCode = 400,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        if (config('app.debug') && $statusCode >= 500) {
            $response['debug'] = true;
        }
        
        return response()->json($response, $statusCode);
    }
}