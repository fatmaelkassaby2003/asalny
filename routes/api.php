<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Locations\LocationController;
use App\Http\Controllers\Api\Locations\QuestionController;
use App\Http\Controllers\Api\Offers\OfferController;
use App\Http\Controllers\Api\Offers\OrderController;
use App\Http\Controllers\Api\Offers\ExtensionController;
use App\Http\Controllers\Api\Wallet\WalletController;

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
    
    // المحفظة
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::post('/withdraw', [WalletController::class, 'withdraw']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
    });
    
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
        Route::get('/my-offers', [OfferController::class, 'myOffers']);
        Route::post('/questions/{questionId}', [OfferController::class, 'store']);
        Route::get('/questions/{questionId}', [OfferController::class, 'getQuestionOffers']);
        Route::get('/{offerId}', [OfferController::class, 'show']);
        Route::put('/{offerId}', [OfferController::class, 'update']);
        Route::delete('/{offerId}', [OfferController::class, 'destroy']);
        Route::post('/accept', [OfferController::class, 'accept']);
        Route::post('/reject', [OfferController::class, 'reject']);
    });

    // الطلبات
    Route::prefix('orders')->group(function () {
        Route::get('/answerer', [OrderController::class, 'answererOrders']);
        Route::post('/{orderId}/answer', [OrderController::class, 'answerOrder']);
        Route::post('/{orderId}/cancel', [OrderController::class, 'cancelOrder']);
        Route::get('/asker/questions', [OrderController::class, 'askerQuestions']);
        Route::get('/asker/questions/{questionId}', [OrderController::class, 'showQuestionWithStatus']);
    });

    // طلبات التمديد
    Route::prefix('extensions')->group(function () {
        Route::post('/orders/{orderId}', [ExtensionController::class, 'requestExtension']); // طلب تمديد
        Route::get('/asker', [ExtensionController::class, 'askerExtensionRequests']); // طلبات السائل
        Route::post('/{extensionId}/accept', [ExtensionController::class, 'acceptExtension']); // قبول
        Route::post('/{extensionId}/reject', [ExtensionController::class, 'rejectExtension']); // رفض
    });
});