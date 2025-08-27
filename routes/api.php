<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\VtuController;
use App\Http\Controllers\ProxyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API routes
Route::get('/test', [ApiController::class, 'test']);
Route::get('/services', [ApiController::class, 'getServices']);

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Service management
    Route::post('/services', [ApiController::class, 'createService']);
    Route::put('/services/{id}', [ApiController::class, 'updateService']);
    Route::delete('/services/{id}', [ApiController::class, 'deleteService']);
    
    // SMS routes
    Route::get('/sms/services', [SmsController::class, 'getServices']);
    Route::post('/sms/order', [SmsController::class, 'createOrder']);
    Route::get('/sms/orders', [SmsController::class, 'getOrders']);
    Route::get('/sms/orders/{id}', [SmsController::class, 'getOrder']);
    
    // VTU routes
    Route::get('/vtu/services', [VtuController::class, 'getServices']);
    Route::post('/vtu/purchase', [VtuController::class, 'purchase']);
    Route::get('/vtu/transactions', [VtuController::class, 'getTransactions']);
    
    // Proxy routes
    Route::get('/proxy/services', [ProxyController::class, 'getServices']);
    Route::post('/proxy/purchase', [ProxyController::class, 'purchase']);
    Route::get('/proxy/transactions', [ProxyController::class, 'getTransactions']);
});
