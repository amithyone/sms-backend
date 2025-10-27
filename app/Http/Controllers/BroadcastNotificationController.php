<?php

namespace App\Http\Controllers;

use App\Models\BroadcastNotification;
use App\Models\User;
use App\Models\InboxMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastNotificationController extends Controller
{
    /**
     * Get all broadcast notifications (admin only)
     */
    public function index(): JsonResponse
    {
        try {
            $broadcasts = BroadcastNotification::with('admin:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $broadcasts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch broadcasts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create and send broadcast notification
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'nullable|in:info,warning,success,error,update,promo',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'action_text' => 'nullable|string|max:100',
            'action_url' => 'nullable|string|max:500',
            'target_audience' => 'nullable|in:all,active,inactive',
            'send_now' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $admin = Auth::user();
            
            // Create broadcast record
            $broadcast = BroadcastNotification::create([
                'admin_id' => $admin->id,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type ?? 'info',
                'priority' => $request->priority ?? 'normal',
                'action_text' => $request->action_text,
                'action_url' => $request->action_url,
                'target_audience' => $request->target_audience ?? 'all',
                'total_recipients' => 0,
                'delivered_count' => 0,
                'is_sent' => false
            ]);

            // Send immediately if requested
            if ($request->send_now) {
                $this->sendBroadcast($broadcast);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $request->send_now 
                    ? 'Broadcast sent successfully' 
                    : 'Broadcast created successfully',
                'data' => $broadcast->fresh()->load('admin:id,name')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create broadcast notification', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send broadcast to users
     */
    private function sendBroadcast(BroadcastNotification $broadcast): void
    {
        try {
            // Get target users
            $users = $this->getTargetUsers($broadcast->target_audience);
            
            $deliveredCount = 0;
            
            foreach ($users as $user) {
                try {
                    // Create inbox message for each user
                    InboxMessage::create([
                        'user_id' => $user->id,
                        'type' => 'broadcast',
                        'title' => $broadcast->title,
                        'message' => $broadcast->message,
                        'reference' => 'BROADCAST-' . $broadcast->id,
                        'metadata' => [
                            'broadcast_id' => $broadcast->id,
                            'type' => $broadcast->type,
                            'priority' => $broadcast->priority,
                            'action_text' => $broadcast->action_text,
                            'action_url' => $broadcast->action_url
                        ],
                        'is_read' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $deliveredCount++;
                    
                } catch (\Exception $e) {
                    Log::error('Failed to send broadcast to user', [
                        'user_id' => $user->id,
                        'broadcast_id' => $broadcast->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Update broadcast record
            $broadcast->update([
                'total_recipients' => $users->count(),
                'delivered_count' => $deliveredCount,
                'sent_at' => now(),
                'is_sent' => true
            ]);
            
            Log::info('Broadcast notification sent', [
                'broadcast_id' => $broadcast->id,
                'total_recipients' => $users->count(),
                'delivered_count' => $deliveredCount
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send broadcast', [
                'broadcast_id' => $broadcast->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get target users based on audience type
     */
    private function getTargetUsers(string $targetAudience)
    {
        $query = User::where('role', '!=', 'admin');
        
        switch ($targetAudience) {
            case 'active':
                $query->where('status', 'active')
                      ->where('last_login_at', '>', now()->subDays(30));
                break;
            case 'inactive':
                $query->where(function($q) {
                    $q->where('status', 'inactive')
                      ->orWhere('last_login_at', '<', now()->subDays(30))
                      ->orWhereNull('last_login_at');
                });
                break;
            case 'all':
            default:
                // All non-admin users
                break;
        }
        
        return $query->get();
    }

    /**
     * Send a specific broadcast (manual trigger)
     */
    public function send($id): JsonResponse
    {
        try {
            $broadcast = BroadcastNotification::findOrFail($id);
            
            if ($broadcast->is_sent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This broadcast has already been sent'
                ], 400);
            }
            
            $this->sendBroadcast($broadcast);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Broadcast sent successfully',
                'data' => $broadcast->fresh()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a broadcast
     */
    public function destroy($id): JsonResponse
    {
        try {
            $broadcast = BroadcastNotification::findOrFail($id);
            $broadcast->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Broadcast deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get broadcast statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_broadcasts' => BroadcastNotification::count(),
                'sent' => BroadcastNotification::where('is_sent', true)->count(),
                'pending' => BroadcastNotification::where('is_sent', false)->count(),
                'total_recipients' => BroadcastNotification::sum('total_recipients'),
                'total_delivered' => BroadcastNotification::sum('delivered_count'),
                'recent_broadcasts' => BroadcastNotification::with('admin:id,name')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
