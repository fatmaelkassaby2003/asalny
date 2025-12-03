<?php

namespace App\Http\Controllers\Api\Offers;

use App\Http\Controllers\Controller;
use App\Models\ExtensionRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ExtensionController extends Controller
{
    /**
     * طلب تمديد مدة الطلب (للمجيب)
     */
    public function requestExtension(Request $request, $orderId): JsonResponse
    {
        try {
            $answerer = $request->user();

            $order = Order::with('pendingExtensionRequest')->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود',
                ], 404);
            }

            if ($order->answerer_id !== $answerer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بطلب تمديد هذا الطلب',
                ], 403);
            }

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن طلب تمديد لطلب ' . $order->status,
                ], 400);
            }

            // التحقق من وجود طلب تمديد معلق
            if ($order->pendingExtensionRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'يوجد طلب تمديد معلق بالفعل',
                    'data' => [
                        'pending_request' => [
                            'id' => $order->pendingExtensionRequest->id,
                            'additional_minutes' => $order->pendingExtensionRequest->additional_minutes,
                        ]
                    ]
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'additional_minutes' => 'required|integer|min:5|max:1440',
                'reason' => 'nullable|string|max:500',
            ], [
                'additional_minutes.required' => 'مدة التمديد مطلوبة',
                'additional_minutes.integer' => 'مدة التمديد يجب أن تكون رقم صحيح',
                'additional_minutes.min' => 'الحد الأدنى للتمديد هو 5 دقائق',
                'additional_minutes.max' => 'الحد الأقصى للتمديد هو 1440 دقيقة (24 ساعة)',
                'reason.max' => 'السبب لا يمكن أن يتجاوز 500 حرف',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $extensionRequest = ExtensionRequest::create([
                'order_id' => $order->id,
                'answerer_id' => $answerer->id,
                'asker_id' => $order->asker_id,
                'additional_minutes' => $request->additional_minutes,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            Log::info('✅ تم إرسال طلب تمديد', [
                'extension_request_id' => $extensionRequest->id,
                'order_id' => $order->id,
                'additional_minutes' => $request->additional_minutes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال طلب التمديد بنجاح',
                'data' => [
                    'extension_request' => [
                        'id' => $extensionRequest->id,
                        'additional_minutes' => $extensionRequest->additional_minutes,
                        'reason' => $extensionRequest->reason,
                        'status' => $extensionRequest->status,
                        'created_at' => $extensionRequest->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في طلب التمديد', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال طلب التمديد',
            ], 500);
        }
    }

    /**
     * عرض طلبات التمديد للسائل
     */
    public function askerExtensionRequests(Request $request): JsonResponse
    {
        try {
            $asker = $request->user();

            $extensionRequests = ExtensionRequest::with(['order.question', 'answerer'])
                ->where('asker_id', $asker->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'extension_requests' => $extensionRequests->map(function ($request) {
                        return [
                            'id' => $request->id,
                            'additional_minutes' => $request->additional_minutes,
                            'reason' => $request->reason,
                            'status' => $request->status,
                            'order' => [
                                'id' => $request->order->id,
                                'question' => $request->order->question->question,
                                'current_expires_at' => $request->order->expires_at->format('Y-m-d H:i:s'),
                            ],
                            'answerer' => [
                                'id' => $request->answerer->id,
                                'name' => $request->answerer->name,
                            ],
                            'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'total' => $extensionRequests->count(),
                    'pending' => $extensionRequests->where('status', 'pending')->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض طلبات التمديد', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض طلبات التمديد',
            ], 500);
        }
    }

    /**
     * قبول طلب تمديد (للسائل)
     */
    public function acceptExtension(Request $request, $extensionId): JsonResponse
    {
        try {
            $asker = $request->user();

            $extensionRequest = ExtensionRequest::with('order')->find($extensionId);

            if (!$extensionRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'طلب التمديد غير موجود',
                ], 404);
            }

            if ($extensionRequest->asker_id !== $asker->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بقبول هذا الطلب',
                ], 403);
            }

            if ($extensionRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب تم معالجته بالفعل',
                ], 400);
            }

            $extensionRequest->accept();

            Log::info('✅ تم قبول طلب التمديد', [
                'extension_request_id' => $extensionRequest->id,
                'order_id' => $extensionRequest->order_id,
                'new_expires_at' => $extensionRequest->order->fresh()->expires_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم قبول طلب التمديد بنجاح',
                'data' => [
                    'order' => [
                        'id' => $extensionRequest->order->id,
                        'new_expires_at' => $extensionRequest->order->fresh()->expires_at->format('Y-m-d H:i:s'),
                        'remaining_minutes' => $extensionRequest->order->fresh()->remaining_time,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في قبول طلب التمديد', [
                'error' => $e->getMessage(),
                'extension_id' => $extensionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء قبول طلب التمديد',
            ], 500);
        }
    }

    /**
     * رفض طلب تمديد (للسائل)
     */
    public function rejectExtension(Request $request, $extensionId): JsonResponse
    {
        try {
            $asker = $request->user();

            $extensionRequest = ExtensionRequest::with('order')->find($extensionId);

            if (!$extensionRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'طلب التمديد غير موجود',
                ], 404);
            }

            if ($extensionRequest->asker_id !== $asker->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك برفض هذا الطلب',
                ], 403);
            }

            if ($extensionRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب تم معالجته بالفعل',
                ], 400);
            }

            // رفض الطلب وإلغاء Order وإرجاع المبلغ
            $extensionRequest->reject();

            Log::info('✅ تم رفض طلب التمديد وإلغاء الطلب', [
                'extension_request_id' => $extensionRequest->id,
                'order_id' => $extensionRequest->order_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم رفض طلب التمديد وإلغاء الطلب وإرجاع المبلغ',
                'data' => [
                    'wallet' => [
                        'balance' => $asker->fresh()->wallet_balance,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في رفض طلب التمديد', [
                'error' => $e->getMessage(),
                'extension_id' => $extensionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض طلب التمديد',
            ], 500);
        }
    }
}