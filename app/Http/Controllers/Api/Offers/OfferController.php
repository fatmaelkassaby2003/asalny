<?php

namespace App\Http\Controllers\Api\Offers;

use App\Http\Controllers\Controller;
use App\Models\QuestionOffer;
use App\Models\UserQuestion;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OfferController extends Controller
{
    /**
     * إضافة عرض على سؤال (للمجيبين فقط)
     */
public function store(Request $request): JsonResponse
{
    try {
        $answerer = $request->user();

        if ($answerer->is_asker) {
            return response()->json([
                'success' => false,
                'message' => 'السائلين غير مصرح لهم بإضافة عروض',
            ], 403);
        }

        // ✅ قراءة question_id من body
        $questionId = $request->input('question_id');
        
        if (!$questionId) {
            return response()->json([
                'success' => false,
                'message' => 'معرف السؤال مطلوب في body (question_id)',
            ], 422);
        }

        $question = UserQuestion::with('location')->find($questionId);
        
        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'السؤال غير موجود',
            ], 404);
        }

        if (!$question->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'السؤال غير نشط',
            ], 400);
        }

        if ($answerer->id === $question->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة عرض على سؤالك الخاص',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
            'response_time' => 'required|integer|min:1',
            'note' => 'nullable|string|max:500',
            
            // ✅ بيانات الموقع - إجباري
            'location_id' => 'required_without:location|exists:user_locations,id',
            'location' => 'required_without:location_id|array',
            'location.latitude' => 'required_with:location|numeric|between:-90,90',
            'location.longitude' => 'required_with:location|numeric|between:-180,180',
            'location.is_current' => 'nullable|boolean',
        ], [
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقم',
            'price.min' => 'السعر لا يمكن أن يكون سالب',
            'response_time.required' => 'مدة الرد مطلوبة',
            'response_time.integer' => 'مدة الرد يجب أن تكون رقم صحيح',
            'response_time.min' => 'مدة الرد يجب أن تكون دقيقة على الأقل',
            'note.max' => 'الملاحظة لا يمكن أن تتجاوز 500 حرف',
            'location_id.required_without' => 'يجب إرسال location_id أو بيانات الموقع',
            'location_id.exists' => 'الموقع المحدد غير موجود',
            'location.required_without' => 'يجب إرسال بيانات الموقع أو location_id',
            'location.latitude.required_with' => 'خط العرض مطلوب',
            'location.longitude.required_with' => 'خط الطول مطلوب',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $existingOffer = QuestionOffer::where('question_id', $questionId)
            ->where('answerer_id', $answerer->id)
            ->first();

        if ($existingOffer) {
            return response()->json([
                'success' => false,
                'message' => 'لديك عرض سابق على هذا السؤال',
                'data' => [
                    'existing_offer' => [
                        'id' => $existingOffer->id,
                        'price' => $existingOffer->price,
                        'response_time' => $existingOffer->response_time,
                        'status' => $existingOffer->status,
                    ]
                ]
            ], 400);
        }

        // ✅ معالجة الموقع
        $selectedLocation = null;

        if (isset($validator->validated()['location_id'])) {
            $selectedLocation = $answerer->locations()->find($validator->validated()['location_id']);

            if (!$selectedLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'الموقع المحدد لا ينتمي لحسابك',
                ], 403);
            }
        } elseif (isset($validator->validated()['location'])) {
            $locationData = $validator->validated()['location'];

            // البحث عن موقع موجود
            $existingLocation = $answerer->locations()
                ->whereBetween('latitude', [
                    $locationData['latitude'] - 0.0001,
                    $locationData['latitude'] + 0.0001
                ])
                ->whereBetween('longitude', [
                    $locationData['longitude'] - 0.0001,
                    $locationData['longitude'] + 0.0001
                ])
                ->first();

            if ($existingLocation) {
                $selectedLocation = $existingLocation;
            } else {
                $selectedLocation = $answerer->locations()->create([
                    'latitude' => $locationData['latitude'],
                    'longitude' => $locationData['longitude'],
                    'is_current' => $locationData['is_current'] ?? ($answerer->locations()->count() === 0),
                ]);
            }
        }

        // ✅ التحقق من المسافة بين المجيب والسؤال (max 1km)
        $questionLocation = $question->location;
        
        if (!$questionLocation) {
            return response()->json([
                'success' => false,
                'message' => 'السؤال ليس له موقع محدد',
            ], 400);
        }

        $distance = \App\Models\UserLocation::calculateDistance(
            $selectedLocation->latitude,
            $selectedLocation->longitude,
            $questionLocation->latitude,
            $questionLocation->longitude
        );

        if ($distance > 1) {
            return response()->json([
                'success' => false,
                'message' => 'يجب أن تكون على مسافة أقل من 1 كم من موقع السؤال',
                'data' => [
                    'distance' => round($distance, 2) . ' كم',
                    'max_allowed' => '1 كم',
                    'question_location' => [
                        'latitude' => $questionLocation->latitude,
                        'longitude' => $questionLocation->longitude,
                    ],
                    'your_location' => [
                        'latitude' => $selectedLocation->latitude,
                        'longitude' => $selectedLocation->longitude,
                    ]
                ]
            ], 400);
        }

        // ✅ إنشاء العرض مع الموقع
        $offer = QuestionOffer::create([
            'question_id' => $questionId,
            'answerer_id' => $answerer->id,
            'asker_id' => $question->user_id,
            'location_id' => $selectedLocation->id, // ✅ إضافة location_id
            'price' => $request->price,
            'response_time' => $request->response_time,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        Log::info('✅ Offer created with location', [
            'offer_id' => $offer->id,
            'question_id' => $questionId,
            'answerer_id' => $answerer->id,
            'location_id' => $selectedLocation->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة العرض بنجاح',
            'data' => [
                'offer' => [
                    'id' => $offer->id,
                    'price' => $offer->price,
                    'response_time' => $offer->response_time,
                    'note' => $offer->note,
                    'status' => $offer->status,
                    'location' => [
                        'id' => $selectedLocation->id,
                        'latitude' => $selectedLocation->latitude,
                        'longitude' => $selectedLocation->longitude,
                        'is_current' => $selectedLocation->is_current,
                    ],
                    'created_at' => $offer->created_at->format('Y-m-d H:i:s'),
                ]
            ]
        ], 201);

    } catch (\Exception $e) {
        Log::error('❌ Error creating offer', [
            'error' => $e->getMessage(),
            'question_id' => $questionId,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إضافة العرض',
        ], 500);
    }
}

    /**
     * عرض تفاصيل عرض معين (للسائل)
     */
    public function show(Request $request, $offerId): JsonResponse
    {
        try {
            $user = $request->user();

            $offer = QuestionOffer::with(['question', 'answerer', 'answerer.locations'])
                ->find($offerId);

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'العرض غير موجود',
                ], 404);
            }

            if ($offer->asker_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بعرض هذا العرض',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'offer' => [
                        'id' => $offer->id,
                        'price' => $offer->price,
                        'response_time' => $offer->response_time,
                        'note' => $offer->note,
                        'status' => $offer->status,
                        'created_at' => $offer->created_at->format('Y-m-d H:i:s'),
                        'question' => [
                            'id' => $offer->question->id,
                            'question' => $offer->question->question,
                            'price' => $offer->question->price,
                        ],
                        'answerer' => [
                            'id' => $offer->answerer->id,
                            'name' => $offer->answerer->name,
                            'phone' => $offer->answerer->phone,
                            'locations' => $offer->answerer->locations->map(function ($location) {
                                return [
                                    'id' => $location->id,
                                    'title' => $location->title,
                                    'address' => $location->address,
                                    'is_current' => $location->is_current,
                                ];
                            }),
                        ],
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض تفاصيل العرض', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض العرض',
            ], 500);
        }
    }

    /**
     * عرض جميع العروض على سؤال معين (للسائل فقط)
     * مع تفاصيل كاملة عن المجيبين
     */
    public function getQuestionOffers(Request $request, $questionId): JsonResponse
    {
        try {
            $user = $request->user();

            $question = UserQuestion::find($questionId);
            
            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'السؤال غير موجود',
                ], 404);
            }

            if ($question->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بعرض عروض هذا السؤال',
                ], 403);
            }

            $offers = QuestionOffer::with(['answerer', 'location'])
                ->where('question_id', $questionId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'question' => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                    ],
                    'offers' => $offers->map(function ($offer) {
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
                                'email' => $offer->answerer->email,
                                'gender' => $offer->answerer->gender,
                                'description' => $offer->answerer->description,
                                'is_active' => $offer->answerer->is_active,
                            ],
                            'location' => $offer->location ? [
                                'id' => $offer->location->id,
                                'latitude' => $offer->location->latitude,
                                'longitude' => $offer->location->longitude,
                                'is_current' => $offer->location->is_current,
                            ] : null,
                            'created_at' => $offer->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'total' => $offers->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض العروض', [
                'error' => $e->getMessage(),
                'question_id' => $questionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض العروض',
            ], 500);
        }
    }

    /**
     * عرض عروض المجيب (عروضي)
     */
    public function myOffers(Request $request): JsonResponse
    {
        try {
            $answerer = $request->user();

            if ($answerer->is_asker) {
                return response()->json([
                    'success' => false,
                    'message' => 'السائلين ليس لديهم عروض',
                ], 403);
            }

            $offers = QuestionOffer::with(['question', 'asker'])
                ->where('answerer_id', $answerer->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'offers' => $offers->map(function ($offer) {
                        return [
                            'id' => $offer->id,
                            'price' => $offer->price,
                            'response_time' => $offer->response_time,
                            'note' => $offer->note,
                            'status' => $offer->status,
                            'question' => [
                                'id' => $offer->question->id,
                                'question' => $offer->question->question,
                                'price' => $offer->question->price,
                            ],
                            'asker' => [
                                'id' => $offer->asker->id,
                                'name' => $offer->asker->name,
                            ],
                            'created_at' => $offer->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'total' => $offers->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض عروضي', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض عروضك',
            ], 500);
        }
    }

    /**
     * تحديث عرض (للمجيب فقط)
     */
    public function update(Request $request, $offerId): JsonResponse
    {
        try {
            $answerer = $request->user();

            $offer = QuestionOffer::find($offerId);

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'العرض غير موجود',
                ], 404);
            }

            if ($offer->answerer_id !== $answerer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا العرض',
                ], 403);
            }

            if (in_array($offer->status, ['accepted', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل عرض ' . ($offer->status === 'accepted' ? 'مقبول' : 'مرفوض'),
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'price' => 'sometimes|numeric|min:0',
                'response_time' => 'sometimes|integer|min:1',
                'note' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $offer->update($request->only(['price', 'response_time', 'note']));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث العرض بنجاح',
                'data' => [
                    'offer' => [
                        'id' => $offer->id,
                        'price' => $offer->price,
                        'response_time' => $offer->response_time,
                        'note' => $offer->note,
                        'status' => $offer->status,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في تحديث العرض', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث العرض',
            ], 500);
        }
    }

    /**
     * حذف عرض (للمجيب فقط)
     */
    public function destroy(Request $request, $offerId): JsonResponse
    {
        try {
            $answerer = $request->user();

            $offer = QuestionOffer::find($offerId);

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'العرض غير موجود',
                ], 404);
            }

            if ($offer->answerer_id !== $answerer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذا العرض',
                ], 403);
            }

            if ($offer->status === 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف عرض مقبول',
                ], 400);
            }

            $offer->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العرض بنجاح',
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في حذف العرض', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف العرض',
            ], 500);
        }
    }

    /**
     * معالجة عرض (قبول أو رفض)
     */
    public function handleOffer(Request $request): JsonResponse
    {
        try {
            $asker = $request->user();

            // ✅ قراءة البيانات من body
            $offerId = $request->input('offer_id');
            $action = $request->input('action'); // accept or reject

            if (!$offerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف العرض مطلوب في body (offer_id)',
                ], 422);
            }

            if (!$action || !in_array($action, ['accept', 'reject'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع الإجراء مطلوب (action: accept أو reject)',
                ], 422);
            }

            $offer = QuestionOffer::with(['question', 'answerer'])->find($offerId);

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'العرض غير موجود',
                ], 404);
            }

            if ($offer->asker_id !== $asker->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بمعالجة هذا العرض',
                ], 403);
            }

            if ($offer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'العرض تم معالجته بالفعل',
                ], 400);
            }

            if ($action === 'accept') {
                // ✅ قبول العرض
                $offer->accept();

                // ✅ إنشاء طلب (Order)
                $expiresAt = Carbon::now()->addMinutes($offer->response_time);
                
                $order = Order::create([
                    'question_id' => $offer->question_id,
                    'offer_id' => $offer->id,
                    'asker_id' => $asker->id,
                    'answerer_id' => $offer->answerer_id,
                    'price' => $offer->price,
                    'response_time' => $offer->response_time,
                    'status' => 'pending',
                    'expires_at' => $expiresAt,
                ]);

                Log::info('✅ تم قبول عرض وإنشاء طلب', [
                    'offer_id' => $offer->id,
                    'order_id' => $order->id,
                    'expires_at' => $expiresAt,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم قبول العرض وجدَّد الطلب بنجاح',
                    'data' => [
                        'order' => [
                            'id' => $order->id,
                            'status' => $order->status,
                            'price' => $order->price,
                            'held_amount' => $order->held_amount,
                            'response_time' => $order->response_time,
                            'expires_at' => $order->expires_at->format('Y-m-d H:i:s'),
                            'remaining_minutes' => $order->remaining_time,
                        ],
                        'answerer' => [
                            'id' => $offer->answerer->id,
                            'name' => $offer->answerer->name,
                            'phone' => $offer->answerer->phone,
                        ]
                    ]
                ], 200);
            } else {
                // رفض العرض وحذفه من قاعدة البيانات
                $offerId = $offer->id;
                $offer->delete();

                Log::info('✅ تم رفض وحذف العرض', [
                    'offer_id' => $offerId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم رفض وحذف العرض',
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('❌ خطأ في معالجة العرض', [
                'error' => $e->getMessage(),
                'offer_id' => $request->input('offer_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة العرض',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}