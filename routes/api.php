<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Locations\LocationController;
use App\Http\Controllers\Api\Locations\QuestionController;
use App\Http\Controllers\Api\Offers\OfferController;
use App\Http\Controllers\Api\Offers\OrderController;
use App\Http\Controllers\Api\Offers\ExtensionController;
use App\Http\Controllers\Api\Wallet\WalletController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;

// Routes عامة
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login/send-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/login/verify-code', [AuthController::class, 'verifyCodeAndLogin']);

// ✅ Fawaterak Webhooks (بدون auth)
Route::post('/fawaterak/webhook', [PaymentController::class, 'webhook']);
Route::get('/fawaterak/callback', [PaymentController::class, 'callback']);

// 🧪 Test endpoint
Route::get('/test-fawaterak', function () {
    return response()->json([
        'api_key_exists' => !empty(config('fawaterak.api_key')),
        'api_key_length' => config('fawaterak.api_key') ? strlen(config('fawaterak.api_key')) : 0,
        'base_url' => config('fawaterak.base_url'),
        'success_url' => config('fawaterak.success_url'),
        'failure_url' => config('fawaterak.failure_url'),
    ]);
});

Route::get('/test-fawaterak-direct', function () {
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('fawaterak.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->post(config('fawaterak.base_url') . '/createInvoice', [
                'payment_method_id' => 1,
                'cartTotal' => 100,
                'currency' => 'EGP',
                'customer' => [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@test.com',
                    'phone' => '01000000000',
                    'address' => 'N/A',
                ],
                'redirectionUrls' => [
                    'successUrl' => 'http://localhost:8000/success',
                    'failUrl' => 'http://localhost:8000/fail',
                    'pendingUrl' => 'http://localhost:8000/pending',
                ],
                'cartItems' => [[
                    'name' => 'Test Item',
                    'price' => 100,
                    'quantity' => 1,
                ]],
            ]);
        
        return response()->json([
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body(),
            'json' => $response->json(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Routes محمية
Route::middleware('auth:api')->group(function () {
    // المصادقة
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/me', [ProfileController::class, 'getMyProfile']); // ✅ بروفايلي مع الإحصائيات
    Route::put('/me', [ProfileController::class, 'updateProfile']); // ✅ تحديث بروفايلي
    
    // المحفظة
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::post('/withdraw', [WalletController::class, 'withdraw']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
        
        // ✅ إيداع/سحب عبر Fawaterak
        Route::post('/deposit/fawaterak', [WalletController::class, 'depositViaFawaterak']);
        Route::post('/withdraw/fawaterak', [WalletController::class, 'withdrawViaFawaterak']);
    });
    
    // المواقع
    Route::prefix('locations')->group(function () {
        Route::post('/nearby-users', [LocationController::class, 'getNearbyUsers']); // ✅ Smart: يعمل بإحداثيات أو موقع محفوظ
        Route::post('/set-default', [LocationController::class, 'setDefault']);
        Route::get('/', [LocationController::class, 'index']);
        Route::post('/', [LocationController::class, 'store']);
        Route::get('/{id}', [LocationController::class, 'show']);
        Route::put('/{id}', [LocationController::class, 'update']);
        Route::delete('/{id}', [LocationController::class, 'destroy']);
    });

    // الأسئلة
    Route::prefix('questions')->group(function () {
        Route::post('/multiple', [QuestionController::class, 'storeMultiple']); // ✅ إضافة أسئلة متعددة
        Route::get('/nearby/all', [QuestionController::class, 'getNearbyQuestions']);
        Route::delete('/', [QuestionController::class, 'destroyAll']);
        Route::get('/{id}/views', [QuestionController::class, 'getViews']);
        Route::get('/', [QuestionController::class, 'index']);
        Route::post('/', [QuestionController::class, 'store']);
        Route::get('/{id}', [QuestionController::class, 'show']);
        Route::put('/{id}', [QuestionController::class, 'update']);
        Route::delete('/{id}', [QuestionController::class, 'destroy']);
    });

    // العروض
    Route::prefix('offers')->group(function () {
        Route::get('/my-offers', [OfferController::class, 'myOffers']);
        Route::post('/create', [OfferController::class, 'store']);
        Route::get('/questions/{questionId}', [OfferController::class, 'getQuestionOffers']);
        Route::get('/{offerId}', [OfferController::class, 'show']);
        Route::put('/{offerId}', [OfferController::class, 'update']);
        Route::delete('/{offerId}', [OfferController::class, 'destroy']);
        Route::post('/handle', [OfferController::class, 'handleOffer']); // قبول أو رفض عرض
    });

    // Orders Routes (معالجة العروض)
    Route::prefix('orders')->group(function () {
        Route::get('/asker', [OrderController::class, 'askerOrders']); // عرض طلبات السائل
        Route::get('/answerer', [OrderController::class, 'answererOrders']); // عرض طلبات المجيب
        Route::post('/answer', [OrderController::class, 'answerOrder']); // الإجابة على طلب
        Route::post('/cancel', [OrderController::class, 'cancelOrder']); // إلغاء طلب
        Route::get('/{orderId}/follow', [OrderController::class, 'followAnswer']); // متابعة الإجابة
        Route::post('/approve', [OrderController::class, 'approveAnswer']); // اعتماد الإجابة
    });
    
    // Disputes via Chat
    Route::post('/chats/dispute', [OrderController::class, 'disputeViaChat']); // الاعتراض عن طريق الشات

    Route::get('/asker/questions', [OrderController::class, 'askerQuestions']);
    Route::get('/asker/questions/{questionId}', [OrderController::class, 'showQuestionWithStatus']);

    // طلبات التمديد
    Route::prefix('extensions')->group(function () {
        Route::post('/request', [ExtensionController::class, 'requestExtension']); // طلب تمديد
        Route::get('/asker', [ExtensionController::class, 'askerExtensionRequests']); // طلبات السائل
        Route::post('/handle', [ExtensionController::class, 'handleExtension']); // قبول أو رفض تمديد
    });

    // الدردشة (Chat)
    Route::prefix('chats')->group(function () {
        Route::post('/', [ChatController::class, 'getOrCreateChat']); // إنشاء أو الحصول على محادثة
        Route::get('/', [ChatController::class, 'index']); // قائمة المحادثات
        Route::get('/{chatId}/messages', [ChatController::class, 'getMessages']); // رسائل محادثة معينة
        Route::post('/messages', [ChatController::class, 'sendMessage']); // إرسال رسالة
    });

    // بروفايل المجيبين والتقييمات
    Route::get('/answerers/{userId}/profile', [ProfileController::class, 'getAnswererProfile']); // بروفايل مجيب
    Route::post('/ratings', [ProfileController::class, 'rateAnswerer']); // تقييم مجيب
});