<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    /**
     * Get all tickets for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = SupportTicket::with(['messages' => function($q) {
            $q->latest()->take(1); // Get last message for preview
        }]);

        // Admins see all tickets, users see only their own
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tickets = $query->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $tickets
        ]);
    }

    /**
     * Create a new support ticket
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category' => 'nullable|in:general,payment,service,technical,other',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = $request->user();

            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subject' => $request->subject,
                'description' => $request->description,
                'category' => $request->category ?? 'general',
                'priority' => $request->priority ?? 'medium',
                'status' => 'open',
            ]);

            // Create first message
            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $request->description,
                'is_admin' => false,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Support ticket created successfully',
                'data' => [
                    'ticket' => $ticket->load('messages')
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific ticket with all messages
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        $ticket = SupportTicket::with(['user:id,name,email', 'messages.user:id,name,email', 'assignedAdmin:id,name,email'])
            ->findOrFail($id);

        // Users can only view their own tickets, admins can view all
        if (!$user->isAdmin() && $ticket->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'ticket' => $ticket
            ]
        ]);
    }

    /**
     * Add a message to a ticket
     */
    public function addMessage(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $ticket = SupportTicket::findOrFail($id);

        // Users can only reply to their own tickets, admins can reply to all
        if (!$user->isAdmin() && $ticket->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied'
            ], 403);
        }

        // Don't allow messages on closed tickets
        if ($ticket->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot add messages to closed tickets'
            ], 400);
        }

        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin' => $user->isAdmin(),
        ]);

        // If ticket was resolved, reopen it when user replies
        if ($ticket->status === 'resolved' && !$user->isAdmin()) {
            $ticket->update(['status' => 'open', 'resolved_at' => null]);
        }

        // If admin replies to open ticket, mark as in_progress
        if ($ticket->status === 'open' && $user->isAdmin()) {
            $ticket->update(['status' => 'in_progress']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Message added successfully',
            'data' => [
                'message' => $message->load('user:id,name,email'),
                'ticket' => $ticket->fresh()
            ]
        ]);
    }

    /**
     * Update ticket status (Admin only)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::findOrFail($id);
        
        $updateData = ['status' => $request->status];
        
        // Set resolved_at when marking as resolved
        if ($request->status === 'resolved' && !$ticket->resolved_at) {
            $updateData['resolved_at'] = now();
        }
        
        // Clear resolved_at if reopening
        if (in_array($request->status, ['open', 'in_progress']) && $ticket->resolved_at) {
            $updateData['resolved_at'] = null;
        }
        
        $ticket->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket status updated successfully',
            'data' => [
                'ticket' => $ticket->fresh()
            ]
        ]);
    }

    /**
     * Assign ticket to admin (Admin only)
     */
    public function assign(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'admin_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['assigned_to' => $request->admin_id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket assigned successfully',
            'data' => [
                'ticket' => $ticket->fresh()->load('assignedAdmin:id,name,email')
            ]
        ]);
    }

    /**
     * Get unread ticket count (tickets with new admin replies)
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Simplified logic: Count tickets with admin messages that are newer than user's last message
            $unreadCount = 0;
            
            // Get all user's tickets
            $userTickets = SupportTicket::where('user_id', $user->id)->get();
            
            foreach ($userTickets as $ticket) {
                // Get the latest user message timestamp
                $latestUserMessage = $ticket->messages()
                    ->where('is_admin', false)
                    ->where('user_id', $user->id)
                    ->latest('created_at')
                    ->first();
                
                if (!$latestUserMessage) {
                    // If no user messages, count all admin messages as unread
                    $adminMessageCount = $ticket->messages()->where('is_admin', true)->count();
                    if ($adminMessageCount > 0) {
                        $unreadCount++;
                    }
                } else {
                    // Check if there are admin messages after the latest user message
                    $newerAdminMessages = $ticket->messages()
                        ->where('is_admin', true)
                        ->where('created_at', '>', $latestUserMessage->created_at)
                        ->count();
                    
                    if ($newerAdminMessages > 0) {
                        $unreadCount++;
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'unread_count' => $unreadCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ticket statistics (Admin only)
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
            'closed' => SupportTicket::where('status', 'closed')->count(),
            'by_priority' => [
                'low' => SupportTicket::where('priority', 'low')->count(),
                'medium' => SupportTicket::where('priority', 'medium')->count(),
                'high' => SupportTicket::where('priority', 'high')->count(),
                'urgent' => SupportTicket::where('priority', 'urgent')->count(),
            ],
            'by_category' => [
                'general' => SupportTicket::where('category', 'general')->count(),
                'payment' => SupportTicket::where('category', 'payment')->count(),
                'service' => SupportTicket::where('category', 'service')->count(),
                'technical' => SupportTicket::where('category', 'technical')->count(),
                'other' => SupportTicket::where('category', 'other')->count(),
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}
