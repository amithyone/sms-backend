<?php

namespace App\Http\Controllers;

use App\Models\CryptoSale;
use App\Models\CryptoExchangeRate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CryptoSaleController extends Controller
{
    /**
     * Get exchange rates and settings
     */
    public function getRates(): JsonResponse
    {
        try {
            $rates = CryptoExchangeRate::enabled()->get()->map(function ($rate) {
                return [
                    'payment_method' => $rate->payment_method,
                    'rate_per_usd' => $rate->rate_per_usd,
                    'min_amount' => $rate->min_amount,
                    'max_amount' => $rate->max_amount,
                    'instructions' => $rate->instructions,
                    'disclaimer' => $rate->disclaimer,
                    'admin_wallet_address' => $rate->admin_wallet_address,
                    'admin_paypal_email' => $rate->admin_paypal_email
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $rates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch exchange rates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new crypto sale request
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:usdt,paypal,bitcoin,ethereum',
            'crypto_amount' => 'required|numeric|min:0.01',
            'user_wallet_address' => 'required_if:payment_method,usdt,bitcoin,ethereum|nullable|string',
            'user_paypal_email' => 'required_if:payment_method,paypal|nullable|email',
            'recipient_account_number' => 'required|string',
            'recipient_account_name' => 'required|string',
            'recipient_bank_name' => 'required|string',
            'recipient_phone' => 'required|string',
            'proof_images' => 'required|array|min:1',
            'proof_images.*' => 'required|string' // Base64 encoded images
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Verify user has phone number
            if (!$user->phone) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please add your phone number to your profile before selling crypto'
                ], 400);
            }

            // Get exchange rate
            $exchangeRate = CryptoExchangeRate::where('payment_method', $request->payment_method)
                ->where('is_enabled', true)
                ->first();

            if (!$exchangeRate) {
                return response()->json([
                    'status' => 'error',
                    'message' => ucfirst($request->payment_method) . ' sales are currently disabled'
                ], 400);
            }

            // Validate amount limits
            if ($request->crypto_amount < $exchangeRate->min_amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Minimum amount is $" . $exchangeRate->min_amount
                ], 400);
            }

            if ($request->crypto_amount > $exchangeRate->max_amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Maximum amount is $" . $exchangeRate->max_amount
                ], 400);
            }

            DB::beginTransaction();

            // Calculate Naira amount
            $nairaAmount = $request->crypto_amount * $exchangeRate->rate_per_usd;

            // Handle proof images upload
            $proofPaths = [];
            foreach ($request->proof_images as $index => $imageData) {
                // Decode base64 image
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $imageData = base64_decode($imageData);
                    
                    if ($imageData === false) {
                        continue;
                    }

                    $extension = strtolower($type[1]);
                    $fileName = 'crypto_proof_' . time() . '_' . $index . '.' . $extension;
                    $path = 'crypto_proofs/' . $fileName;
                    
                    Storage::disk('public')->put($path, $imageData);
                    $proofPaths[] = Storage::url($path);
                }
            }

            if (empty($proofPaths)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload proof of payment images'
                ], 400);
            }

            // Generate unique transaction ID
            $transactionId = 'CRYPTO-' . strtoupper(Str::random(10));

            // Create crypto sale request
            $cryptoSale = CryptoSale::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'payment_method' => $request->payment_method,
                'crypto_amount' => $request->crypto_amount,
                'exchange_rate' => $exchangeRate->rate_per_usd,
                'naira_amount' => $nairaAmount,
                'user_wallet_address' => $request->user_wallet_address,
                'user_paypal_email' => $request->user_paypal_email,
                'recipient_account_number' => $request->recipient_account_number,
                'recipient_account_name' => $request->recipient_account_name,
                'recipient_bank_name' => $request->recipient_bank_name,
                'recipient_phone' => $request->recipient_phone,
                'proof_of_payment' => $proofPaths,
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Crypto sale request submitted successfully. We will process your request within 24 hours.',
                'data' => $cryptoSale
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create crypto sale request', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's crypto sale history
     */
    public function getUserSales(): JsonResponse
    {
        try {
            $user = Auth::user();
            $sales = CryptoSale::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $sales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch sales history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all crypto sales (Admin only)
     */
    public function index(): JsonResponse
    {
        try {
            $sales = CryptoSale::with(['user:id,name,email,phone', 'processor:id,name'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $sales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch crypto sales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update crypto sale status (Admin only)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,completed,rejected,cancelled',
            'admin_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $admin = Auth::user();
            $sale = CryptoSale::findOrFail($id);

            DB::beginTransaction();

            $sale->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
                'processed_by' => $admin->id,
                'processed_at' => now()
            ]);

            // If completed, credit user's balance
            if ($request->status === 'completed' && $sale->status !== 'completed') {
                $user = $sale->user;
                $user->updateBalance($sale->naira_amount, 'add');

                // Create transaction record
                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'crypto_sale',
                    'amount' => $sale->naira_amount,
                    'status' => 'completed',
                    'reference' => $sale->transaction_id,
                    'description' => "Crypto sale: {$sale->crypto_amount} " . strtoupper($sale->payment_method) . " at ₦{$sale->exchange_rate}/USD",
                    'metadata' => [
                        'crypto_sale_id' => $sale->id,
                        'payment_method' => $sale->payment_method,
                        'crypto_amount' => $sale->crypto_amount,
                        'exchange_rate' => $sale->exchange_rate
                    ]
                ]);

                // Send inbox notification
                \App\Models\InboxMessage::create([
                    'user_id' => $user->id,
                    'type' => 'crypto_sale',
                    'title' => "Fadded VIP 🔆  Crypto Sale Completed",
                    'message' => "Your crypto sale of {$sale->crypto_amount} " . strtoupper($sale->payment_method) . " has been completed. ₦{$sale->naira_amount} has been credited to your wallet.",
                    'reference' => $sale->transaction_id,
                    'metadata' => [
                        'crypto_sale_id' => $sale->id,
                        'crypto_amount' => $sale->crypto_amount,
                        'naira_amount' => $sale->naira_amount,
                        'payment_method' => $sale->payment_method,
                        'exchange_rate' => $sale->exchange_rate
                    ],
                    'is_read' => false
                ]);
            }

            // If rejected, send notification
            if ($request->status === 'rejected') {
                \App\Models\InboxMessage::create([
                    'user_id' => $sale->user_id,
                    'type' => 'crypto_sale',
                    'title' => "Fadded VIP 🔆  Crypto Sale Rejected",
                    'message' => "Your crypto sale request has been rejected. Reason: " . ($request->admin_notes ?? 'No reason provided'),
                    'reference' => $sale->transaction_id,
                    'metadata' => [
                        'crypto_sale_id' => $sale->id,
                        'admin_notes' => $request->admin_notes
                    ],
                    'is_read' => false
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Crypto sale status updated successfully',
                'data' => $sale->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get exchange rate settings (Admin only)
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = CryptoExchangeRate::all();
            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update exchange rate settings (Admin only)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:usdt,paypal,bitcoin,ethereum',
            'rate_per_usd' => 'required|numeric|min:0',
            'is_enabled' => 'required|boolean',
            'instructions' => 'nullable|string',
            'disclaimer' => 'nullable|string',
            'admin_wallet_address' => 'nullable|string',
            'admin_paypal_email' => 'nullable|email',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $admin = Auth::user();

            $setting = CryptoExchangeRate::updateOrCreate(
                ['payment_method' => $request->payment_method],
                [
                    'rate_per_usd' => $request->rate_per_usd,
                    'is_enabled' => $request->is_enabled,
                    'instructions' => $request->instructions,
                    'disclaimer' => $request->disclaimer,
                    'admin_wallet_address' => $request->admin_wallet_address,
                    'admin_paypal_email' => $request->admin_paypal_email,
                    'min_amount' => $request->min_amount,
                    'max_amount' => $request->max_amount,
                    'updated_by' => $admin->id
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully',
                'data' => $setting
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics (Admin only)
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_requests' => CryptoSale::count(),
                'pending' => CryptoSale::where('status', 'pending')->count(),
                'processing' => CryptoSale::where('status', 'processing')->count(),
                'completed' => CryptoSale::where('status', 'completed')->count(),
                'rejected' => CryptoSale::where('status', 'rejected')->count(),
                'total_crypto_received' => CryptoSale::where('status', 'completed')->sum('crypto_amount'),
                'total_naira_paid' => CryptoSale::where('status', 'completed')->sum('naira_amount')
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
