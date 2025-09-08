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

// CORS test route
Route::get('/cors-test', function () {
    return response()->json([
        'message' => 'CORS is working!',
        'timestamp' => now(),
        'origin' => request()->header('Origin')
    ]);
});

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Public VTU routes - Basic service information
Route::get('/vtu/services', [VtuController::class, 'getServices']);
Route::get('/vtu/airtime/networks', [VtuController::class, 'getAirtimeNetworks']);
Route::get('/vtu/data/networks', [VtuController::class, 'getDataNetworks']);
Route::get('/vtu/variations/data', [VtuController::class, 'getDataBundles']);
Route::get('/vtu/provider/balance', [VtuController::class, 'getProviderBalance']);
Route::get('/betting/providers', [VtuController::class, 'getBettingProviders']);
Route::get('/vtu/betting/providers', [VtuController::class, 'getBettingProviders']);
Route::get('/electricity/providers', [VtuController::class, 'getElectricityProviders']);
Route::get('/vtu/electricity/providers', [VtuController::class, 'getElectricityProviders']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Service management
    Route::post('/services', [ApiController::class, 'createService']);
    Route::put('/services/{id}', [ApiController::class, 'updateService']);
    Route::delete('/services/{id}', [ApiController::class, 'deleteService']);
    
    // SMS routes
    Route::get('/sms/providers', [SmsController::class, 'getProviders']);
    Route::get('/sms/services', [SmsController::class, 'getServices']);
    Route::post('/sms/services', [SmsController::class, 'getServices']);
    Route::get('/sms/countries', [SmsController::class, 'getCountries']);
    Route::get('/sms/countries-by-service', [SmsController::class, 'getCountriesByService']);
    Route::post('/sms/order', [SmsController::class, 'createOrder']);
    Route::get('/sms/orders', [SmsController::class, 'getOrders']);
    Route::get('/sms/orders/{id}', [SmsController::class, 'getOrder']);
    Route::post('/sms/code', [SmsController::class, 'getSmsCode']);
    Route::post('/sms/cancel', [SmsController::class, 'cancelOrder']);
    Route::get('/sms/stats', [SmsController::class, 'getStats']);
    
    // VTU routes - Protected purchase endpoints
    Route::post('/vtu/purchase', [VtuController::class, 'purchase']);
    Route::get('/vtu/transactions', [VtuController::class, 'getTransactions']);
    Route::get('/transactions', [VtuController::class, 'getTransactions']);
    Route::post('/vtu/airtime/purchase', [VtuController::class, 'purchaseAirtime']);
    Route::post('/vtu/data/purchase', [VtuController::class, 'purchaseDataBundle']);
    Route::get('/vtu/transaction/status', [VtuController::class, 'getTransactionStatus']);
    Route::post('/vtu/validate/phone', [VtuController::class, 'validatePhoneNumber']);
    Route::post('/verify-customer', [VtuController::class, 'verifyCustomer']);
    Route::post('/betting/purchase', [VtuController::class, 'purchaseBetting']);
    Route::post('/vtu/verify-customer', [VtuController::class, 'verifyCustomer']);
    Route::post('/vtu/betting/purchase', [VtuController::class, 'purchaseBetting']);
    Route::post('/electricity/verify', [VtuController::class, 'verifyElectricityCustomer']);
    Route::post('/electricity/purchase', [VtuController::class, 'purchaseElectricity']);
    Route::post('/vtu/electricity/verify', [VtuController::class, 'verifyElectricityCustomer']);
    Route::post('/vtu/electricity/purchase', [VtuController::class, 'purchaseElectricity']);
    
    // Proxy routes
    Route::get('/proxy/services', [ProxyController::class, 'getServices']);
    Route::post('/proxy/purchase', [ProxyController::class, 'purchase']);
    Route::get('/proxy/transactions', [ProxyController::class, 'getTransactions']);

    // Wallet routes
    Route::get('/wallet/deposits', [\App\Http\Controllers\WalletController::class, 'getRecentDeposits']);
});
