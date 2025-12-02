<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Locations\LocationController;
use App\Http\Controllers\Api\Locations\QuestionController;
use App\Http\Controllers\Api\Offers\{OffersController, OrderController};

// Routes عامة
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login/send-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/login/verify-code', [AuthController::class, 'verifyCodeAndLogin']);

// Routes محمية
Route::middleware('auth:sanctum')->group(function () {
    // المصادقة
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/update-profile', [AuthController::class, 'updateProfile']);
    
    // المواقع
    Route::prefix('locations')->group(function () {
        Route::post('/search/nearby', [LocationController::class, 'searchNearby']);
        Route::get('/nearby-users', [LocationController::class, 'getNearbyUsers']);
        Route::post('/nearby-users/search', [LocationController::class, 'getNearbyUsersByCoordinates']);
        Route::post('/{id}/set-default', [LocationController::class, 'setDefault']);
        Route::get('/', [LocationController::class, 'index']);
        Route::post('/', [LocationController::class, 'store']);
        Route::get('/{id}', [LocationController::class, 'show']);
        Route::put('/{id}', [LocationController::class, 'update']);
        Route::delete('/{id}', [LocationController::class, 'destroy']);
    });

    // الأسئلة
    Route::prefix('questions')->group(function () {
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
        Route::get('/my-offers', [OffersController::class, 'myOffers']); // عروضي (للمجيب)
        Route::post('/questions/{questionId}', [OffersController::class, 'store']); // إضافة عرض
        Route::get('/questions/{questionId}', [OffersController::class, 'getQuestionOffers']); // عروض سؤال
        Route::get('/{offerId}', [OffersController::class, 'show']); // تفاصيل عرض
        Route::put('/{offerId}', [OffersController::class, 'update']); // تحديث عرض
        Route::delete('/{offerId}', [OffersController::class, 'destroy']); // حذف عرض
        Route::post('/accept', [OffersController::class, 'accept']); // قبول عرض
        Route::post('/reject', [OffersController::class, 'reject']); // رفض عرض
    });

    // الطلبات
    Route::prefix('orders')->group(function () {
        Route::get('/answerer', [OrderController::class, 'answererOrders']); // طلبات المجيب
        Route::post('/{orderId}/answer', [OrderController::class, 'answerOrder']); // الإجابة على طلب
        Route::post('/{orderId}/cancel', [OrderController::class, 'cancelOrder']); // إلغاء طلب
        Route::get('/asker/questions/{questionId}', [OrderController::class, 'showQuestionWithStatus']); // سؤال مع حالته
    });
});