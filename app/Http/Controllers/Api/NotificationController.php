<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * تسجيل/تحديث FCM Token للمستخدم
     */
    public function registerToken(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'fcm_token' => 'required|string',
            ]);

            $user = auth('api')->user();
            $user->update(['fcm_token' => $request->fcm_token]);

            Log::info('✅ FCM token registered', [
                'user_id' => $user->id,
                'token' => substr($request->fcm_token, 0, 20) . '...',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token registered successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('❌ FCM token registration failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register FCM token',
            ], 500);
        }
    }

    /**
     * إرسال إشعار تجريبي للمستخدم الحالي
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user->fcm_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'FCM token not registered',
                ], 400);
            }

            $sent = $this->firebase->sendToUser(
                $user->fcm_token,
                'مرحباً! 👋',
                'هذا إشعار تجريبي من تطبيق أسألني',
                ['type' => 'test']
            );

            return response()->json([
                'success' => $sent,
                'message' => $sent ? 'Test notification sent' : 'Failed to send notification',
            ], $sent ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('❌ Test notification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
            ], 500);
        }
    }

    /**
     * إرسال إشعار مخصص لمستخدم معين (للأدمن)
     */
    public function sendToUser(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'data' => 'nullable|array',
            ]);

            $user = \App\Models\User::find($request->user_id);

            if (!$user->fcm_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have FCM token',
                ], 400);
            }

            $sent = $this->firebase->sendToUser(
                $user->fcm_token,
                $request->title,
                $request->body,
                $request->data
            );

            return response()->json([
                'success' => $sent,
                'message' => $sent ? 'Notification sent successfully' : 'Failed to send notification',
            ], $sent ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('❌ Send notification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إرسال إشعار لعدة مستخدمين
     */
    public function sendToMultiple(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'data' => 'nullable|array',
            ]);

            $users = \App\Models\User::whereIn('id', $request->user_ids)
                ->whereNotNull('fcm_token')
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users with FCM tokens found',
                ], 400);
            }

            $tokens = $users->pluck('fcm_token')->toArray();
            $results = $this->firebase->sendToMultiple(
                $tokens,
                $request->title,
                $request->body,
                $request->data
            );

            $successCount = count(array_filter($results));

            return response()->json([
                'success' => true,
                'message' => "Sent to $successCount out of " . count($results) . " users",
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Send multiple notifications failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notifications',
            ], 500);
        }
    }

    /**
     * Get all user notifications (paginated)
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);

            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications->items(),
                    'pagination' => [
                        'total' => $notifications->total(),
                        'per_page' => $notifications->perPage(),
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to get notifications', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
            ], 500);
        }
    }

    /**
     * Get latest notifications
     */
    public function getLatest(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = $request->input('limit', 10);

            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            $unreadCount = Notification::where('user_id', $user->id)
                ->unread()
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to get latest notifications', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
            ], 500);
        }
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $count = Notification::where('user_id', $user->id)
                ->unread()
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get count',
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId): JsonResponse
    {
        try {
            $user = $request->user();

            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found',
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as read',
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Notification::where('user_id', $user->id)
                ->unread()
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all as read',
            ], 500);
        }
    }
}
