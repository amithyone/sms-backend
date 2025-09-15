<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This is an API backend. Web routes are disabled.
| Use /api/* endpoints instead.
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'FaddedSMS API Backend',
        'version' => '1.0.0',
        'status' => 'running',
        'endpoints' => [
            'api' => '/api',
            'test' => '/api/test',
            'services' => '/api/services',
            'admin' => '/admin'
        ]
    ]);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Admin panel routes for managing the system
|
*/

// Admin login page (public)
Route::get('/admin/login', function () {
    return view('admin.login');
});

// Publicly serve the admin shell views; the views will call protected APIs via token
Route::prefix('admin')->group(function () {
    // Dashboard view (unprotected view; data fetched via API)
    Route::get('/', [AdminController::class, 'dashboard']);
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
});

// Protected admin API routes (JSON only)
Route::prefix('api')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::get('/admin/users/{id}', [AdminController::class, 'getUser']);
    Route::put('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus']);
    Route::put('/admin/users/{id}/role', [AdminController::class, 'updateUserRole']);
    Route::post('/admin/users/{id}/balance', [AdminController::class, 'updateUserBalance']);
    Route::get('/admin/transactions', [AdminController::class, 'transactions']);
    Route::get('/admin/transactions/export.csv', [AdminController::class, 'exportTransactionsCsv']);
    Route::get('/admin/orders/sms', [AdminController::class, 'listSmsOrders']);
    Route::get('/admin/orders/vtu', [AdminController::class, 'listVtuOrders']);
    Route::get('/admin/deposits', [AdminController::class, 'deposits']);
    Route::put('/admin/deposits/{id}/status', [AdminController::class, 'updateDepositStatus']);
    Route::get('/admin/users/export.csv', [AdminController::class, 'exportUsersCsv']);
    Route::get('/admin/statistics', [AdminController::class, 'statistics']);
    Route::get('/admin/services', [AdminController::class, 'listApiServices']);
    Route::put('/admin/services/sms/{id}', [AdminController::class, 'updateSmsService']);
    Route::put('/admin/services/vtu/{id}', [AdminController::class, 'updateVtuService']);
    Route::post('/admin/services/sms/{id}/refresh-balance', [AdminController::class, 'refreshSmsProviderBalance']);
    Route::post('/admin/services/sms/{id}/test', [AdminController::class, 'testSmsProvider']);
    Route::post('/admin/services/vtu/{id}/refresh-balance', [AdminController::class, 'refreshVtuProviderBalance']);
    Route::get('/admin/pricing', [AdminController::class, 'getPricingSettings']);
    Route::post('/admin/pricing', [AdminController::class, 'updatePricingSettings']);
});

Route::get('/login', function () {
    return response()->json(['message' => 'Login route for redirects'], 200);
})->name('login');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);

// TextVerified Webhook endpoint (no auth; secured by HMAC signature)
Route::post('/webhooks/textverified', [WebhookController::class, 'textVerified']);
