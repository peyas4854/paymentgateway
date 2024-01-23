<?php

use App\Http\Controllers\BkashController;
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

Route::get('/', function () {
    return view('welcome');
});
// Amar pay routes
Route::get('/checkout',[\App\Http\Controllers\paymentController::class,'payment'])->name('payment');
//You need declare your success & fail route in "app\Middleware\VerifyCsrfToken.php"
Route::post('success',[\App\Http\Controllers\paymentController::class,'success'])->name('success');
Route::post('fail',[\App\Http\Controllers\paymentController::class,'fail'])->name('fail');
Route::get('cancel',[\App\Http\Controllers\paymentController::class,'cancel'])->name('cancel');

// Bkash routes
//Route::get('/bkash/checkout-url/pay', [BkashController::class, 'pay'])->name('url-pay');
Route::post('/bkash/checkout-url/create', [BkashController::class, 'create'])->name('url-create');
Route::get('/bkash/checkout-url/callback', [BkashController::class, 'callback'])->name('url-callback');


