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
use App\Http\Controllers\InboxController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\Api\CachedSmsController;

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
	$user = $request->user();
	return response()->json([
		'id' => $user->id,
		'name' => $user->name,
		'email' => $user->email,
		'phone' => $user->phone,
		'username' => $user->username,
		'balance' => $user->balance,
		'wallet' => $user->balance, // For frontend compatibility
		'referral_code' => $user->referral_code,
		'role' => $user->role,
		'status' => $user->status,
		'avatar' => $user->avatar,
		'google_id' => $user->google_id,
		'auth_provider' => $user->auth_provider,
		'vtu_access_enabled' => $user->vtu_access_enabled,
		'last_login_at' => $user->last_login_at,
	]);
});

Route::middleware('auth:sanctum')->put('/user/update', [AuthController::class, 'updateProfile']);
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);

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

// Documentation routes - Public access
Route::get('/docs', [DocumentationController::class, 'index']);
Route::get('/API_DOCUMENTATION.md', [DocumentationController::class, 'apiDocumentation']);
Route::get('/HELP_RESOURCES.md', [DocumentationController::class, 'helpResources']);
Route::get('/TERMS_OF_USE.md', [DocumentationController::class, 'termsOfUse']);
Route::get('/PRIVACY_POLICY.md', [DocumentationController::class, 'privacyPolicy']);

// Debug SMS providers (raw response samples)
Route::get('/debug/sms/providers', [DebugSmsController::class, 'providers']);

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Google OAuth routes
Route::get('/auth/google', [\App\Http\Controllers\SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [\App\Http\Controllers\SocialAuthController::class, 'handleGoogleCallbackRedirect']);

// Admin login route (same as regular login but returns admin info)
Route::post('/admin/login', [AuthController::class, 'login'])->name('api/admin/login');

// VTU routes - Basic service information (now protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/vtu/services', [VtuController::class, 'getServices']);
    Route::get('/vtu/airtime/networks', [VtuController::class, 'getAirtimeNetworks']);
    Route::get('/vtu/data/networks', [VtuController::class, 'getDataNetworks']);
    Route::get('/vtu/variations/data', [VtuController::class, 'getDataBundles']);
    Route::get('/vtu/data-plans', [VtuController::class, 'getDataBundles']); // Alternative endpoint for frontend compatibility
    Route::get('/vtu/provider/balance', [VtuController::class, 'getProviderBalance']);
    Route::get('/betting/providers', [VtuController::class, 'getBettingProviders']);
    Route::get('/vtu/betting/providers', [VtuController::class, 'getBettingProviders']);
    Route::get('/electricity/providers', [VtuController::class, 'getElectricityProviders']);
    Route::get('/vtu/electricity/providers', [VtuController::class, 'getElectricityProviders']);
    Route::post('/vtu/validate/phone', [VtuController::class, 'validatePhoneNumber']);
});

// SMS routes - Now protected (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/sms/providers', [SmsController::class, 'getProviders']);
    Route::get('/sms/services', [SmsController::class, 'getServices']);
    Route::post('/sms/services', [SmsController::class, 'getServices']);
    Route::get('/sms/countries', [SmsController::class, 'getCountries']);

    // Cached SMS routes - Fast delivery from database
    Route::get('/sms/cached/countries', [CachedSmsController::class, 'getCountries']);
    Route::get('/sms/cached/services', [CachedSmsController::class, 'getServices']);
    Route::get('/sms/cached/search', [CachedSmsController::class, 'searchServices']);
    Route::get('/sms/cached/stats', [CachedSmsController::class, 'getCacheStats']);
    Route::get('/sms/countries-by-service', [SmsController::class, 'getCountriesByService']);
});

// Error logging routes
Route::prefix('errors')->group(function () {
    Route::post('/logFrontendError', [App\Http\Controllers\Api\ErrorLoggerController::class, 'logFrontendError']);
    Route::post('/logPerformanceIssue', [App\Http\Controllers\Api\ErrorLoggerController::class, 'logPerformanceIssue']);
    Route::post('/logApiUsage', [App\Http\Controllers\Api\ErrorLoggerController::class, 'logApiUsage']);
});

// Server list endpoint for frontend - moved to top of file
// Route::get('/servers', [SmsController::class, 'getServers']); // Moved to line 31

// Phone validation - public route (FIXME: previously malformed)
// Route::post('/vtu/validate/phone', [VtuController::class, 'validatePhoneNumber']);

// TEMP public route for PayVibe testing (remove in production)
Route::post('/wallet/topup/initiate-public', [WalletController::class, 'initiateTopUpPublic']);

// Advertisement routes - Public (no authentication required for viewing ads)
Route::get('/advertisements', [AdvertisementController::class, 'getActive']);

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
    // Order status check for timeout scenarios
    Route::post('/order/status', [VtuController::class, 'checkOrderStatus']);
    
    // Proxy routes
    Route::get('/proxy/services', [ProxyController::class, 'getServices']);
    Route::post('/proxy/purchase', [ProxyController::class, 'purchase']);
    Route::get('/proxy/transactions', [ProxyController::class, 'getTransactions']);

    // Wallet routes
    Route::get('/wallet/deposits', [WalletController::class, 'getRecentDeposits']);
    Route::post('/wallet/topup/initiate', [WalletController::class, 'initiateTopUp']);
    Route::post('/wallet/topup/verify', [WalletController::class, 'verifyTopUp']);
    
    // Inbox routes
    Route::get('/inbox/messages', [InboxController::class, 'getMessages']);
    Route::get('/inbox/unread-count', [InboxController::class, 'getUnreadCount']);
    Route::post('/inbox/mark-read', [InboxController::class, 'markAsRead']);
    Route::post('/inbox/mark-all-read', [InboxController::class, 'markAllAsRead']);
    Route::get('/inbox/message/{id}', [InboxController::class, 'getMessageDetails']);
    Route::delete('/inbox/message/{id}', [InboxController::class, 'deleteMessage']);
    Route::get('/inbox/electricity-tokens', [InboxController::class, 'getElectricityTokens']);
    
    // Support Ticket routes
    Route::get('/support/tickets', [\App\Http\Controllers\SupportTicketController::class, 'index']);
    Route::post('/support/tickets', [\App\Http\Controllers\SupportTicketController::class, 'store']);
    Route::get('/support/tickets/{id}', [\App\Http\Controllers\SupportTicketController::class, 'show']);
    Route::post('/support/tickets/{id}/messages', [\App\Http\Controllers\SupportTicketController::class, 'addMessage']);
    Route::put('/support/tickets/{id}/status', [\App\Http\Controllers\SupportTicketController::class, 'updateStatus']);
    Route::put('/support/tickets/{id}/assign', [\App\Http\Controllers\SupportTicketController::class, 'assign']);
    Route::get('/support/statistics', [\App\Http\Controllers\SupportTicketController::class, 'statistics']);
    Route::get('/support/unread-count', [\App\Http\Controllers\SupportTicketController::class, 'getUnreadCount']);
    
    // Transaction details route
    Route::get('/transactions/{reference}', [VtuController::class, 'getTransactionDetails']);
    
    // API Key Management (for authenticated users)
    Route::get('/api-keys', [\App\Http\Controllers\ApiManagementController::class, 'index']);
    Route::post('/api-keys', [\App\Http\Controllers\ApiManagementController::class, 'create']);
    Route::put('/api-keys/{id}', [\App\Http\Controllers\ApiManagementController::class, 'update']);
    Route::delete('/api-keys/{id}', [\App\Http\Controllers\ApiManagementController::class, 'delete']);
    Route::get('/api-keys/usage-stats', [\App\Http\Controllers\ApiManagementController::class, 'getUsageStats']);
});

// Admin routes - V2 Sync Management DISABLED
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Route::get('/v2-sync/status', [\App\Http\Controllers\AdminController::class, 'v2SyncStatus']);
    // Route::get('/v2-sync/stats', [\App\Http\Controllers\AdminController::class, 'v2SyncStats']);
    // Route::get('/v2-sync/logs', [\App\Http\Controllers\AdminController::class, 'v2MigrationLogs']);
    
    // VTU Access Management
    Route::get('/vtu-access/users', [\App\Http\Controllers\AdminController::class, 'getVtuAccessUsers']);
    Route::get('/vtu-access/stats', [\App\Http\Controllers\AdminController::class, 'getVtuAccessStats']);
    Route::post('/vtu-access/{userId}/disable', [\App\Http\Controllers\AdminController::class, 'disableVtuAccess']);
    Route::post('/vtu-access/{userId}/enable', [\App\Http\Controllers\AdminController::class, 'enableVtuAccess']);
    
    // Provider Balances
    Route::get('/provider-balances', [\App\Http\Controllers\AdminController::class, 'getProviderBalances']);
    
    // Broadcast Notifications
    Route::get('/broadcasts', [\App\Http\Controllers\BroadcastNotificationController::class, 'index']);
    Route::post('/broadcasts', [\App\Http\Controllers\BroadcastNotificationController::class, 'store']);
    Route::post('/broadcasts/{id}/send', [\App\Http\Controllers\BroadcastNotificationController::class, 'send']);
    Route::delete('/broadcasts/{id}', [\App\Http\Controllers\BroadcastNotificationController::class, 'destroy']);
    Route::get('/broadcasts/stats', [\App\Http\Controllers\BroadcastNotificationController::class, 'stats']);
    
    // Crypto Sales Management
    Route::get('/crypto/sales', [\App\Http\Controllers\CryptoSaleController::class, 'index']);
    Route::put('/crypto/sales/{id}/status', [\App\Http\Controllers\CryptoSaleController::class, 'updateStatus']);
    Route::get('/crypto/settings', [\App\Http\Controllers\CryptoSaleController::class, 'getSettings']);
    Route::post('/crypto/settings', [\App\Http\Controllers\CryptoSaleController::class, 'updateSettings']);
    Route::get('/crypto/stats', [\App\Http\Controllers\CryptoSaleController::class, 'getStats']);
    
    // Reseller Panel Management
    Route::get('/reseller/panels', [\App\Http\Controllers\ResellerPanelController::class, 'index']);
    Route::post('/reseller/{id}/approve', [\App\Http\Controllers\ResellerPanelController::class, 'approve']);
    Route::post('/reseller/{id}/reject', [\App\Http\Controllers\ResellerPanelController::class, 'reject']);
    Route::get('/reseller/stats', [\App\Http\Controllers\ResellerPanelController::class, 'getStats']);
    // Route::post('/v2-sync/test-connection', [\App\Http\Controllers\AdminController::class, 'v2TestConnection']);
    // Route::post('/v2-sync/regenerate-key', [\App\Http\Controllers\AdminController::class, 'v2RegenerateApiKey']);
    
    // Advertisement management
    Route::get('/advertisements', [AdvertisementController::class, 'index']);
    Route::post('/advertisements', [AdvertisementController::class, 'store']);
    Route::put('/advertisements/{advertisement}', [AdvertisementController::class, 'update']);
    Route::delete('/advertisements/{advertisement}', [AdvertisementController::class, 'destroy']);
    Route::patch('/advertisements/{advertisement}/toggle', [AdvertisementController::class, 'toggleStatus']);
});

// Reseller API v1 - Authenticated with API Key
Route::prefix('v1')->middleware('api.key')->group(function () {
    // Info & Balance
    Route::get('/info', [\App\Http\Controllers\ResellerApiController::class, 'getInfo']);
    Route::get('/balance', [\App\Http\Controllers\ResellerApiController::class, 'getBalance']);
    
    // SMS Services
    Route::post('/sms/order', [\App\Http\Controllers\ResellerApiController::class, 'purchaseSms'])
        ->middleware('api.key:sms');
    Route::post('/sms/code', [\App\Http\Controllers\ResellerApiController::class, 'getSmsCode'])
        ->middleware('api.key:sms');
    
    // VTU Services
    Route::post('/airtime', [\App\Http\Controllers\ResellerApiController::class, 'purchaseAirtime'])
        ->middleware('api.key:vtu');
    Route::post('/data', [\App\Http\Controllers\ResellerApiController::class, 'purchaseData'])
        ->middleware('api.key:vtu');
    Route::post('/electricity', [\App\Http\Controllers\ResellerApiController::class, 'purchaseElectricity'])
        ->middleware('api.key:vtu');
    
    // Transactions
    Route::get('/transactions', [\App\Http\Controllers\ResellerApiController::class, 'getTransactions']);
    Route::get('/transactions/{reference}', [\App\Http\Controllers\ResellerApiController::class, 'getTransaction']);
});

Route::post('/webhooks/payvibe', [WalletController::class, 'handlePayVibeWebhook']);

// V2 Sync API - DISABLED - No longer syncing with old V2 site
// Route::prefix('v2-sync')->group(function () {
//     Route::post('/get-user', [\App\Http\Controllers\V2SyncController::class, 'getUser']);
//     Route::post('/verify-user', [\App\Http\Controllers\V2SyncController::class, 'verifyUser']);
//     Route::post('/update-balance', [\App\Http\Controllers\V2SyncController::class, 'updateBalance']);
//     Route::post('/batch-users', [\App\Http\Controllers\V2SyncController::class, 'batchGetUsers']);
//     Route::post('/create-user', [\App\Http\Controllers\V2SyncController::class, 'createUser']);
// });

// Referral System API
Route::prefix('referrals')->middleware('auth:sanctum')->group(function () {
    Route::get('/stats', [\App\Http\Controllers\ReferralController::class, 'getStats']);
    Route::get('/link', [\App\Http\Controllers\ReferralController::class, 'getReferralLink']);
    Route::get('/commissions', [\App\Http\Controllers\ReferralController::class, 'getCommissions']);
    Route::post('/payout', [\App\Http\Controllers\ReferralController::class, 'requestPayout']);
    Route::get('/leaderboard', [\App\Http\Controllers\ReferralController::class, 'getLeaderboard']);
    Route::get('/tiers', [\App\Http\Controllers\ReferralController::class, 'getTierInfo']);
    Route::post('/generate-code', [\App\Http\Controllers\ReferralController::class, 'generateReferralCode']);
});

// Public referral validation (for registration)
Route::post('/referrals/validate', [\App\Http\Controllers\ReferralController::class, 'validateReferralCode']);

// Crypto Sales System (Now protected - requires authentication)
Route::middleware('auth:sanctum')->get('/crypto/rates', [\App\Http\Controllers\CryptoSaleController::class, 'getRates']);

// Reseller Branding & Payment Config - Public (needed for frontend initialization before login)
Route::get('/branding', [\App\Http\Controllers\ResellerPanelController::class, 'getBrandingByDomain']);
Route::get('/payment-config', [\App\Http\Controllers\ResellerPanelController::class, 'getPaymentConfig']);

Route::prefix('crypto')->middleware('auth:sanctum')->group(function () {
    Route::post('/sell', [\App\Http\Controllers\CryptoSaleController::class, 'store']);
    Route::get('/my-sales', [\App\Http\Controllers\CryptoSaleController::class, 'getUserSales']);
});

// Reseller Panel System
Route::prefix('reseller')->middleware('auth:sanctum')->group(function () {
    Route::post('/apply', [\App\Http\Controllers\ResellerPanelController::class, 'apply']);
    Route::get('/my-panel', [\App\Http\Controllers\ResellerPanelController::class, 'getMyPanel']);
    Route::get('/dns-instructions', [\App\Http\Controllers\ResellerPanelController::class, 'getDNSInstructions']);
    Route::post('/custom-domain', [\App\Http\Controllers\ResellerPanelController::class, 'addCustomDomain']);
    Route::put('/settings', [\App\Http\Controllers\ResellerPanelController::class, 'updateSettings']);
    Route::post('/payment-gateway', [\App\Http\Controllers\ResellerPanelController::class, 'configurePaymentGateway']);
});
