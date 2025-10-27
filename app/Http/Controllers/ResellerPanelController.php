<?php

namespace App\Http\Controllers;

use App\Models\ResellerPanel;
use App\Models\ResellerPayment;
use App\Models\User;
use App\Services\SubdomainService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResellerPanelController extends Controller
{
    /**
     * Apply for reseller panel
     */
    public function apply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'panel_name' => 'required|string|max:255',
            'subdomain' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9-]+$/',
                'unique:reseller_panels,subdomain',
                'not_in:www,api,admin,app,mail,ftp,webmail,localhost'
            ],
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                'unique:reseller_panels,custom_domain'
            ],
            'subscription_type' => 'required|in:monthly,annual',
            'brand_name' => 'required|string|max:100',
            'business_description' => 'nullable|string|max:1000'
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
            
            // Check if user already has a panel
            $existing = ResellerPanel::where('user_id', $user->id)->first();
            if ($existing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already have a reseller panel application'
                ], 400);
            }

            // Calculate subscription fee
            $subscriptionFee = $request->subscription_type === 'annual' ? 300000 : 30000;

            // Check if user has enough balance
            if ($user->balance < $subscriptionFee) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Insufficient balance. You need ₦{$subscriptionFee} to apply. Current balance: ₦{$user->balance}"
                ], 400);
            }

            DB::beginTransaction();

            // Create reseller panel
            $panel = ResellerPanel::create([
                'user_id' => $user->id,
                'panel_name' => $request->panel_name,
                'subdomain' => strtolower($request->subdomain),
                'custom_domain' => $request->custom_domain,
                'status' => 'pending',
                'subscription_type' => $request->subscription_type,
                'subscription_fee' => $subscriptionFee,
                'brand_name' => $request->brand_name,
                'is_paid' => false
            ]);

            // Deduct application fee from user balance
            $user->updateBalance($subscriptionFee, 'subtract');

            // Create payment record
            ResellerPayment::create([
                'reseller_panel_id' => $panel->id,
                'user_id' => $user->id,
                'payment_reference' => 'RESELLER-' . strtoupper(Str::random(10)),
                'amount' => $subscriptionFee,
                'payment_type' => 'setup',
                'payment_method' => 'wallet',
                'payment_status' => 'completed',
                'paid_at' => now(),
                'period_start' => now(),
                'period_end' => $request->subscription_type === 'annual' 
                    ? now()->addYear() 
                    : now()->addMonth()
            ]);

            // Create inbox notification
            \App\Models\InboxMessage::create([
                'user_id' => $user->id,
                'type' => 'reseller_application',
                'title' => 'Fadded VIP 🔆  Reseller Panel Application Received',
                'message' => "Your reseller panel application for '{$request->panel_name}' has been received and is pending approval. We will review it within 24 hours.",
                'reference' => 'RESELLER-' . $panel->id,
                'metadata' => [
                    'panel_id' => $panel->id,
                    'subdomain' => $panel->subdomain,
                    'subscription_type' => $request->subscription_type
                ],
                'is_read' => false
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Application submitted successfully! We will review and activate your panel within 24 hours.',
                'data' => $panel
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create reseller panel application', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's reseller panel info
     */
    public function getMyPanel(): JsonResponse
    {
        try {
            $user = Auth::user();
            $panel = ResellerPanel::where('user_id', $user->id)->first();

            return response()->json([
                'status' => 'success',
                'data' => $panel
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch panel info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update reseller panel settings (reseller owner)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'brand_name' => 'nullable|string|max:100',
            'logo_url' => 'nullable|string',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'footer_text' => 'nullable|string|max:500',
            'sms_margin_percentage' => 'nullable|numeric|min:0|max:100',
            'vtu_margin_percentage' => 'nullable|numeric|min:0|max:100',
            'airtime_margin_percentage' => 'nullable|numeric|min:0|max:100',
            'data_margin_percentage' => 'nullable|numeric|min:0|max:100',
            'electricity_margin_percentage' => 'nullable|numeric|min:0|max:100'
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
            $panel = ResellerPanel::where('user_id', $user->id)->firstOrFail();

            $panel->update($request->only([
                'brand_name',
                'logo_url',
                'primary_color',
                'secondary_color',
                'footer_text',
                'sms_margin_percentage',
                'vtu_margin_percentage',
                'airtime_margin_percentage',
                'data_margin_percentage',
                'electricity_margin_percentage'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully',
                'data' => $panel
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configure payment gateway (reseller owner)
     */
    public function configurePaymentGateway(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_gateway' => 'required|in:paystack,flutterwave,payvibe',
            'paystack_public_key' => 'required_if:payment_gateway,paystack',
            'paystack_secret_key' => 'required_if:payment_gateway,paystack',
            'paystack_webhook_secret' => 'nullable|string',
            'flutterwave_public_key' => 'required_if:payment_gateway,flutterwave',
            'flutterwave_secret_key' => 'required_if:payment_gateway,flutterwave',
            'flutterwave_encryption_key' => 'required_if:payment_gateway,flutterwave',
            'payvibe_api_key' => 'required_if:payment_gateway,payvibe',
            'payvibe_contract_code' => 'required_if:payment_gateway,payvibe'
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
            $panel = ResellerPanel::where('user_id', $user->id)->firstOrFail();

            $updateData = [
                'payment_gateway' => $request->payment_gateway
            ];

            if ($request->payment_gateway === 'paystack') {
                $updateData['paystack_public_key'] = $request->paystack_public_key;
                $updateData['paystack_secret_key'] = $request->paystack_secret_key;
                $updateData['paystack_webhook_secret'] = $request->paystack_webhook_secret;
                $updateData['payment_gateway_enabled'] = true;
            } elseif ($request->payment_gateway === 'flutterwave') {
                $updateData['flutterwave_public_key'] = $request->flutterwave_public_key;
                $updateData['flutterwave_secret_key'] = $request->flutterwave_secret_key;
                $updateData['flutterwave_encryption_key'] = $request->flutterwave_encryption_key;
                $updateData['payment_gateway_enabled'] = true;
            } elseif ($request->payment_gateway === 'payvibe') {
                $updateData['payvibe_api_key'] = $request->payvibe_api_key;
                $updateData['payvibe_contract_code'] = $request->payvibe_contract_code;
                $updateData['payment_gateway_enabled'] = true;
            }

            $panel->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment gateway configured successfully',
                'data' => $panel
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to configure payment gateway: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all reseller panels (Admin only)
     */
    public function index(): JsonResponse
    {
        try {
            $panels = ResellerPanel::with('owner:id,name,email,phone')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $panels
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch reseller panels',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve reseller panel (Admin only)
     */
    public function approve($id): JsonResponse
    {
        try {
            $panel = ResellerPanel::findOrFail($id);
            $subdomainService = new SubdomainService();

            DB::beginTransaction();

            // Create subdomain configuration
            $subdomainResult = $subdomainService->createSubdomain($panel);
            
            if (!$subdomainResult['success']) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create subdomain: ' . $subdomainResult['message']
                ], 500);
            }

            // Update panel status
            $panel->update([
                'status' => 'active',
                'is_paid' => true,
                'last_payment_at' => now(),
                'subscription_expires_at' => $panel->subscription_type === 'annual' 
                    ? now()->addYear() 
                    : now()->addMonth()
            ]);

            // Configure custom domain if provided
            $customDomainInstructions = null;
            if ($panel->custom_domain) {
                $customResult = $subdomainService->configureCustomDomain($panel);
                if ($customResult['success']) {
                    $customDomainInstructions = $customResult['dns_instructions'];
                }
            }

            // Notify user
            $message = "Congratulations! Your reseller panel '{$panel->panel_name}' has been activated.\n\n";
            $message .= "🔗 Subdomain: https://{$panel->subdomain}.fadsms.com\n\n";
            
            if ($customDomainInstructions) {
                $message .= "📋 Custom Domain Setup:\n";
                $message .= "To activate your custom domain ({$panel->custom_domain}), add these DNS records:\n\n";
                foreach ($customDomainInstructions['instructions'] as $instruction) {
                    $message .= "• {$instruction['type']}: {$instruction['name']} → {$instruction['value']}\n";
                }
                $message .= "\nDNS changes may take up to 48 hours to propagate.";
            }

            \App\Models\InboxMessage::create([
                'user_id' => $panel->user_id,
                'type' => 'reseller_approved',
                'title' => 'Fadded VIP 🔆  Reseller Panel Activated!',
                'message' => $message,
                'reference' => 'RESELLER-APPROVED-' . $panel->id,
                'metadata' => [
                    'panel_id' => $panel->id,
                    'subdomain' => $panel->subdomain,
                    'panel_url' => "https://{$panel->subdomain}.fadsms.com",
                    'custom_domain_instructions' => $customDomainInstructions
                ],
                'is_read' => false
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reseller panel approved and activated',
                'data' => [
                    'panel' => $panel,
                    'subdomain_url' => $subdomainResult['url'],
                    'custom_domain_instructions' => $customDomainInstructions
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve panel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject reseller panel (Admin only)
     */
    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $panel = ResellerPanel::findOrFail($id);
            $reason = $request->input('reason', 'Application did not meet requirements');

            DB::beginTransaction();

            $panel->update(['status' => 'cancelled']);

            // Refund user
            $user = $panel->owner;
            $user->updateBalance($panel->subscription_fee, 'add');

            // Notify user
            \App\Models\InboxMessage::create([
                'user_id' => $panel->user_id,
                'type' => 'reseller_rejected',
                'title' => 'Fadded VIP 🔆  Reseller Panel Application Declined',
                'message' => "Your reseller panel application has been declined. Reason: {$reason}. Your payment of ₦{$panel->subscription_fee} has been refunded.",
                'reference' => 'RESELLER-REJECTED-' . $panel->id,
                'metadata' => [
                    'panel_id' => $panel->id,
                    'reason' => $reason,
                    'refund_amount' => $panel->subscription_fee
                ],
                'is_read' => false
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Application rejected and refunded'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reseller statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_panels' => ResellerPanel::count(),
                'active_panels' => ResellerPanel::where('status', 'active')->count(),
                'pending_panels' => ResellerPanel::where('status', 'pending')->count(),
                'total_revenue' => ResellerPanel::where('status', 'active')->sum('subscription_fee'),
                'total_reseller_users' => User::whereNotNull('reseller_id')->count()
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

    /**
     * Add/Update custom domain (User)
     */
    public function addCustomDomain(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'custom_domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i'
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid domain format',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $panel = ResellerPanel::where('user_id', $user->id)->firstOrFail();

            // Check if domain is already taken
            $existing = ResellerPanel::where('custom_domain', $request->custom_domain)
                ->where('id', '!=', $panel->id)
                ->exists();

            if ($existing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This domain is already in use by another panel'
                ], 400);
            }

            $panel->update([
                'custom_domain' => strtolower($request->custom_domain)
            ]);

            // Configure custom domain
            $subdomainService = new SubdomainService();
            $result = $subdomainService->configureCustomDomain($panel);

            return response()->json([
                'status' => 'success',
                'message' => 'Custom domain added successfully. Please configure DNS records.',
                'data' => [
                    'panel' => $panel,
                    'dns_instructions' => $result['dns_instructions'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add custom domain: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get DNS instructions for custom domain (User)
     */
    public function getDNSInstructions(): JsonResponse
    {
        try {
            $user = Auth::user();
            $panel = ResellerPanel::where('user_id', $user->id)->firstOrFail();
            
            if (!$panel->custom_domain) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No custom domain configured'
                ], 400);
            }

            $subdomainService = new SubdomainService();
            $instructions = $subdomainService->getDNSInstructions($panel);

            return response()->json([
                'status' => 'success',
                'data' => $instructions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch DNS instructions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment gateway config for panel (Public endpoint for deposits)
     */
    public function getPaymentConfig(Request $request): JsonResponse
    {
        try {
            $panelId = $request->input('panel_id');
            
            if (!$panelId) {
                // Return main site payment config
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'is_reseller' => false,
                        'gateway' => env('PAYMENT_GATEWAY', 'paystack'),
                        'paystack_public_key' => env('PAYSTACK_PUBLIC_KEY'),
                        'flutterwave_public_key' => env('FLUTTERWAVE_PUBLIC_KEY')
                    ]
                ]);
            }

            $panel = ResellerPanel::where('id', $panelId)
                ->where('status', 'active')
                ->where('payment_gateway_enabled', true)
                ->first();

            if (!$panel || !$panel->payment_gateway) {
                // Fall back to main site payment
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'is_reseller' => false,
                        'gateway' => env('PAYMENT_GATEWAY', 'paystack'),
                        'paystack_public_key' => env('PAYSTACK_PUBLIC_KEY'),
                        'flutterwave_public_key' => env('FLUTTERWAVE_PUBLIC_KEY')
                    ]
                ]);
            }

            // Return reseller's payment config
            $config = [
                'is_reseller' => true,
                'panel_id' => $panel->id,
                'gateway' => $panel->payment_gateway
            ];

            if ($panel->payment_gateway === 'paystack') {
                $config['paystack_public_key'] = $panel->paystack_public_key;
            } elseif ($panel->payment_gateway === 'flutterwave') {
                $config['flutterwave_public_key'] = $panel->flutterwave_public_key;
            }

            return response()->json([
                'status' => 'success',
                'data' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch payment config',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reseller panel branding by domain (Public endpoint)
     */
    public function getBrandingByDomain(Request $request): JsonResponse
    {
        try {
            $host = $request->header('Host') ?? $request->input('domain');
            
            if (!$host) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No domain provided'
                ], 400);
            }

            // Check if it's a reseller subdomain
            if (str_ends_with($host, '.fadsms.com') && $host !== 'fadsms.com' && $host !== 'www.fadsms.com' && $host !== 'api.fadsms.com') {
                $subdomain = str_replace('.fadsms.com', '', $host);
                $panel = ResellerPanel::where('subdomain', $subdomain)
                    ->where('status', 'active')
                    ->first();
            } else {
                // Check if it's a custom domain
                $panel = ResellerPanel::where('custom_domain', $host)
                    ->where('status', 'active')
                    ->first();
            }

            if (!$panel) {
                // Return default branding for main site
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'is_reseller' => false,
                        'brand_name' => 'FaddedSMS',
                        'primary_color' => '#0f172a',
                        'secondary_color' => '#1c64f2',
                        'logo_url' => null,
                        'favicon_url' => null,
                        'footer_text' => '© 2025 FaddedSMS. All rights reserved.'
                    ]
                ]);
            }

            // Return reseller branding
            return response()->json([
                'status' => 'success',
                'data' => [
                    'is_reseller' => true,
                    'panel_id' => $panel->id,
                    'brand_name' => $panel->brand_name,
                    'primary_color' => $panel->primary_color,
                    'secondary_color' => $panel->secondary_color,
                    'logo_url' => $panel->logo_url,
                    'favicon_url' => $panel->favicon_url,
                    'footer_text' => $panel->footer_text ?? "© 2025 {$panel->brand_name}. All rights reserved.",
                    'subdomain' => $panel->subdomain,
                    'custom_domain' => $panel->custom_domain
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch branding',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
