<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/test-fawaterak-config', function () {
    return response()->json([
        'api_key' => config('fawaterak.api_key') ? 'موجود (طوله: ' . strlen(config('fawaterak.api_key')) . ')' : 'غير موجود',
        'base_url' => config('fawaterak.base_url'),
        'success_url' => config('fawaterak.success_url'),
        'failure_url' => config('fawaterak.failure_url'),
    ]);
});

Route::middleware('auth:sanctum')->post('/test-fawaterak-call', function (Request $request) {
    $user = $request->user();
    $fawaterakService = app(\App\Services\FawaterakService::class);
    
    $result = $fawaterakService->createDepositInvoice($user, 100);
    
    return response()->json($result);
});
