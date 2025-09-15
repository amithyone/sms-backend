<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SmsOrder;

class WebhookController extends Controller
{
    /**
     * TextVerified webhook receiver with HMAC-SHA512 verification.
     * Header: X-Webhook-Signature: HMAC-SHA512=BASE64(hmac_sha512(secret, body))
     */
    public function textVerified(Request $request)
    {
        $signature = $request->header('X-Webhook-Signature');
        if (!$signature || stripos($signature, 'HMAC-SHA512=') !== 0) {
            return response()->json(['success' => false, 'message' => 'Missing or invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        $encoded = substr($signature, strlen('HMAC-SHA512='));
        $secret = env('TEXTVERIFIED_WEBHOOK_SECRET', '');
        if (empty($secret)) {
            Log::warning('TextVerified webhook secret not configured');
            return response()->json(['success' => false, 'message' => 'Webhook not configured'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $rawBody = $request->getContent();
        $computed = base64_encode(hash_hmac('sha512', $rawBody, $secret, true));
        if (!hash_equals($encoded, $computed)) {
            Log::warning('TextVerified webhook signature mismatch', [ 'provided' => $encoded, 'computed' => $computed ]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $request->json()->all();
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        Log::info('TextVerified webhook received', [ 'event' => $event ]);

        // Handle SMS received event: v2.sms.received
        if ($event === 'v2.sms.received') {
            $reservationId = (string)($data['reservationId'] ?? '');
            $code = $data['parsedCode'] ?? null;
            $to = $data['to'] ?? null;
            // Try to find an active SmsOrder with provider textverified and matching provider_order_id or phone
            $order = SmsOrder::where('status', 'active')
                ->whereHas('smsService', function ($q) { $q->where('provider', 'textverified'); })
                ->where(function ($q) use ($reservationId, $to) {
                    if (!empty($reservationId)) { $q->orWhere('provider_order_id', $reservationId); }
                    if (!empty($to)) { $q->orWhere('phone_number', $to); }
                })
                ->orderByDesc('created_at')
                ->first();

            if ($order && $code) {
                $order->markAsCompleted($code);
                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => true]);
    }
}


