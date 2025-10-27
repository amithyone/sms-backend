<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        try {
            // Determine frontend origin to return to after OAuth
            $origin = $request->header('Origin') ?: $request->header('Referer', '');
            $frontend = 'fadsms';
            if (strpos($origin, 'faddedsms.com') !== false) {
                $frontend = 'faddedsms';
            } elseif (strpos($origin, 'fadsms.com') !== false) {
                $frontend = 'fadsms';
            }
            $mode = $request->query('mode', 'redirect');
            $statePayload = base64_encode(json_encode([
                'frontend' => $frontend,
                'ts' => time(),
                'mode' => $mode,
            ]));

            return Socialite::driver('google')
                ->stateless()
                ->with(['state' => $statePayload])
                ->redirect();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate Google OAuth URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request): JsonResponse
    {
        try {
            // Get user from Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find or create user - improved query logic
            $user = User::where('google_id', $googleUser->getId())->first();
            
            // If not found by google_id, try by email
            if (!$user) {
                $user = User::where('email', $googleUser->getEmail())->first();
            }

            if ($user) {
                // Update existing user with Google info if not set
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'auth_provider' => 'google',
                    ]);
                }
            } else {
                // Generate unique referral code
                do {
                    $referralCode = Str::random(8);
                } while (User::where('referral_code', $referralCode)->exists());

                // Create new user with all required data
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'auth_provider' => 'google',
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)), // Random password
                    'referral_code' => $referralCode,
                    'referred_by' => null, // Can be set later if they use a referral link
                    'balance' => 0.00,
                    'status' => 'active',
                    'role' => 'user',
                    'vtu_access_enabled' => true, // Enable VTU access by default
                    'phone' => null, // Can be added later
                    'username' => null, // Can be added later
                ]);
            }

            // Generate Sanctum token
            $token = $user->createToken('google-auth-token')->plainTextToken;

            // Return user data and token
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'balance' => $user->balance,
                        'role' => $user->role,
                        'status' => $user->status,
                        'referral_code' => $user->referral_code,
                    ],
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Google authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Frontend-friendly callback that redirects with token
     */
    public function handleGoogleCallbackRedirect(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find or create user - improved query logic
            $user = User::where('google_id', $googleUser->getId())->first();
            
            // If not found by google_id, try by email
            if (!$user) {
                $user = User::where('email', $googleUser->getEmail())->first();
            }

            if ($user) {
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'auth_provider' => 'google',
                    ]);
                }
            } else {
                // Generate unique referral code
                do {
                    $referralCode = Str::random(8);
                } while (User::where('referral_code', $referralCode)->exists());

                // Create new user with all required data
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'auth_provider' => 'google',
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)),
                    'referral_code' => $referralCode,
                    'referred_by' => null, // Can be set later if they use a referral link
                    'balance' => 0.00,
                    'status' => 'active',
                    'role' => 'user',
                    'vtu_access_enabled' => true, // Enable VTU access by default
                    'phone' => null, // Can be added later
                    'username' => null, // Can be added later
                ]);
            }

            $token = $user->createToken('google-auth-token')->plainTextToken;

            // Determine frontend domain from OAuth state or fallbacks
            $state = $request->input('state');
            $frontendUrl = env('FRONTEND_URL', 'https://fadsms.com'); // default
            $mode = 'redirect';
            if ($state) {
                try {
                    $decoded = json_decode(base64_decode($state), true);
                    $mode = $decoded['mode'] ?? 'redirect';
                    if (($decoded['frontend'] ?? '') === 'faddedsms') {
                        $frontendUrl = env('FRONTEND_URL_ALT', 'https://faddedsms.com');
                    } elseif (($decoded['frontend'] ?? '') === 'fadsms') {
                        $frontendUrl = env('FRONTEND_URL', 'https://fadsms.com');
                    }
                } catch (\Throwable $t) {
                    // ignore state parse errors, keep default
                }
            } else {
                // Fallback to referer header for rare cases
                $referer = request()->header('referer', '');
                if (strpos($referer, 'faddedsms.com') !== false) {
                    $frontendUrl = env('FRONTEND_URL_ALT', 'https://faddedsms.com');
                }
            }
            
            $encodedToken = urlencode($token);
            $userPayload = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'balance' => $user->balance,
                'role' => $user->role,
                'referral_code' => $user->referral_code,
            ];
            $encodedUser = urlencode(json_encode($userPayload));

            if ($mode === 'popup') {
                // Render a lightweight page that posts the token to the opener window
                $origin = parse_url($frontendUrl, PHP_URL_SCHEME) . '://' . parse_url($frontendUrl, PHP_URL_HOST);
                $jsonForJs = json_encode(['token' => $token, 'user' => $userPayload]);
                $html = "<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Signing you in...</title></head><body><script>(function(){\n  try {\n    var data = " . json_encode($jsonForJs) . ";\n    data = JSON.parse(data);\n    if (window.opener) {\n      window.opener.postMessage({ type: 'oauth_result', token: data.token, user: data.user }, '" . addslashes($origin) . "');\n    }\n  } catch (e) {}\n  setTimeout(function(){ window.close(); }, 200);\n})();</script><p>Signing you in... You can close this window.</p></body></html>";
                return response($html, 200)->header('Content-Type', 'text/html');
            } else {
                // Provide both query and hash to maximize compatibility with frontend routing/CDN rewrites
                $redirectUrl = $frontendUrl . '/auth/callback?token=' . $encodedToken . '&user=' . $encodedUser
                    . '#token=' . $encodedToken . '&user=' . $encodedUser;
                return redirect()->away($redirectUrl);
            }

        } catch (\Exception $e) {
            // Redirect to frontend with error - detect which domain to redirect to using state
            $state = $request->input('state');
            $frontendUrl = env('FRONTEND_URL', 'https://fadsms.com'); // default
            if ($state) {
                try {
                    $decoded = json_decode(base64_decode($state), true);
                    if (($decoded['frontend'] ?? '') === 'faddedsms') {
                        $frontendUrl = env('FRONTEND_URL_ALT', 'https://faddedsms.com');
                    }
                } catch (\Throwable $t) {}
            } else {
                $referer = request()->header('referer', '');
                if (strpos($referer, 'faddedsms.com') !== false) {
                    $frontendUrl = env('FRONTEND_URL_ALT', 'https://faddedsms.com');
                }
            }
            
            return redirect()->away(
                $frontendUrl . '/auth/callback?error=' . urlencode($e->getMessage())
            );
        }
    }
}

