<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function getRecentDeposits(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rows = DB::table('deposits')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $items = $rows->map(function ($d) {
            return [
                'id' => $d->id,
                'amount' => (float) $d->amount,
                'reference' => (string) $d->reference,
                'status' => (string) $d->status,
                'created_at' => (string) $d->created_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'message' => 'Recent deposits retrieved successfully'
        ]);
    }
}
