<?php

namespace App\Http\Controllers;

use App\Models\InboxMessage;
use App\Models\SmsOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InboxController extends Controller
{
    /**
     * Get user's inbox messages (including SMS orders)
     */
    public function getMessages(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $targetUserId = $user->id;
            // Admin override: allow querying another user's inbox with ?user_id=
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                $overrideId = $request->input('user_id');
                if ($overrideId) {
                    $targetUserId = (int) $overrideId;
                }
            }
            
            $validator = Validator::make($request->all(), [
                'type' => 'nullable|string|in:electricity_token,transaction,general,sms_order',
                'is_read' => 'nullable|boolean',
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $limit = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $type = $request->input('type');
            $isRead = $request->input('is_read');

            // Get inbox messages from database
            $inboxQuery = InboxMessage::where('user_id', $targetUserId);
            if ($type) {
                $inboxQuery->where('type', $type);
            }
            if ($isRead !== null) {
                $inboxQuery->where('is_read', $isRead);
            }

            // Get SMS orders and convert them to inbox message format
            $smsQuery = SmsOrder::where('user_id', $targetUserId)
                ->with('smsService')
                ->whereIn('status', ['pending', 'active']); // Only show active/pending orders

            if ($type === 'sms_order') {
                // Only SMS orders
                $smsOrders = $smsQuery->get();
                $inboxMessages = collect();
            } elseif ($type && $type !== 'sms_order') {
                // Only inbox messages (exclude SMS orders)
                $inboxMessages = $inboxQuery->get();
                $smsOrders = collect();
            } else {
                // Both inbox messages and SMS orders
                $inboxMessages = $inboxQuery->get();
                $smsOrders = $smsQuery->get();
            }

            // Transform inbox messages
            $transformedInboxMessages = $inboxMessages->map(function ($message) {
                return [
                    'id' => 'inbox_' . $message->id,
                    'type' => $message->type,
                    'title' => $message->title,
                    'message' => $message->message,
                    'reference' => $message->reference,
                    'metadata' => $message->metadata,
                    'is_read' => $message->is_read,
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at->toISOString(),
                    'updated_at' => $message->updated_at->toISOString(),
                    'source' => 'inbox'
                ];
            });

            // Transform SMS orders to inbox message format
            $transformedSmsOrders = $smsOrders->map(function ($order) {
                $statusLabel = $order->getStatusLabel();
                $serviceName = $order->getServiceDisplayName();
                $phoneNumber = $order->getFormattedPhoneNumber();
                
                return [
                    'id' => 'sms_' . $order->id,
                    'type' => 'sms_order',
                    'title' => "Fadded VIP SMS Order - {$serviceName}",
                    'message' => "Your virtual number {$phoneNumber} for {$serviceName} is ready. Waiting for SMS verification code to arrive.",
                    'reference' => $order->order_id,
                    'metadata' => [
                        'order_id' => $order->order_id,
                        'phone_number' => $order->phone_number,
                        'formatted_phone' => $phoneNumber,
                        'service' => $order->service,
                        'service_name' => $serviceName,
                        'country' => $order->country,
                        'cost' => $order->cost,
                        'status' => $order->status,
                        'status_label' => $statusLabel,
                        'expires_at' => $order->expires_at?->toISOString(),
                        'provider' => $order->smsService->provider ?? 'unknown',
                        'provider_name' => $order->smsService->name ?? 'Unknown Provider'
                    ],
                    'is_read' => false, // SMS orders are always unread until SMS is received
                    'read_at' => null,
                    'created_at' => $order->created_at->toISOString(),
                    'updated_at' => $order->updated_at->toISOString(),
                    'source' => 'sms_order'
                ];
            });

            // Combine and sort all messages by created_at desc
            $allMessages = $transformedInboxMessages->concat($transformedSmsOrders)
                ->sortByDesc('created_at')
                ->values();

            // Apply pagination manually since we're combining two data sources
            $totalMessages = $allMessages->count();
            $offset = ($page - 1) * $limit;
            $paginatedMessages = $allMessages->slice($offset, $limit)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $paginatedMessages,
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => ceil($totalMessages / $limit),
                        'per_page' => $limit,
                        'total' => $totalMessages,
                        'has_more' => $offset + $limit < $totalMessages
                    ]
                ],
                'message' => 'Inbox messages retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inbox messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread message count (including SMS orders)
     */
    public function getUnreadCount(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Count unread inbox messages
            $unreadInboxCount = InboxMessage::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            // Count active SMS orders (they're always "unread" until SMS is received)
            $unreadSmsCount = SmsOrder::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'active'])
                ->count();

            $totalUnreadCount = $unreadInboxCount + $unreadSmsCount;

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $totalUnreadCount,
                    'inbox_unread' => $unreadInboxCount,
                    'sms_unread' => $unreadSmsCount
                ],
                'message' => 'Unread count retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve unread count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $messageId = $request->message_id;

            // Check if it's an inbox message or SMS order
            if (str_starts_with($messageId, 'inbox_')) {
                $actualId = substr($messageId, 6); // Remove 'inbox_' prefix
                $message = InboxMessage::where('user_id', $user->id)
                    ->where('id', $actualId)
                    ->first();

                if (!$message) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Message not found'
                    ], 404);
                }

                $message->markAsRead();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'message_id' => $messageId,
                        'is_read' => true,
                        'read_at' => $message->read_at
                    ],
                    'message' => 'Message marked as read'
                ]);
            } elseif (str_starts_with($messageId, 'sms_')) {
                // For SMS orders, we don't mark them as "read" since they should stay visible until SMS is received
                return response()->json([
                    'success' => true,
                    'data' => [
                        'message_id' => $messageId,
                        'is_read' => false, // SMS orders remain "unread" until completed
                        'read_at' => null
                    ],
                    'message' => 'SMS orders remain visible until SMS is received'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid message ID format'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark message as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all messages as read
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Only mark inbox messages as read, not SMS orders
            $updated = InboxMessage::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_count' => $updated
                ],
                'message' => 'All inbox messages marked as read (SMS orders remain visible until completed)'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all messages as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get message details
     */
    public function getMessageDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $messageId = $request->message_id;

            // Check if it's an inbox message or SMS order
            if (str_starts_with($messageId, 'inbox_')) {
                $actualId = substr($messageId, 6); // Remove 'inbox_' prefix
                $message = InboxMessage::where('user_id', $user->id)
                    ->where('id', $actualId)
                    ->first();

                if (!$message) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Message not found'
                    ], 404);
                }

                // Mark as read when viewing details
                if (!$message->is_read) {
                    $message->markAsRead();
                }

                $messageData = [
                    'id' => $messageId,
                    'type' => $message->type,
                    'title' => $message->title,
                    'message' => $message->message,
                    'reference' => $message->reference,
                    'metadata' => $message->metadata,
                    'is_read' => $message->is_read,
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at->toISOString(),
                    'updated_at' => $message->updated_at->toISOString(),
                    'source' => 'inbox'
                ];

                // Add electricity-specific details if it's an electricity token message
                if ($message->type === 'electricity_token' && $message->metadata) {
                    $metadata = $message->metadata;
                    $messageData['electricity_details'] = [
                        'token' => $metadata['token'] ?? null,
                        'units' => $metadata['units'] ?? null,
                        'customer_name' => $metadata['customer_name'] ?? null,
                        'address' => $metadata['address'] ?? null,
                        'amount' => $metadata['amount'] ?? null,
                        'service_id' => $metadata['service_id'] ?? null,
                        'customer_id' => $metadata['customer_id'] ?? null,
                        'variation_id' => $metadata['variation_id'] ?? null
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => $messageData,
                    'message' => 'Message details retrieved successfully'
                ]);

            } elseif (str_starts_with($messageId, 'sms_')) {
                $actualId = substr($messageId, 4); // Remove 'sms_' prefix
                $order = SmsOrder::where('user_id', $user->id)
                    ->where('id', $actualId)
                    ->with('smsService')
                    ->first();

                if (!$order) {
                    return response()->json([
                        'success' => false,
                        'message' => 'SMS order not found'
                    ], 404);
                }

                $statusLabel = $order->getStatusLabel();
                $serviceName = $order->getServiceDisplayName();
                $phoneNumber = $order->getFormattedPhoneNumber();

                $messageData = [
                    'id' => $messageId,
                    'type' => 'sms_order',
                    'title' => "Fadded VIP SMS Order - {$serviceName}",
                    'message' => "Your virtual number {$phoneNumber} for {$serviceName} is ready. Waiting for SMS verification code to arrive.",
                    'reference' => $order->order_id,
                    'metadata' => [
                        'order_id' => $order->order_id,
                        'phone_number' => $order->phone_number,
                        'formatted_phone' => $phoneNumber,
                        'service' => $order->service,
                        'service_name' => $serviceName,
                        'country' => $order->country,
                        'cost' => $order->cost,
                        'status' => $order->status,
                        'status_label' => $statusLabel,
                        'expires_at' => $order->expires_at?->toISOString(),
                        'provider' => $order->smsService->provider ?? 'unknown',
                        'provider_name' => $order->smsService->name ?? 'Unknown Provider',
                        'sms_code' => $order->sms_code,
                        'received_at' => $order->received_at?->toISOString()
                    ],
                    'is_read' => false, // SMS orders remain "unread" until completed
                    'read_at' => null,
                    'created_at' => $order->created_at->toISOString(),
                    'updated_at' => $order->updated_at->toISOString(),
                    'source' => 'sms_order'
                ];

                return response()->json([
                    'success' => true,
                    'data' => $messageData,
                    'message' => 'SMS order details retrieved successfully'
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid message ID format'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve message details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete message
     */
    public function deleteMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $messageId = $request->message_id;

            // Check if it's an inbox message or SMS order
            if (str_starts_with($messageId, 'inbox_')) {
                $actualId = substr($messageId, 6); // Remove 'inbox_' prefix
                $message = InboxMessage::where('user_id', $user->id)
                    ->where('id', $actualId)
                    ->first();

                if (!$message) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Message not found'
                    ], 404);
                }

                $message->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Message deleted successfully'
                ]);

            } elseif (str_starts_with($messageId, 'sms_')) {
                // For SMS orders, we don't allow deletion since they're active orders
                return response()->json([
                    'success' => false,
                    'message' => 'SMS orders cannot be deleted. They will be removed automatically when completed or expired.'
                ], 400);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid message ID format'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get electricity token messages specifically
     */
    public function getElectricityTokens(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $limit = $request->input('limit', 20);
            $page = $request->input('page', 1);

            $messages = InboxMessage::where('user_id', $user->id)
                ->where('type', 'electricity_token')
                ->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            $transformedMessages = $messages->map(function ($message) {
                $metadata = $message->metadata ?? [];
                return [
                    'id' => 'inbox_' . $message->id,
                    'title' => $message->title,
                    'message' => $message->message,
                    'reference' => $message->reference,
                    'token' => $metadata['token'] ?? null,
                    'units' => $metadata['units'] ?? null,
                    'customer_name' => $metadata['customer_name'] ?? null,
                    'address' => $metadata['address'] ?? null,
                    'amount' => $metadata['amount'] ?? null,
                    'service_id' => $metadata['service_id'] ?? null,
                    'customer_id' => $metadata['customer_id'] ?? null,
                    'variation_id' => $metadata['variation_id'] ?? null,
                    'is_read' => $message->is_read,
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at->toISOString(),
                    'source' => 'inbox'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'electricity_tokens' => $transformedMessages,
                    'pagination' => [
                        'current_page' => $messages->currentPage(),
                        'last_page' => $messages->lastPage(),
                        'per_page' => $messages->perPage(),
                        'total' => $messages->total(),
                        'has_more' => $messages->hasMorePages()
                    ]
                ],
                'message' => 'Electricity token messages retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve electricity token messages: ' . $e->getMessage()
            ], 500);
        }
    }
}
