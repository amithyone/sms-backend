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
            'status' => 'required|in:pending,approved,rejected',
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
            $deposit->update([
                'status' => $request->status,
                'admin_note' => $request->admin_note,
                'processed_at' => now(),
                'processed_by' => $user->id,
            ]);

            // If approved, credit user's balance
            if ($request->status === 'approved') {
                $deposit->user->updateBalance($deposit->amount, 'add');
                
                // Create transaction record
                Transaction::create([
                    'user_id' => $deposit->user_id,
                    'type' => 'credit',
                    'amount' => $deposit->amount,
                    'description' => 'Deposit approved - ' . $deposit->reference,
                    'status' => 'completed',
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

        $markup = $this->getSettingValue('pricing.markup_percent', 'number', 10.0);
        $currency = $this->getSettingValue('pricing.currency', 'string', 'NGN');
        $autoFx = $this->getSettingValue('pricing.auto_fx', 'boolean', true);

        return response()->json([
            'status' => 'success',
            'data' => [
                'markup_percent' => (float)$markup,
                'currency' => (string)$currency,
                'auto_fx' => (bool)$autoFx,
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
            'markup_percent' => 'required|numeric|min:0|max:100',
            'currency' => 'sometimes|string|in:NGN,USD,EUR,GBP',
            'auto_fx' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $this->putSettingValue('pricing.markup_percent', (string)$data['markup_percent'], 'number', 'pricing');
        if (isset($data['currency'])) {
            $this->putSettingValue('pricing.currency', (string)$data['currency'], 'string', 'pricing');
        }
        if (array_key_exists('auto_fx', $data)) {
            $this->putSettingValue('pricing.auto_fx', $data['auto_fx'] ? '1' : '0', 'boolean', 'pricing');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pricing settings updated successfully',
            'data' => [
                'markup_percent' => (float)$this->getSettingValue('pricing.markup_percent', 'number', 10.0),
                'currency' => (string)$this->getSettingValue('pricing.currency', 'string', 'NGN'),
                'auto_fx' => (bool)$this->getSettingValue('pricing.auto_fx', 'boolean', true),
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
}
