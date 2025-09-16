<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\VtuController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\DebugSmsController;

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
Route::get('/servers', [SmsController::class, 'getServers']);
Route::get('/services', [ApiController::class, 'getServices']);

// Test route to verify getServers method works
Route::get('/test-servers', function() {
    $controller = new \App\Http\Controllers\SmsController(new \App\Services\SmsProviderService());
    return $controller->getServers();
});

// CORS test route
Route::get('/cors-test', function () {
	return response()->json([
		'message' => 'CORS is working!',
		'timestamp' => now(),
		'origin' => request()->header('Origin')
	]);
});

// Health check routes
Route::get('/health', [HealthController::class, 'check']);
Route::get('/health/quick', [HealthController::class, 'quick']);
Route::get('/health/endpoints', [HealthController::class, 'endpoints']);

// Debug SMS providers (raw response samples)
Route::get('/debug/sms/providers', [DebugSmsController::class, 'providers']);

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Admin login route (same as regular login but returns admin info)
Route::post('/admin/login', [AuthController::class, 'login'])->name('api/admin/login');

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
Route::post('/vtu/validate/phone', [VtuController::class, 'validatePhoneNumber']);

// SMS routes - Public access for frontend
Route::get('/sms/providers', [SmsController::class, 'getProviders']);
Route::get('/sms/services', [SmsController::class, 'getServices']);
Route::post('/sms/services', [SmsController::class, 'getServices']);
Route::get('/sms/countries', [SmsController::class, 'getCountries']);
Route::get('/sms/countries-by-service', [SmsController::class, 'getCountriesByService']);

// Server list endpoint for frontend - moved to top of file
// Route::get('/servers', [SmsController::class, 'getServers']); // Moved to line 31

// Phone validation - public route (FIXME: previously malformed)
// Route::post('/vtu/validate/phone', [VtuController::class, 'validatePhoneNumber']);

// TEMP public route for PayVibe testing (remove in production)
Route::post('/wallet/topup/initiate-public', [WalletController::class, 'initiateTopUpPublic']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Service management
    Route::post('/services', [ApiController::class, 'createService']);
    Route::put('/services/{id}', [ApiController::class, 'updateService']);
    Route::delete('/services/{id}', [ApiController::class, 'deleteService']);
    
    // SMS routes - Protected endpoints
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
    Route::get('/wallet/deposits', [WalletController::class, 'getRecentDeposits']);
    Route::post('/wallet/topup/initiate', [WalletController::class, 'initiateTopUp']);
    Route::post('/wallet/topup/verify', [WalletController::class, 'verifyTopUp']);
});

Route::post('/webhooks/payvibe', [WalletController::class, 'handlePayVibeWebhook']);
