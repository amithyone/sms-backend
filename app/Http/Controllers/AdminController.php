<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\SmsOrder;
use App\Models\VtuOrder;
use App\Models\Deposit;
use App\Models\SmsService;
use App\Models\VtuService;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Admin dashboard data
     */
    public function dashboard()
    {
        // For non-API requests, serve the Blade view without forcing server-side auth.
        // The page bootstraps itself by calling the protected API endpoint with a token.
        if (!request()->expectsJson()) {
            return view('admin.dashboard');
        }

        $user = Auth::user();

        // Check if user is admin (API calls must be authenticated)
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        // Get dashboard statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_transactions' => Transaction::count(),
            'total_deposits' => Deposit::count(),
            'total_sms_orders' => SmsOrder::count(),
            'total_vtu_orders' => VtuOrder::count(),
            'total_revenue' => Transaction::where('type', 'credit')->sum('amount'),
            'total_user_balance' => User::sum('balance'),
            'pending_deposits' => Deposit::where('status', 'pending')->count(),
        ];

        // Get recent activities
        $recentUsers = User::latest()->take(5)->get(['id', 'name', 'email', 'role', 'created_at']);
        $recentTransactions = Transaction::with('user:id,name,email')
            ->latest()
            ->take(10)
            ->get(['id', 'user_id', 'type', 'amount', 'description', 'status', 'created_at']);
        $recentDeposits = Deposit::with('user:id,name,email')
            ->latest()
            ->take(10)
            ->get(['id', 'user_id', 'amount', 'status', 'reference', 'created_at']);

        // Check if request expects JSON (API call)
        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => $stats,
                    'recent_users' => $recentUsers,
                    'recent_transactions' => $recentTransactions,
                    'recent_deposits' => $recentDeposits,
                ]
            ]);
        }

        // Return view for web requests
        return view('admin.dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentTransactions' => $recentTransactions,
            'recentDeposits' => $recentDeposits,
        ]);
    }

    /**
     * Get all users with pagination
     */
    public function users(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $query = User::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->has('role')) {
            $query->where('role', $request->get('role'));
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $users = $query->with('referrer:id,name,email')
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Get specific user details
     */
    public function getUser($id): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $targetUser = User::with(['referrer:id,name,email', 'referrals:id,name,email,created_at'])
            ->findOrFail($id);

        // Get user's transactions
        $transactions = $targetUser->transactions()
            ->with('service:id,name')
            ->latest()
            ->take(20)
            ->get();

        // Get user's deposits
        $deposits = $targetUser->deposits()
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $targetUser,
                'transactions' => $transactions,
                'deposits' => $deposits,
            ]
        ]);
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,suspended',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $targetUser = User::findOrFail($id);
        
        // Prevent admin from changing super admin status
        if ($targetUser->isSuperAdmin() && !($user instanceof \App\Models\User) ? true : !$user->isSuperAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot modify super admin status'
            ], 403);
        }

        $targetUser->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User status updated successfully',
            'data' => [
                'user' => $targetUser->only(['id', 'name', 'email', 'status', 'role'])
            ]
        ]);
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isSuperAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Super admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:user,admin,super_admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $targetUser = User::findOrFail($id);
        $targetUser->update(['role' => $request->role]);

        return response()->json([
            'status' => 'success',
            'message' => 'User role updated successfully',
            'data' => [
                'user' => $targetUser->only(['id', 'name', 'email', 'role'])
            ]
        ]);
    }

    /**
     * Adjust user balance (admin action)
     */
    public function updateUserBalance(Request $request, $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'action' => 'required|string|in:add,subtract',
            'note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);
        $amount = (float)$request->amount;
        $action = $request->action;

        $before = $user->balance;
        if ($action === 'add') {
            $user->updateBalance($amount, 'add');
            $type = 'credit';
            $desc = 'Admin balance top-up';
        } else {
            if ($user->balance < $amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient user balance to deduct'
                ], 400);
            }
            $user->updateBalance($amount, 'subtract');
            $type = 'debit';
            $desc = 'Admin balance deduction';
        }

        Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $user->balance,
            'description' => $desc,
            'reference' => 'ADM_' . substr(md5(uniqid('', true)), 0, 12),
            'status' => 'success',
            'metadata' => [ 'admin_id' => $admin->id, 'note' => $request->note ]
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User balance updated',
            'data' => [ 'balance' => $user->balance ]
        ]);
    }

    /**
     * Get all transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $query = Transaction::with(['user:id,name,email', 'service:id,name']);

        // Search filter
        if ($request->filled('search')) {
            $search = (string)$request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Type filter
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->get('to_date'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = strtolower((string)$request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at','amount','status','type'];
        if (!in_array($sortBy, $allowed, true)) { $sortBy = 'created_at'; }
        $query->orderBy($sortBy, $sortDir);

        $perPage = (int)$request->get('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }

    /**
     * Get all deposits
     */
    public function deposits(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $query = Deposit::with(['user:id,name,email']);

        // Search filter
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Date range
        if ($request->filled('from_date')) { $query->whereDate('created_at', '>=', $request->get('from_date')); }
        if ($request->filled('to_date')) { $query->whereDate('created_at', '<=', $request->get('to_date')); }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = strtolower((string)$request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at','amount','status'];
        if (!in_array($sortBy, $allowed, true)) { $sortBy = 'created_at'; }
        $query->orderBy($sortBy, $sortDir);

        $perPage = (int)$request->get('per_page', 20);
        $deposits = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $deposits
        ]);
    }

    /**
     * Update deposit status
     */
    public function updateDepositStatus(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,failed,cancelled',
            'admin_note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $deposit = Deposit::findOrFail($id);
        
        if ($deposit->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Deposit status cannot be changed'
            ], 400);
        }

        DB::beginTransaction();
        
        try {
            // Get existing metadata
            $metadata = is_array($deposit->metadata) ? $deposit->metadata : 
                       (is_string($deposit->metadata) ? json_decode($deposit->metadata, true) : []);
            
            // Add admin processing info to metadata
            $metadata['admin_note'] = $request->admin_note;
            $metadata['processed_at'] = now()->toDateTimeString();
            $metadata['processed_by'] = $user->id;
            $metadata['processed_by_name'] = $user->name;
            
            $deposit->update([
                'status' => $request->status,
                'metadata' => json_encode($metadata),
            ]);

            // If completed (approved), credit user's balance
            if ($request->status === 'completed') {
                $depositUser = $deposit->user;
                $balanceBefore = $depositUser->balance;
                $depositUser->updateBalance($deposit->amount, 'add');
                
                // Create transaction record
                Transaction::create([
                    'user_id' => $deposit->user_id,
                    'type' => Transaction::TYPE_DEPOSIT,
                    'amount' => $deposit->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $depositUser->fresh()->balance,
                    'description' => 'Deposit approved - ' . $deposit->reference,
                    'reference' => $deposit->reference,
                    'status' => Transaction::STATUS_SUCCESS,
                    'metadata' => [
                        'admin_id' => $user->id,
                        'deposit_id' => $deposit->id,
                        'admin_note' => $request->admin_note
                    ]
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Deposit status updated successfully',
                'data' => [
                    'deposit' => $deposit->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update deposit status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system statistics
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        // Get monthly statistics
        $monthlyStats = [
            'users' => User::whereMonth('created_at', now()->month)->count(),
            'transactions' => Transaction::whereMonth('created_at', now()->month)->count(),
            'revenue' => Transaction::whereMonth('created_at', now()->month)
                ->where('type', 'credit')
                ->sum('amount'),
            'deposits' => Deposit::whereMonth('created_at', now()->month)->count(),
        ];

        // Get daily statistics for the last 30 days
        $dailyStats = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyStats[] = [
                'date' => $date,
                'users' => User::whereDate('created_at', $date)->count(),
                'transactions' => Transaction::whereDate('created_at', $date)->count(),
                'revenue' => Transaction::whereDate('created_at', $date)
                    ->where('type', 'credit')
                    ->sum('amount'),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'monthly' => $monthlyStats,
                'daily' => $dailyStats,
            ]
        ]);
    }

    /**
     * List SMS orders (paginated)
     */
    public function listSmsOrders(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $query = SmsOrder::with(['user:id,name,email', 'smsService:id,name,provider']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->has('service')) {
            $query->where('service', $request->get('service'));
        }
        if ($request->has('country')) {
            $query->where('country', $request->get('country'));
        }

        $orders = $query->latest()->paginate(20);

        // If no SMS orders exist yet, backfill from transactions as a fallback
        if ($orders->total() === 0) {
            $perPage = (int) $request->get('per_page', 20);
            $txQ = DB::table('transactions')
                ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
                ->where('transactions.type', 'service_purchase')
                ->where('transactions.status', 'success')
                ->where(function ($q) {
                    $q->where('description', 'like', '%SMS verification%')
                      ->orWhere('metadata->provider', '5sim')
                      ->orWhere('metadata->provider', 'dassy')
                      ->orWhere('metadata->provider', 'tiger_sms')
                      ->orWhere('metadata->provider', 'textverified');
                })
                ->orderByDesc('transactions.created_at')
                ->select(
                    'transactions.reference',
                    'transactions.amount',
                    'transactions.description',
                    'transactions.metadata',
                    'transactions.created_at',
                    'users.name as user_name',
                    'users.email as user_email'
                );

            if ($request->filled('service')) {
                $svc = $request->get('service');
                $txQ->where('metadata->service', $svc);
            }
            if ($request->filled('country')) {
                $country = strtolower((string)$request->get('country'));
                $txQ->where('description', 'like', '%(' . $country . ')%');
            }

            $tx = $txQ->paginate($perPage);
            $items = collect($tx->items())->map(function ($row) {
                $meta = is_string($row->metadata) ? json_decode($row->metadata, true) : (array)$row->metadata;
                $desc = (string)($row->description ?? '');
                // Try to parse country from description pattern: "(...country...)"
                $country = null;
                if (preg_match('/\(([^)]+)\)/', $desc, $m)) { $country = $m[1]; }
                return [
                    'order_id' => $meta['order_id'] ?? ($row->reference ?? ''),
                    'user' => [ 'name' => $row->user_name, 'email' => $row->user_email ],
                    'phone_number' => $meta['phone_number'] ?? '',
                    'service' => $meta['service'] ?? '',
                    'country' => $country ?? '',
                    'sms_service' => [ 'name' => isset($meta['provider']) ? ucfirst((string)$meta['provider']) : 'SMS' ],
                    'cost' => 0,
                    'status' => 'completed',
                    'created_at' => (string)$row->created_at,
                ];
            })->values();

            $payload = [
                'current_page' => $tx->currentPage(),
                'per_page' => $tx->perPage(),
                'total' => $tx->total(),
                'data' => $items,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $payload
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * List VTU orders (paginated)
     */
    public function listVtuOrders(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        // Backfill from transactions since VTU orders may not be consistently written yet
        $perPage = (int) $request->get('per_page', 20);
        $query = DB::table('transactions')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactions.type', 'service_purchase')
            ->where('transactions.status', 'success')
            ->where(function ($q) {
                $q->where('metadata->provider', 'vtu_ng')
                  ->orWhere('description', 'like', '%Airtime purchase%')
                  ->orWhere('description', 'like', '%Data bundle purchase%')
                  ->orWhere('description', 'like', '%Electricity bill%')
                  ->orWhere('description', 'like', '%Betting funding%');
            })
            ->orderByDesc('transactions.created_at')
            ->select(
                'transactions.id',
                'transactions.reference',
                'transactions.amount',
                'transactions.description',
                'transactions.metadata',
                'transactions.created_at',
                'users.name as user_name',
                'users.email as user_email'
            );

        // Optional filters
        if ($request->filled('network')) {
            $network = $request->get('network');
            $query->where(function ($q) use ($network) {
                $q->where('metadata->network', $network)
                  ->orWhere('metadata->service_id', $network)
                  ->orWhere('description', 'like', "%{$network}%");
            });
        }
        if ($request->filled('type')) {
            $type = strtolower($request->get('type'));
            $query->where(function ($q) use ($type) {
                if ($type === 'airtime') {
                    $q->where('description', 'like', '%Airtime purchase%');
                } elseif ($type === 'data') {
                    $q->where('description', 'like', '%Data bundle purchase%');
                } elseif ($type === 'electricity') {
                    $q->where('description', 'like', '%Electricity bill%');
                } elseif ($type === 'betting') {
                    $q->where('description', 'like', '%Betting funding%');
                }
            });
        }

        $tx = $query->paginate($perPage);

        // Transform items to normalized VTU order rows
        $items = collect($tx->items())->map(function ($row) {
            $meta = is_string($row->metadata) ? json_decode($row->metadata, true) : (array)$row->metadata;
            $desc = strtolower((string)($row->description ?? ''));
            $category = $meta['category'] ?? (
                str_contains($desc, 'airtime') ? 'airtime' : (
                    str_contains($desc, 'data') ? 'data' : (
                        str_contains($desc, 'electricity') ? 'electricity' : (
                            str_contains($desc, 'betting') ? 'betting' : 'vtu'
                        )
                    )
                )
            );
            return [
                'reference' => $row->reference,
                'user' => [ 'name' => $row->user_name, 'email' => $row->user_email ],
                'type' => $category,
                'network' => $meta['network'] ?? ($meta['service_id'] ?? ''),
                'phone' => $meta['phone'] ?? ($meta['customer_id'] ?? ''),
                'amount' => (float)$row->amount,
                'status' => 'completed',
                'created_at' => (string)$row->created_at,
            ];
        })->values();

        $payload = [
            'current_page' => $tx->currentPage(),
            'per_page' => $tx->perPage(),
            'total' => $tx->total(),
            'data' => $items,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    /**
     * Get pricing settings (markup, currency, auto FX)
     */
    public function getPricingSettings(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        // VTU Settings (legacy)
        $markup = $this->getSettingValue('pricing.markup_percent', 'number', 10.0);
        $currency = $this->getSettingValue('pricing.currency', 'string', 'NGN');
        $autoFx = $this->getSettingValue('pricing.auto_fx', 'boolean', true);

        // SMS Settings from settings table
        $smsFxRate = (float) (DB::table('settings')->where('key', 'sms_fx_ngn_per_usd')->value('value') ?? 1600);
        $smsProfitMargin = (float) (DB::table('settings')->where('key', 'sms_profit_margin')->value('value') ?? 15);
        $smsMinPrice = (float) (DB::table('settings')->where('key', 'sms_min_price')->value('value') ?? 1500);
        $smsVat = (float) (DB::table('settings')->where('key', 'sms_vat')->value('value') ?? 700);
        $smsMarkup = (float) (DB::table('settings')->where('key', 'sms_markup_percent')->value('value') ?? 10);

        return response()->json([
            'status' => 'success',
            'data' => [
                // VTU Settings
                'markup_percent' => (float)$markup,
                'currency' => (string)$currency,
                'auto_fx' => (bool)$autoFx,
                
                // SMS Settings
                'sms_fx_rate' => $smsFxRate,
                'sms_profit_margin' => $smsProfitMargin,
                'sms_min_price' => $smsMinPrice,
                'sms_vat' => $smsVat,
                'sms_markup' => $smsMarkup,
            ]
        ]);
    }

    /**
     * Update pricing settings (markup, currency, auto FX)
     */
    public function updatePricingSettings(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            // VTU Settings
            'markup_percent' => 'sometimes|numeric|min:0|max:100',
            'currency' => 'sometimes|string|in:NGN,USD,EUR,GBP',
            'auto_fx' => 'sometimes|boolean',
            
            // SMS Settings
            'sms_fx_rate' => 'sometimes|numeric|min:1000|max:3000',
            'sms_profit_margin' => 'sometimes|numeric|min:0|max:100',
            'sms_min_price' => 'sometimes|numeric|min:100|max:10000',
            'sms_vat' => 'sometimes|numeric|min:0|max:5000',
            'sms_markup' => 'sometimes|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Update VTU Settings
        if (isset($data['markup_percent'])) {
            $this->putSettingValue('pricing.markup_percent', (string)$data['markup_percent'], 'number', 'pricing');
        }
        if (isset($data['currency'])) {
            $this->putSettingValue('pricing.currency', (string)$data['currency'], 'string', 'pricing');
        }
        if (array_key_exists('auto_fx', $data)) {
            $this->putSettingValue('pricing.auto_fx', $data['auto_fx'] ? '1' : '0', 'boolean', 'pricing');
        }

        // Update SMS Settings
        if (isset($data['sms_fx_rate'])) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'sms_fx_ngn_per_usd'],
                ['value' => (string)$data['sms_fx_rate'], 'updated_at' => now()]
            );
        }
        if (isset($data['sms_profit_margin'])) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'sms_profit_margin'],
                ['value' => (string)$data['sms_profit_margin'], 'updated_at' => now()]
            );
        }
        if (isset($data['sms_min_price'])) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'sms_min_price'],
                ['value' => (string)$data['sms_min_price'], 'updated_at' => now()]
            );
        }
        if (isset($data['sms_vat'])) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'sms_vat'],
                ['value' => (string)$data['sms_vat'], 'updated_at' => now()]
            );
        }
        if (isset($data['sms_markup'])) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'sms_markup_percent'],
                ['value' => (string)$data['sms_markup'], 'updated_at' => now()]
            );
        }

        // Clear cache
        \Artisan::call('config:clear');

        return response()->json([
            'status' => 'success',
            'message' => 'Pricing settings updated successfully',
            'data' => [
                'markup_percent' => (float)$this->getSettingValue('pricing.markup_percent', 'number', 10.0),
                'currency' => (string)$this->getSettingValue('pricing.currency', 'string', 'NGN'),
                'auto_fx' => (bool)$this->getSettingValue('pricing.auto_fx', 'boolean', true),
                'sms_fx_rate' => (float) (DB::table('settings')->where('key', 'sms_fx_ngn_per_usd')->value('value') ?? 1600),
                'sms_profit_margin' => (float) (DB::table('settings')->where('key', 'sms_profit_margin')->value('value') ?? 15),
                'sms_min_price' => (float) (DB::table('settings')->where('key', 'sms_min_price')->value('value') ?? 1500),
                'sms_vat' => (float) (DB::table('settings')->where('key', 'sms_vat')->value('value') ?? 700),
                'sms_markup' => (float) (DB::table('settings')->where('key', 'sms_markup_percent')->value('value') ?? 10),
            ]
        ]);
    }

    private function getSettingValue(string $key, string $type = 'string', mixed $default = null): mixed
    {
        $row = Setting::where('key', $key)->first();
        if (!$row) { return $default; }
        return match ($type) {
            'boolean' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($row->value) ? (float)$row->value : $default,
            'json' => json_decode($row->value, true),
            default => $row->value,
        };
    }

    private function putSettingValue(string $key, string $value, string $type = 'string', string $group = 'general'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );
    }

    /**
     * Admin: Combined list of API services (SMS + VTU)
     */
    public function listApiServices(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $search = (string)($request->get('search') ?? '');
        $category = (string)($request->get('category') ?? ''); // 'sms' | 'vtu' | ''
        $isActive = $request->has('is_active') ? $request->get('is_active') : null; // '1' | '0' | null

        $items = collect();

        if ($category === '' || $category === 'sms') {
            $q = SmsService::query();
            if ($search !== '') { $q->where('name', 'like', "%{$search}%")->orWhere('provider', 'like', "%{$search}%"); }
            if ($isActive !== null && $isActive !== '') { $q->where('is_active', (bool)$isActive); }
            $sms = $q->orderBy('priority')->get()->map(function ($s) {
                $apiKey = (string)($s->api_key ?? '');
                $apiKeyMasked = $apiKey !== '' ? (str_repeat('*', max(0, strlen($apiKey) - 4)) . substr($apiKey, -4)) : null;
                return [
                    'id' => $s->id,
                    'type' => 'SMS',
                    'name' => $s->name,
                    'provider' => $s->provider,
                    'is_active' => (bool)$s->is_active,
                    'balance' => (float)($s->balance ?? 0),
                    'priority' => (int)($s->priority ?? 0),
                    'success_rate' => (float)($s->success_rate ?? 0),
                    'api_url' => (string)($s->api_url ?? ''),
                    'api_key_masked' => $apiKeyMasked,
                ];
            });
            $items = $items->merge($sms);
        }

        if ($category === '' || $category === 'vtu') {
            $q2 = VtuService::query();
            if ($search !== '') { $q2->where('name', 'like', "%{$search}%")->orWhere('provider', 'like', "%{$search}%"); }
            if ($isActive !== null && $isActive !== '') { $q2->where('is_active', (bool)$isActive); }
            $vtu = $q2->orderBy('priority')->get()->map(function ($s) {
                $apiKey = (string)($s->api_key ?? '');
                $apiKeyMasked = $apiKey !== '' ? (str_repeat('*', max(0, strlen($apiKey) - 4)) . substr($apiKey, -4)) : null;
                return [
                    'id' => $s->id,
                    'type' => 'VTU',
                    'name' => $s->name,
                    'provider' => $s->provider,
                    'is_active' => (bool)$s->is_active,
                    'balance' => (float)($s->balance ?? 0),
                    'priority' => (int)($s->priority ?? 0),
                    'success_rate' => (float)($s->success_rate ?? 0),
                    'api_url' => (string)($s->api_url ?? ''),
                    'username' => (string)($s->username ?? ''),
                    'password_masked' => isset($s->password) && $s->password !== '' ? '********' : null,
                    'pin_masked' => isset($s->pin) && $s->pin !== '' ? '****' : null,
                    'api_key_masked' => $apiKeyMasked,
                ];
            });
            $items = $items->merge($vtu);
        }

        // Sort by type then priority-like fields
        $items = $items->sortBy([['type','asc'],['priority','asc']])->values();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    /**
     * Update SMS service fields (editable: name, api_key, api_url, is_active, priority, balance)
     */
    public function updateSmsService(Request $request, int $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'api_key' => 'sometimes|nullable|string',
            'api_url' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'balance' => 'sometimes|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $svc = SmsService::findOrFail($id);
        $svc->fill($validator->validated());
        $svc->save();
        return response()->json(['status' => 'success', 'message' => 'SMS service updated']);
    }

    /**
     * Update VTU service fields (editable: name, api_key, username, password, pin, api_url, is_active, priority, balance)
     */
    public function updateVtuService(Request $request, int $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'api_key' => 'sometimes|nullable|string',
            'username' => 'sometimes|nullable|string|max:255',
            'password' => 'sometimes|nullable|string|max:255',
            'pin' => 'sometimes|nullable|string|max:50',
            'api_url' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'balance' => 'sometimes|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $svc = VtuService::findOrFail($id);
        $svc->fill($validator->validated());
        $svc->save();
        return response()->json(['status' => 'success', 'message' => 'VTU service updated']);
    }

    /**
     * V2 Migration - Get sync status
     */
    public function v2SyncStatus(): JsonResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }

        $apiKeyConfigured = !empty(env('V2_SYNC_API_KEY'));
        $syncedUsers = DB::table('transactions')
            ->where('metadata', 'like', '%"source":"v2_sync"%')
            ->distinct('user_id')
            ->count();

        $totalV2Transactions = DB::table('transactions')
            ->where('metadata', 'like', '%"source":"v2_sync"%')
            ->count();

        $recentSyncs = DB::table('transactions')
            ->where('metadata', 'like', '%"source":"v2_sync"%')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['user_id', 'amount', 'description', 'reference', 'created_at']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'api_configured' => $apiKeyConfigured,
                'api_key' => $apiKeyConfigured ? substr(env('V2_SYNC_API_KEY'), 0, 20) . '...' : null,
                'synced_users_count' => $syncedUsers,
                'total_v2_transactions' => $totalV2Transactions,
                'recent_syncs' => $recentSyncs,
                'endpoints' => [
                    'base_url' => url('/api/v2-sync'),
                    'get_user' => url('/api/v2-sync/get-user'),
                    'update_balance' => url('/api/v2-sync/update-balance'),
                    'verify_user' => url('/api/v2-sync/verify-user'),
                ]
            ]
        ]);
    }

    /**
     * V2 Migration - Test connection from V2 site
     */
    public function v2TestConnection(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'v2_api_url' => 'required|url',
            'v2_api_key' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Test connection to V2 site
            $ch = curl_init($request->v2_api_url . '/test');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-V2-API-Key: ' . $request->v2_api_key
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'connection' => $httpCode === 200 ? 'working' : 'failed',
                    'http_code' => $httpCode,
                    'response' => $response
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Connection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * V2 Migration - Get migration logs
     */
    public function v2MigrationLogs(): JsonResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }

        // Get users with V2 sync transactions
        $migratedUsers = DB::table('users')
            ->join('transactions', 'users.id', '=', 'transactions.user_id')
            ->where('transactions.metadata', 'like', '%"source":"v2_sync"%')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.balance',
                DB::raw('COUNT(transactions.id) as v2_transaction_count'),
                DB::raw('SUM(transactions.amount) as total_v2_amount'),
                DB::raw('MAX(transactions.created_at) as last_sync')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'users.balance')
            ->orderBy('last_sync', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'migrated_users' => $migratedUsers,
                'total_migrated' => $migratedUsers->count()
            ]
        ]);
    }

    /**
     * V2 Migration - Regenerate API key
     */
    public function v2RegenerateApiKey(): JsonResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }

        $newApiKey = 'v2sync_' . bin2hex(random_bytes(32));

        // Update .env file
        $envFile = base_path('.env');
        if (!file_exists($envFile)) {
            return response()->json([
                'status' => 'error',
                'message' => '.env file not found'
            ], 500);
        }

        $envContent = file_get_contents($envFile);
        
        if (strpos($envContent, 'V2_SYNC_API_KEY=') !== false) {
            $envContent = preg_replace('/V2_SYNC_API_KEY=.*/', "V2_SYNC_API_KEY={$newApiKey}", $envContent);
        } else {
            $envContent .= "\nV2_SYNC_API_KEY={$newApiKey}\n";
        }

        file_put_contents($envFile, $envContent);

        // Clear config cache
        \Artisan::call('config:clear');

        return response()->json([
            'status' => 'success',
            'data' => [
                'new_api_key' => $newApiKey,
                'message' => 'API key regenerated. Update V2 site with new key!'
            ]
        ]);
    }

    /**
     * V2 Migration - Get statistics
     */
    public function v2SyncStats(): JsonResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }

        $stats = [
            'total_users_with_v2_activity' => DB::table('transactions')
                ->where('metadata', 'like', '%"source":"v2_sync"%')
                ->distinct('user_id')
                ->count(),
            
            'total_v2_transactions' => DB::table('transactions')
                ->where('metadata', 'like', '%"source":"v2_sync"%')
                ->count(),
            
            'total_v2_debits' => DB::table('transactions')
                ->where('metadata', 'like', '%"source":"v2_sync"%')
                ->where('type', 'service_purchase')
                ->sum('amount'),
            
            'total_v2_credits' => DB::table('transactions')
                ->where('metadata', 'like', '%"source":"v2_sync"%')
                ->where('type', 'deposit')
                ->sum('amount'),
            
            'last_v2_sync' => DB::table('transactions')
                ->where('metadata', 'like', '%"source":"v2_sync"%')
                ->max('created_at'),
            
            'v2_syncs_today' => DB::table('transactions')
                ->where('metadata', 'like', '%"source":"v2_sync"%')
                ->whereDate('created_at', today())
                ->count(),
            
            'v2_syncs_this_week' => DB::table('transactions')
                ->where('metadata', 'like', '%"source":"v2_sync"%')
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Get refundable transactions
     * Returns successful transactions that can be refunded
     */
    public function getRefundableTransactions(Request $request): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $query = Transaction::with(['user:id,name,email', 'service:id,name'])
            ->where('status', Transaction::STATUS_SUCCESS)
            ->whereIn('type', [
                Transaction::TYPE_SERVICE_PURCHASE,
                Transaction::TYPE_DEBIT
            ]);

        // Search filter
        if ($request->filled('search')) {
            $search = (string)$request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->get('to_date'));
        }

        // Exclude already refunded transactions
        $query->where(function($q) {
            $q->whereNull('metadata->refunded')
              ->orWhereJsonDoesntContain('metadata->refunded', true);
        });

        $perPage = (int)$request->get('per_page', 20);
        $transactions = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }

    /**
     * Refund a transaction
     * Returns money to user's balance
     */
    public function refundTransaction(Request $request, $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'amount' => 'nullable|numeric|min:0.01', // Optional: partial refund
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = Transaction::with('user')->findOrFail($id);

        // Check if transaction can be refunded
        if ($transaction->status !== Transaction::STATUS_SUCCESS) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only successful transactions can be refunded'
            ], 400);
        }

        // Check if already refunded
        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
        if (isset($metadata['refunded']) && $metadata['refunded'] === true) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction has already been refunded'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $refundAmount = $request->filled('amount') 
                ? (float)$request->amount 
                : (float)$transaction->amount;

            // Validate refund amount
            if ($refundAmount > (float)$transaction->amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Refund amount cannot exceed original transaction amount'
                ], 400);
            }

            $user = $transaction->user;
            $balanceBefore = $user->balance;

            // Credit user's balance
            $user->updateBalance($refundAmount, 'add');

            // Update original transaction metadata
            $metadata['refunded'] = true;
            $metadata['refund_amount'] = $refundAmount;
            $metadata['refund_reason'] = $request->reason;
            $metadata['refunded_by'] = $admin->id;
            $metadata['refunded_at'] = now()->toDateTimeString();
            
            $transaction->update(['metadata' => $metadata]);

            // Create refund transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => Transaction::TYPE_REFUND,
                'amount' => $refundAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->fresh()->balance,
                'description' => 'Refund: ' . $transaction->description,
                'reference' => 'REF_' . $transaction->reference,
                'status' => Transaction::STATUS_SUCCESS,
                'metadata' => [
                    'original_transaction_id' => $transaction->id,
                    'original_reference' => $transaction->reference,
                    'refund_reason' => $request->reason,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'refund_type' => $refundAmount < (float)$transaction->amount ? 'partial' : 'full'
                ]
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction refunded successfully',
                'data' => [
                    'refund_amount' => $refundAmount,
                    'new_balance' => $user->fresh()->balance,
                    'transaction' => $transaction->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending deposits (for admin dashboard quick view)
     */
    public function getPendingDeposits(Request $request): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $pendingDeposits = Deposit::with(['user:id,name,email'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $pendingDeposits
        ]);
    }

    /**
     * Update transaction status
     * Allows admin to manually change transaction status
     */
    public function updateTransactionStatus(Request $request, $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,success,failed,cancelled',
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = Transaction::with('user')->findOrFail($id);
        $oldStatus = $transaction->status;
        $newStatus = $request->status;

        // Prevent changing already successful transactions to failed (use refund instead)
        if ($oldStatus === Transaction::STATUS_SUCCESS && $newStatus === Transaction::STATUS_FAILED) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot change successful transaction to failed. Use refund feature instead.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Get existing metadata
            $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
            
            // Add status change info to metadata
            $statusChanges = $metadata['status_changes'] ?? [];
            $statusChanges[] = [
                'from' => $oldStatus,
                'to' => $newStatus,
                'changed_by' => $admin->id,
                'changed_by_name' => $admin->name,
                'changed_at' => now()->toDateTimeString(),
                'note' => $request->admin_note
            ];
            $metadata['status_changes'] = $statusChanges;
            $metadata['admin_modified'] = true;
            
            // Update transaction
            $transaction->update([
                'status' => $newStatus,
                'metadata' => $metadata
            ]);

            // Handle balance adjustments based on status change
            $user = $transaction->user;
            $balanceChanged = false;

            // If changing pending to success for credit types, credit the balance
            if ($oldStatus === Transaction::STATUS_PENDING && 
                $newStatus === Transaction::STATUS_SUCCESS &&
                in_array($transaction->type, [Transaction::TYPE_CREDIT, Transaction::TYPE_DEPOSIT])) {
                
                $user->updateBalance($transaction->amount, 'add');
                $balanceChanged = true;
            }

            // If changing pending to failed for debit that already deducted, refund it
            if ($oldStatus === Transaction::STATUS_PENDING && 
                $newStatus === Transaction::STATUS_FAILED &&
                in_array($transaction->type, [Transaction::TYPE_DEBIT, Transaction::TYPE_SERVICE_PURCHASE])) {
                
                // Check if balance was already deducted
                if ($transaction->balance_before && $transaction->balance_after) {
                    $deducted = $transaction->balance_before - $transaction->balance_after;
                    if ($deducted > 0) {
                        $user->updateBalance($deducted, 'add');
                        $balanceChanged = true;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction status updated successfully',
                'data' => [
                    'transaction' => $transaction->fresh(),
                    'balance_changed' => $balanceChanged,
                    'new_balance' => $balanceChanged ? $user->fresh()->balance : null
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update transaction status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction details with related deposit info
     */
    public function getTransactionWithDeposit($id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $transaction = Transaction::with(['user:id,name,email', 'service:id,name'])
            ->findOrFail($id);

        // If this is a deposit transaction, try to find related deposit
        $deposit = null;
        if ($transaction->type === Transaction::TYPE_DEPOSIT) {
            $deposit = Deposit::where('reference', $transaction->reference)
                ->orWhere('user_id', $transaction->user_id)
                ->where('amount', $transaction->amount)
                ->first();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'transaction' => $transaction,
                'deposit' => $deposit
            ]
        ]);
    }

    /**
     * Manually update SMS provider balance
     * For providers that don't support automatic balance checking
     */
    public function updateSmsProviderBalance(Request $request, $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'balance' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $smsService = SmsService::findOrFail($id);
            $oldBalance = $smsService->balance;
            
            $smsService->updateBalance((float) $request->balance);

            Log::info('SMS provider balance manually updated', [
                'provider' => $smsService->provider,
                'provider_name' => $smsService->name,
                'old_balance' => $oldBalance,
                'new_balance' => $smsService->balance,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Provider balance updated successfully',
                'data' => [
                    'provider' => $smsService->provider,
                    'provider_name' => $smsService->name,
                    'old_balance' => $oldBalance,
                    'new_balance' => $smsService->balance,
                    'last_updated' => $smsService->last_balance_check
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update SMS provider balance', [
                'provider_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update provider balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh SMS provider balance
     * Fetches actual balance from provider API and updates database
     */
    public function refreshSmsProviderBalance(Request $request, $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        try {
            $smsService = SmsService::findOrFail($id);
            $smsProviderService = app(\App\Services\SmsProviderService::class);
            
            // Fetch balance from provider API
            $balance = $smsProviderService->getBalance($smsService);
            
            // Update balance in database
            $smsService->update(['balance' => $balance]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'SMS provider balance refreshed successfully',
                'data' => [
                    'provider' => $smsService->provider,
                    'name' => $smsService->name,
                    'balance' => $balance
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to refresh balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test SMS provider connection
     */
    public function testSmsProvider(Request $request, $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        try {
            $smsService = SmsService::findOrFail($id);
            $smsProviderService = app(\App\Services\SmsProviderService::class);
            
            // Test by fetching balance
            $balance = $smsProviderService->getBalance($smsService);
            
            // Try to get countries to test API connection
            $countries = $smsProviderService->getCountries($smsService);
            
            return response()->json([
                'status' => 'success',
                'message' => 'SMS provider connection successful',
                'data' => [
                    'provider' => $smsService->provider,
                    'name' => $smsService->name,
                    'balance' => $balance,
                    'countries_available' => count($countries),
                    'connection' => 'working'
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'SMS provider test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh VTU provider balance
     * Fetches actual balance from VTU.ng API and updates database
     */
    public function refreshVtuProviderBalance(Request $request, $id): JsonResponse
    {
        $admin = Auth::user();
        if (!($admin instanceof \App\Models\User) || !$admin->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        try {
            $vtuService = VtuService::findOrFail($id);
            
            // Get VTU service provider
            $vtuProvider = null;
            if ($vtuService->provider === 'vtu_ng') {
                $vtuProvider = app(\App\Services\VtuNgService::class);
            } else {
                throw new \Exception("Unsupported VTU provider: {$vtuService->provider}");
            }
            
            // Fetch balance from provider API
            $balanceResponse = $vtuProvider->getBalance();
            
            if (!$balanceResponse['success']) {
                throw new \Exception($balanceResponse['message'] ?? 'Failed to fetch balance');
            }
            
            $balance = (float)($balanceResponse['balance'] ?? 0);
            
            // Update balance in database
            $vtuService->update(['balance' => $balance]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'VTU provider balance refreshed successfully',
                'data' => [
                    'provider' => $vtuService->provider,
                    'name' => $vtuService->name,
                    'balance' => $balance
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to refresh VTU balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Advertisement management page
     */
    public function advertisements()
    {
        // Serve the Blade view for advertisement management
        return view('admin.advertisements');
    }

    public function broadcasts()
    {
        // Serve the Blade view for broadcast notifications management
        return view('admin.broadcasts');
    }

    public function cryptoSales()
    {
        // Serve the Blade view for crypto sales management
        return view('admin.crypto-sales');
    }

    public function resellerPanels()
    {
        // Serve the Blade view for reseller panel management
        return view('admin.reseller-panels');
    }

    /**
     * Get all users with VTU access status
     */
    public function getVtuAccessUsers(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', 'all'); // all, enabled, disabled
        $perPage = $request->query('per_page', 50);

        $query = User::select('id', 'name', 'email', 'phone', 'balance', 'vtu_access_enabled', 'vtu_access_reason', 'vtu_access_disabled_at', 'vtu_access_disabled_by', 'created_at');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status === 'enabled') {
            $query->where('vtu_access_enabled', true);
        } elseif ($status === 'disabled') {
            $query->where('vtu_access_enabled', false);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Disable VTU access for a specific user
     */
    public function disableVtuAccess(Request $request, $userId): JsonResponse
    {
        $admin = Auth::user();

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->update([
            'vtu_access_enabled' => false,
            'vtu_access_reason' => $request->reason,
            'vtu_access_disabled_at' => now(),
            'vtu_access_disabled_by' => $admin->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'VTU access disabled successfully',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'vtu_access_enabled' => false,
                'reason' => $request->reason
            ]
        ]);
    }

    /**
     * Enable VTU access for a specific user
     */
    public function enableVtuAccess($userId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->update([
            'vtu_access_enabled' => true,
            'vtu_access_reason' => null,
            'vtu_access_disabled_at' => null,
            'vtu_access_disabled_by' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'VTU access enabled successfully',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'vtu_access_enabled' => true
            ]
        ]);
    }

    /**
     * Get VTU access statistics
     */
    public function getVtuAccessStats(): JsonResponse
    {
        $totalUsers = User::count();
        $enabledUsers = User::where('vtu_access_enabled', true)->count();
        $disabledUsers = User::where('vtu_access_enabled', false)->count();

        $recentlyDisabled = User::where('vtu_access_enabled', false)
            ->whereNotNull('vtu_access_disabled_at')
            ->orderBy('vtu_access_disabled_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'vtu_access_reason', 'vtu_access_disabled_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'enabled_users' => $enabledUsers,
                'disabled_users' => $disabledUsers,
                'recently_disabled' => $recentlyDisabled
            ]
        ]);
    }

    /**
     * Get provider balances
     */
    public function getProviderBalances(): JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User) || !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $balances = [
            'textverified' => 0.0,
            'sim5_rub' => 0.0,
            'sim5_usd' => 0.0,
            'tiger_sms' => 0.0,
            'dassy' => 0.0,
            'smspool' => 0.0,
            'simtoken' => 0.0,
            'sms_key' => 0.0,
            'sms_wkey' => 0.0,
            'vtu_ng' => 0.0,
            'other_providers' => []
        ];

        try {
            // TextVerified Balance
            $apiKey = config('services.textverified.api_key');
            $username = config('services.textverified.username');
            
            if ($apiKey && $username) {
                $scriptPath = sys_get_temp_dir() . '/textverified_balance_' . uniqid() . '.py';
                $scriptContent = "#!/usr/bin/env python3
from textverified import TextVerified
import json

client = TextVerified(
    api_key=\"{$apiKey}\",
    api_username=\"{$username}\",
)

try:
    account_info = client.account.me()
    result = {
        'success': True,
        'balance': account_info.current_balance
    }
    print(json.dumps(result))
except Exception as e:
    result = {
        'success': False,
        'error': str(e)
    }
    print(json.dumps(result))
";

                file_put_contents($scriptPath, $scriptContent);
                chmod($scriptPath, 0755);

                $output = shell_exec("python3 $scriptPath 2>&1");
                $result = json_decode($output, true);
                
                if ($result && $result['success']) {
                    $balances['textverified'] = (float) $result['balance'];
                }
                
                unlink($scriptPath);
            }

            // 5sim Balance
            $sim5ApiKey = config('services.sim5.api_key');
            if ($sim5ApiKey) {
                $client = new \GuzzleHttp\Client();
                $response = $client->get('https://5sim.net/v1/user/profile', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $sim5ApiKey,
                        'Accept' => 'application/json'
                    ]
                ]);
                
                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody(), true);
                    $balances['sim5_rub'] = (float) $data['balance'];
                    $balances['sim5_usd'] = $balances['sim5_rub'] * 0.011; // RUB to USD conversion
                }
            }

            // Tiger SMS Balance
            $tigerApiKey = config('services.sms.tiger_sms.api_key');
            if ($tigerApiKey) {
                try {
                    $client = new \GuzzleHttp\Client();
                    $response = $client->get('https://api.tiger-sms.com/stubs/handler_api.php', [
                        'query' => [
                            'api_key' => $tigerApiKey,
                            'action' => 'getBalance'
                        ]
                    ]);
                    
                    if ($response->getStatusCode() === 200) {
                        $body = trim($response->getBody());
                        // Tiger SMS returns balance as "ACCESS_BALANCE:12.96"
                        if (preg_match('/ACCESS_BALANCE:([0-9.]+)/', $body, $matches)) {
                            $balances['tiger_sms'] = (float) $matches[1];
                        } elseif (is_numeric($body)) {
                            $balances['tiger_sms'] = (float) $body;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Tiger SMS balance check failed: ' . $e->getMessage());
                }
            }

            // Dassy Balance
            $dassyApiKey = config('services.sms.dassy.api_key');
            if ($dassyApiKey) {
                try {
                    $client = new \GuzzleHttp\Client();
                    // Dassy uses Gateway360 API
                    $response = $client->post('https://api.gateway360.com/api/3.0/subaccount/get-balance', [
                        'json' => [
                            'api_key' => $dassyApiKey,
                            'user_name' => 'default' // or your subaccount username
                        ],
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json'
                        ]
                    ]);
                    
                    if ($response->getStatusCode() === 200) {
                        $data = json_decode($response->getBody(), true);
                        if (isset($data['result']['balance'])) {
                            $balances['dassy'] = (float) $data['result']['balance'];
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Dassy balance check failed: ' . $e->getMessage());
                }
            }

            // SMSPool Balance (if configured)
            $smspoolApiKey = env('SMSPOOL_API_KEY');
            if ($smspoolApiKey) {
                try {
                    $client = new \GuzzleHttp\Client();
                    $response = $client->post('https://api.smspool.net/request/balance', [
                        'json' => [
                            'key' => $smspoolApiKey
                        ],
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json'
                        ]
                    ]);
                    
                    if ($response->getStatusCode() === 200) {
                        $data = json_decode($response->getBody(), true);
                        $balances['smspool'] = (float) ($data['balance'] ?? 0);
                    }
                } catch (\Exception $e) {
                    \Log::warning('SMSPool balance check failed: ' . $e->getMessage());
                }
            }

            // SIMTOKEN Balance (if configured)
            $simtokenApiKey = env('SIMTOKEN_API_KEY');
            if ($simtokenApiKey) {
                try {
                    $client = new \GuzzleHttp\Client();
                    // Try common SMS provider endpoints
                    $endpoints = [
                        'https://api.simtoken.com/balance',
                        'https://simtoken.com/api/balance',
                        'https://api.simtoken.com/user/balance'
                    ];
                    
                    foreach ($endpoints as $endpoint) {
                        try {
                            $response = $client->get($endpoint, [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $simtokenApiKey,
                                    'Accept' => 'application/json'
                                ]
                            ]);
                            
                            if ($response->getStatusCode() === 200) {
                                $data = json_decode($response->getBody(), true);
                                if (isset($data['balance'])) {
                                    $balances['simtoken'] = (float) $data['balance'];
                                    break;
                                }
                            }
                        } catch (\Exception $e) {
                            continue; // Try next endpoint
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('SIMTOKEN balance check failed: ' . $e->getMessage());
                }
            }

            // SMS_KEY Balance (if configured)
            $smsKeyApiKey = env('SMS_KEY_API_KEY');
            if ($smsKeyApiKey) {
                try {
                    $client = new \GuzzleHttp\Client();
                    // Try common SMS provider endpoints
                    $endpoints = [
                        'https://api.smskey.com/balance',
                        'https://smskey.com/api/balance',
                        'https://api.smskey.com/user/balance'
                    ];
                    
                    foreach ($endpoints as $endpoint) {
                        try {
                            $response = $client->get($endpoint, [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $smsKeyApiKey,
                                    'Accept' => 'application/json'
                                ]
                            ]);
                            
                            if ($response->getStatusCode() === 200) {
                                $data = json_decode($response->getBody(), true);
                                if (isset($data['balance'])) {
                                    $balances['sms_key'] = (float) $data['balance'];
                                    break;
                                }
                            }
                        } catch (\Exception $e) {
                            continue; // Try next endpoint
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('SMS_KEY balance check failed: ' . $e->getMessage());
                }
            }

            // SMS_WKEY Balance (if configured)
            $smsWkeyApiKey = env('SMS_WKEY_API_KEY');
            if ($smsWkeyApiKey) {
                try {
                    $client = new \GuzzleHttp\Client();
                    // Try common SMS provider endpoints
                    $endpoints = [
                        'https://api.smswkey.com/balance',
                        'https://smswkey.com/api/balance',
                        'https://api.smswkey.com/user/balance'
                    ];
                    
                    foreach ($endpoints as $endpoint) {
                        try {
                            $response = $client->get($endpoint, [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $smsWkeyApiKey,
                                    'Accept' => 'application/json'
                                ]
                            ]);
                            
                            if ($response->getStatusCode() === 200) {
                                $data = json_decode($response->getBody(), true);
                                if (isset($data['balance'])) {
                                    $balances['sms_wkey'] = (float) $data['balance'];
                                    break;
                                }
                            }
                        } catch (\Exception $e) {
                            continue; // Try next endpoint
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('SMS_WKEY balance check failed: ' . $e->getMessage());
                }
            }

            // VTU.ng Balance
            $vtuUsername = config('services.vtu.vtu_ng.username');
            $vtuPassword = config('services.vtu.vtu_ng.password');
            
            
            if ($vtuUsername && $vtuPassword) {
                try {
                    $client = new \GuzzleHttp\Client();
                    
                    // Get JWT token
                    $jwtUrl = 'https://vtu.ng/wp-json/jwt-auth/v1/token';
                    $authResponse = $client->post($jwtUrl, [
                        'json' => [
                            'username' => $vtuUsername,
                            'password' => $vtuPassword
                        ],
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json'
                        ]
                    ]);
                    
                    if ($authResponse->getStatusCode() === 200) {
                        $authData = json_decode($authResponse->getBody(), true);
                        $token = $authData['token'] ?? null;
                        
                        if ($token) {
                            // Try to get balance
                            $balanceResponse = $client->get('https://vtu.ng/wp-json/api/v2/balance', [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token,
                                    'Accept' => 'application/json'
                                ]
                            ]);
                            
                            if ($balanceResponse->getStatusCode() === 200) {
                                $balanceData = json_decode($balanceResponse->getBody(), true);
                                $balances['vtu_ng'] = (float) ($balanceData['data']['balance'] ?? 0);
                            } else {
                                // Extract balance from error message (VTU.ng returns 403 with balance in message)
                                $errorBody = $balanceResponse->getBody();
                                
                                if (preg_match('/NGN([0-9,]+\.?[0-9]*)/', $errorBody, $matches)) {
                                    $balances['vtu_ng'] = (float) str_replace(',', '', $matches[1]);
                                } else {
                                    // If no balance found in error, log the error for debugging
                                    \Log::warning('VTU.ng balance extraction failed', [
                                        'status' => $balanceResponse->getStatusCode(),
                                        'body' => $errorBody
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    // Handle 403 errors specifically for VTU.ng
                    if ($e->getCode() === 403) {
                        $errorBody = $e->getResponse()->getBody();
                        
                        if (preg_match('/NGN([0-9,]+\.?[0-9]*)/', $errorBody, $matches)) {
                            $balances['vtu_ng'] = (float) str_replace(',', '', $matches[1]);
                        }
                    } else {
                        \Log::error('VTU.ng API error', [
                            'code' => $e->getCode(),
                            'message' => $e->getMessage()
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('VTU.ng general error', [
                        'message' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('Error getting provider balances: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'data' => $balances
        ]);
    }
}

