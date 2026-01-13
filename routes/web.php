<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::any('/', function () {
    return redirect('/admin');
});

Route::any('/public', function () {
    return redirect('/admin');
});

Route::fallback(function () {
    return redirect('/admin');
});

// 🧪 Test Payment Page
Route::get('/test-payment/{invoice}', function ($invoice) {
    $amount = request('amount', 100);
    $userId = request('user_id', 1);
    return view('test-payment', compact('invoice', 'amount', 'userId'));
})->name('fawaterak.test.payment');
