<?php

namespace App\Http\Controllers\Api\Offers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * عرض طلبات المجيب (الطلبات الواردة له)
     */
    public function answererOrders(Request $request): JsonResponse
    {
        try {
            $answerer = $request->user();

            if ($answerer->is_asker) {
                return response()->json([
                    'success' => false,
                    'message' => 'السائلين ليس لديهم طلبات للإجابة',
                ], 403);
            }

            // تحديث الطلبات المنتهية
            Order::updateExpiredOrders();

            $orders = Order::with(['question', 'asker', 'offer'])
                ->where('answerer_id', $answerer->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'status' => $order->status,
                            'price' => $order->price,
                            'response_time' => $order->response_time,
                            'remaining_minutes' => $order->remaining_time,
                            'expires_at' => \Carbon\Carbon::parse($order->expires_at)->format('Y-m-d H:i'),
                            'answered_at' => $order->answered_at ? $order->answered_at->format('Y-m-d H:i:s') : null,
                            'question' => [
                                'id' => $order->question->id,
                                'question' => $order->question->question,
                            ],
                    
                            'asker' => [
                                'id' => $order->asker->id,
                                'name' => $order->asker->name,
                                'phone' => $order->asker->phone,
                            ],
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'total' => $orders->count(),
                    'pending' => $orders->where('status', 'pending')->count(),
                    'answered' => $orders->where('status', 'answered')->count(),
                    'expired' => $orders->where('status', 'expired')->count(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض طلبات المجيب', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض الطلبات',
            ], 500);
        }
    }

    /**
     * الإجابة على طلب (للمجيب فقط)
     * يتضمن فحص رصيد السائل وحجز المبلغ
     */
    public function answerOrder(Request $request, $orderId): JsonResponse
    {
        try {
            $answerer = $request->user();

            $order = Order::with(['asker', 'question'])->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود',
                ], 404);
            }

            if ($order->answerer_id !== $answerer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالإجابة على هذا الطلب',
                ], 403);
            }

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب تم معالجته بالفعل',
                ], 400);
            }

            // التحقق من انتهاء الوقت
            if ($order->isExpired()) {
                $order->update(['status' => 'expired']);
                return response()->json([
                    'success' => false,
                    'message' => 'انتهى وقت الإجابة على هذا الطلب',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'answer_text' => 'required_without:answer_image|string|max:2000',
                'answer_image' => 'required_without:answer_text|image|mimes:jpeg,png,jpg|max:5120',
            ], [
                'answer_text.required_without' => 'يجب إدخال نص الإجابة أو صورة',
                'answer_image.required_without' => 'يجب إدخال صورة أو نص للإجابة',
                'answer_image.image' => 'يجب أن يكون الملف صورة',
                'answer_image.mimes' => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg',
                'answer_image.max' => 'حجم الصورة يجب أن لا يتجاوز 5 ميجا',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // ✅ التحقق من رصيد السائل قبل الإجابة
            $walletService = app(\App\Services\WalletService::class);
            $asker = $order->asker;
            $currentBalance = $walletService->getBalance($asker);
            
            if ($currentBalance < $order->price) {
                $shortage = $order->price - $currentBalance;
                
                Log::warning('❌ رصيد المحفظة غير كافي للسائل', [
                    'asker_id' => $asker->id,
                    'current_balance' => $currentBalance,
                    'required_amount' => $order->price,
                    'shortage' => $shortage,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'رصيد السائل غير كافي لإتمام الطلب',
                    'data' => [
                        'current_balance' => $currentBalance,
                        'required_amount' => $order->price,
                        'shortage' => $shortage,
                        'note' => 'يرجى إبلاغ السائل بشحن المحفظة',
                    ]
                ], 402); // 402 Payment Required
            }

            $answerData = [
                'answer_text' => $request->answer_text,
                'status' => 'answered',
                'answered_at' => now(),
            ];

            // رفع الصورة إن وجدت
            if ($request->hasFile('answer_image')) {
                $image = $request->file('answer_image');
                $path = $image->store('answers', 'public');
                $answerData['answer_image'] = $path;
            }

            $order->update($answerData);

            // ✅ حجز المبلغ من محفظة السائل
            try {
                $transaction = $walletService->holdAmount($asker, $order, $order->price);
                
                Log::info('✅ تم حجز المبلغ من محفظة السائل', [
                    'asker_id' => $asker->id,
                    'order_id' => $order->id,
                    'amount' => $order->price,
                    'balance_after' => $transaction->balance_after,
                ]);
                
            } catch (\Exception $e) {
                // إذا فشل حجز المبلغ، إلغاء الإجابة
                $order->update([
                    'answer_text' => null,
                    'answer_image' => null,
                    'status' => 'pending',
                    'answered_at' => null,
                ]);
                
                Log::error('❌ فشل حجز المبلغ بعد الإجابة', [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'فشل حجز المبلغ من محفظة السائل',
                    'error' => $e->getMessage(),
                ], 500);
            }

            Log::info('✅ تم الإجابة على الطلب وحجز المبلغ', [
                'order_id' => $order->id,
                'answerer_id' => $answerer->id,
                'amount_held' => $order->price,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإجابة وحجز المبلغ بنجاح',
                'data' => [
                    'question' => [
                        'id' => $order->question->id,
                        'question' => $order->question->question,
                        'price' => $order->question->price,
                    ],
                    'order' => [
                        'id' => $order->id,
                        'status' => $order->status,
                        'price' => $order->price,
                        'answer_text' => $order->answer_text,
                        'answer_image' => $order->answer_image ? Storage::url($order->answer_image) : null,
                        'answered_at' => $order->answered_at->format('Y-m-d H:i:s'),
                        'response_time' => $order->response_time,
                        'expires_at' => $order->expires_at->format('Y-m-d H:i:s'),
                        'remaining_minutes' => $order->remaining_time,
                        'held_amount' => $order->held_amount,
                    ],
                    'wallet' => [
                        'asker_balance_before' => $transaction->balance_before,
                        'asker_balance_after' => $transaction->balance_after,
                        'amount_held' => $order->price,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ خطأ في الإجابة على الطلب', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإجابة',
            ], 500);
        }
    }

    /**
     * إلغاء طلب (للسائل فقط)
     */
    public function cancelOrder(Request $request, $orderId): JsonResponse
    {
        try {
            $asker = $request->user();

            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود',
                ], 404);
            }

            if ($order->asker_id !== $asker->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإلغاء هذا الطلب',
                ], 403);
            }

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إلغاء طلب ' . $order->status,
                ], 400);
            }

            $order->cancel();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الطلب بنجاح',
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ خطأ في إلغاء الطلب', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الطلب',
            ], 500);
        }
    }

    /**
     * عرض سؤال معين مع حالته وتفاصيله
     */
    public function showQuestionWithStatus(Request $request, $questionId): JsonResponse
    {
        try {
            $asker = $request->user();

            $question = UserQuestion::with(['location', 'offers.answerer', 'offers.order'])
                ->where('user_id', $asker->id)
                ->find($questionId);

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'السؤال غير موجود',
                ], 404);
            }

            // تحديد حالة السؤال
            $acceptedOffer = $question->offers->firstWhere('status', 'accepted');
            $order = $acceptedOffer ? $acceptedOffer->order : null;

            if ($order) {
                Order::updateExpiredOrders();
                $order->refresh();

                if ($order->status === 'answered') {
                    $questionStatus = 'answered';
                } elseif ($order->status === 'pending') {
                    $questionStatus = 'waiting_answer';
                } elseif ($order->status === 'cancelled') {
                    $questionStatus = 'cancelled';
                } elseif ($order->status === 'expired') {
                    $questionStatus = 'expired';
                } else {
                    $questionStatus = 'unknown';
                }
            } else {
                $pendingOffers = $question->offers->where('status', 'pending');
                $questionStatus = $pendingOffers->count() > 0 ? 'has_offers' : 'no_offers';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'question' => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                        'is_active' => $question->is_active,
                        'status' => $questionStatus,
                        'views_count' => $question->views()->count(),
                        'location' => [
                            'id' => $question->location->id,
                            'title' => $question->location->title,
                            'address' => $question->location->address,
                            'latitude' => $question->location->latitude,
                            'longitude' => $question->location->longitude,
                        ],
                        'offers' => $question->offers->map(function ($offer) {
                            return [
                                'id' => $offer->id,
                                'price' => $offer->price,
                                'response_time' => $offer->response_time,
                                'note' => $offer->note,
                                'status' => $offer->status,
                                'answerer' => [
                                    'id' => $offer->answerer->id,
                                    'name' => $offer->answerer->name,
                                    'phone' => $offer->answerer->phone,
                                ],
                                'created_at' => $offer->created_at->format('Y-m-d H:i:s'),
                            ];
                        }),
                        'order' => $order ? [
                            'id' => $order->id,
                            'status' => $order->status,
                            'price' => $order->price,
                            'response_time' => $order->response_time,
                            'remaining_minutes' => $order->remaining_time,
                            'expires_at' => $order->expires_at->format('Y-m-d H:i:s'),
                            'answer_text' => $order->answer_text,
                            'answer_image' => $order->answer_image ? Storage::url($order->answer_image) : null,
                            'answered_at' => $order->answered_at ? $order->answered_at->format('Y-m-d H:i:s') : null,
                            'answerer' => [
                                'id' => $order->answerer->id,
                                'name' => $order->answerer->name,
                                'phone' => $order->answerer->phone,
                            ],
                        ] : null,
                        'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض تفاصيل السؤال', [
                'error' => $e->getMessage(),
                'question_id' => $questionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض السؤال',
            ], 500);
        }
    }

    /**
     * متابعة الإجابة - للسائل (يتحقق من الرصيد ويعرض تفاصيل الطلب)
     */
    public function followAnswer(Request $request, $orderId): JsonResponse
    {
        try {
            $asker = $request->user();

            $order = Order::with(['question', 'answerer'])->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود',
                ], 404);
            }

            if ($order->asker_id !== $asker->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بمتابعة هذا الطلب',
                ], 403);
            }

            // ✅ التحقق من رصيد المحفظة
            $walletService = app(\App\Services\WalletService::class);
            $currentBalance = $walletService->getBalance($asker);
            
            if ($currentBalance < $order->price) {
                $shortage = $order->price - $currentBalance;
                
                Log::warning('❌ رصيد المحفظة غير كافي للمتابعة', [
                    'asker_id' => $asker->id,
                    'current_balance' => $currentBalance,
                    'required_amount' => $order->price,
                    'shortage' => $shortage,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'رصيد المحفظة غير كافي',
                    'data' => [
                        'current_balance' => $currentBalance,
                        'required_amount' => $order->price,
                        'shortage' => $shortage,
                        'action_required' => 'يرجى شحن المحفظة لمتابعة الإجابة',
                    ]
                ], 402); // 402 Payment Required
            }

            // تحديث حالة الطلبات المنتهية
            Order::updateExpiredOrders();
            $order->refresh();

            return response()->json([
                'success' => true,
                'message' => 'بيانات متابعة الإجابة',
                'data' => [
                    'wallet_balance' => $currentBalance,
                    'question' => [
                        'id' => $order->question->id,
                        'question' => $order->question->question,
                        'price' => $order->question->price,
                    ],
                    'order' => [
                        'id' => $order->id,
                        'status' => $order->status,
                        'price' => $order->price,
                        'response_time' => $order->response_time,
                        'expires_at' => \Carbon\Carbon::parse($order->expires_at)->format('Y-m-d H:i:s'),
                        'remaining_minutes' => $order->remaining_time,
                        'held_amount' => $order->held_amount,
                        'answer_text' => $order->answer_text,
                        'answer_image' => $order->answer_image ? Storage::url($order->answer_image) : null,
                        'answered_at' => $order->answered_at ? \Carbon\Carbon::parse($order->answered_at)->format('Y-m-d H:i:s') : null,
                    ],
                    'answerer' => [
                        'id' => $order->answerer->id,
                        'name' => $order->answerer->name,
                        'phone' => $order->answerer->phone,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في متابعة الإجابة', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء متابعة الإجابة',
            ], 500);
        }
    }
}
