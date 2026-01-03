<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * الحصول على أو إنشاء محادثة
     */
    public function getOrCreateChat(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $order = Order::with(['asker', 'answerer'])->find($request->order_id);

            // التحقق من أن المستخدم طرف في الطلب
            if ($order->asker_id !== $user->id && $order->answerer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول لهذه المحادثة',
                ], 403);
            }

            // الحصول على المحادثة أو إنشاؤها
            $chat = Chat::firstOrCreate(
                [
                    'asker_id' => $order->asker_id,
                    'answerer_id' => $order->answerer_id,
                    'order_id' => $order->id,
                ]
            );

            $otherParticipant = $chat->getOtherParticipant($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'chat' => [
                        'id' => $chat->id,
                        'order_id' => $chat->order_id,
                        'last_message_at' => $chat->last_message_at ? \Carbon\Carbon::parse($chat->last_message_at)->format('Y-m-d H:i:s') : null,
                        'unread_count' => $chat->unreadCountFor($user->id),
                        'other_participant' => [
                            'id' => $otherParticipant->id,
                            'name' => $otherParticipant->name,
                            'phone' => $otherParticipant->phone,
                        ],
                        'created_at' => $chat->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إنشاء المحادثة', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المحادثة',
            ], 500);
        }
    }

    /**
     * قائمة المحادثات للمستخدم
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $unreadOnly = $request->query('unread_only', false);

            $query = Chat::with(['asker', 'answerer', 'lastMessage.sender', 'order'])
                ->where(function ($q) use ($user) {
                    $q->where('asker_id', $user->id)
                        ->orWhere('answerer_id', $user->id);
                })
                // ✅ Eager load unread count to avoid N+1 problem
                ->withCount(['messages as unread_count' => function ($q) use ($user) {
                    $q->where('sender_id', '!=', $user->id)
                      ->where('is_read', false);
                }]);

            // ✅ الفلترة حسب المحادثات غير المقروءة فقط
            if ($unreadOnly) {
                $query->having('unread_count', '>', 0);
            }

            $chats = $query->orderBy('last_message_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($chat) use ($user) {
                    $otherParticipant = $chat->getOtherParticipant($user->id);
                    $lastMessage = $chat->lastMessage;

                    return [
                        'id' => $chat->id,
                        'order_id' => $chat->order_id,
                        'other_participant' => [
                            'id' => $otherParticipant->id,
                            'name' => $otherParticipant->name,
                            'phone' => $otherParticipant->phone,
                        ],
                        'last_message' => $lastMessage ? [
                            'message' => $lastMessage->message,
                            'sender_name' => $lastMessage->sender->name,
                            'is_mine' => $lastMessage->sender_id === $user->id,
                            'created_at' => $lastMessage->created_at->format('Y-m-d H:i:s'),
                        ] : null,
                        'unread_count' => $chat->unread_count, // From withCount
                        'has_unread_messages' => $chat->unread_count > 0, // ✅ تمييز المحادثات غير المقروءة
                        'last_message_at' => $chat->last_message_at ? \Carbon\Carbon::parse($chat->last_message_at)->format('Y-m-d H:i:s') : null,
                        'created_at' => $chat->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'chats' => $chats,
                    'total' => $chats->count(),
                    'total_unread_conversations' => $chats->where('has_unread_messages', true)->count(),
                    'total_unread_messages' => $chats->sum('unread_count'),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب المحادثات', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المحادثات',
            ], 500);
        }
    }

    /**
     * جلب رسائل محادثة معينة
     */
    public function getMessages(Request $request, $chatId): JsonResponse
    {
        try {
            $user = $request->user();

            $chat = Chat::with(['asker', 'answerer'])->find($chatId);

            if (!$chat) {
                return response()->json([
                    'success' => false,
                    'message' => 'المحادثة غير موجودة',
                ], 404);
            }

            // التحقق من أن المستخدم مشارك في المحادثة
            if (!$chat->isParticipant($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول لهذه المحادثة',
                ], 403);
            }

            $messages = $chat->messages()
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) use ($user) {
                    return [
                        'id' => $message->id,
                        'message' => $message->message,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name,
                        ],
                        'is_mine' => $message->sender_id === $user->id,
                        'is_read' => $message->is_read,
                        'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // تحديد الرسائل كمقروءة
            $chat->messages()
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            $otherParticipant = $chat->getOtherParticipant($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'chat' => [
                        'id' => $chat->id,
                        'order_id' => $chat->order_id,
                        'other_participant' => [
                            'id' => $otherParticipant->id,
                            'name' => $otherParticipant->name,
                            'phone' => $otherParticipant->phone,
                        ],
                    ],
                    'messages' => $messages,
                    'total' => $messages->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب الرسائل', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الرسائل',
            ], 500);
        }
    }

    /**
     * إرسال رسالة
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // ✅ قراءة chat_id من body
            $chatId = $request->input('chat_id');
            
            if (!$chatId) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف المحادثة مطلوب في body (chat_id)',
                ], 422);
            }

            $chat = Chat::find($chatId);

            if (!$chat) {
                return response()->json([
                    'success' => false,
                    'message' => 'المحادثة غير موجودة',
                ], 404);
            }

            // التحقق من أن المستخدم مشارك في المحادثة
            if (!$chat->isParticipant($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإرسال رسائل في هذه المحادثة',
                ], 403);
            }

            // ✅ منع الإرسال إذا كان الطلب قيد المراجعة (اعتراض ثاني)
            if ($chat->order && ($chat->order->status === 'under_review' || $chat->order->dispute_count >= 2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، تم إغلاق الشات لأن الطلب قيد مراجعة الإدارة',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:2000',
            ], [
                'message.required' => 'نص الرسالة مطلوب',
                'message.max' => 'الرسالة طويلة جداً',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // إنشاء الرسالة
            $message = Message::create([
                'chat_id' => $chat->id,
                'sender_id' => $user->id,
                'message' => $request->message,
            ]);

            // تحديث وقت آخر رسالة في المحادثة
            $chat->update([
                'last_message_at' => now(),
            ]);

            // تحميل علاقة المرسل
            $message->load('sender');

            // بث الرسالة عبر Pusher
            broadcast(new MessageSent($message))->toOthers();

            Log::info('✅ تم إرسال رسالة', [
                'message_id' => $message->id,
                'chat_id' => $chat->id,
                'sender_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'data' => [
                    'message' => [
                        'id' => $message->id,
                        'message' => $message->message,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name,
                        ],
                        'is_read' => $message->is_read,
                        'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إرسال الرسالة', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الرسالة',
            ], 500);
        }
    }
}
