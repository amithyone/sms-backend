<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\SmsService;
use App\Services\SmsProviderService;

class DebugSmsController extends Controller
{
    public function providers(Request $request, SmsProviderService $providerService): JsonResponse
    {
        $country = $request->get('country');
        $service = $request->get('service');

        $rows = [];
        $providers = SmsService::active()->orderedByPriority()->get();
        foreach ($providers as $p) {
            $rows[] = $providerService->debugProbe($p, $country, $service);
        }

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }
}


