<?php

namespace App\Http\Controllers\Api\Locations;

use App\Http\Controllers\Controller;
use App\Models\UserQuestion;
use App\Models\QuestionView;
use App\Models\Order;
use App\Models\UserLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    /**
     * عرض جميع أسئلة السائل مع حالاتها
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $asker = $request->user();

            if (!$asker->is_asker) {
                return response()->json([
                    'success' => false,
                    'message' => 'المجيبين ليس لديهم أسئلة',
                ], 403);
            }

            Order::updateExpiredOrders();

            $questions = UserQuestion::with(['location', 'offers', 'offers.order'])
                ->where('user_id', $asker->id)
                ->withCount(['offers as pending_offers_count' => function ($query) {
                    $query->where('status', 'pending');
                }])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($question) {
                    $acceptedOffer = $question->offers->firstWhere('status', 'accepted');
                    $order = $acceptedOffer ? $acceptedOffer->order : null;

                    if ($order) {
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
                    } elseif ($question->pending_offers_count > 0) {
                        $questionStatus = 'has_offers';
                    } else {
                        $questionStatus = 'no_offers';
                    }

                    return [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                        'is_active' => $question->is_active,
                        'status' => $questionStatus,
                        'views_count' => $question->views()->count(),
                        'pending_offers_count' => $question->pending_offers_count,
                        'location' => [
                            'latitude' => $question->location->latitude,
                            'longitude' => $question->location->longitude,
                        ],
                        'order' => $order ? [
                            'id' => $order->id,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'remaining_minutes' => $order->remaining_time,
                            // إظهار الإجابة فقط إذا تم الدفع
                            'answer_text' => $order->payment_status === 'paid' 
                                ? $order->answer_text 
                                : ($order->answer_text ? 'الإجابة جاهزة - يرجى الدفع لمشاهدتها' : null),
                            'answer_image' => $order->payment_status === 'paid' && $order->answer_image 
                                ? Storage::url($order->answer_image) 
                                : null,
                            'is_paid' => $order->payment_status === 'paid',
                        ] : null,
                        'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            $stats = [
                'total' => $questions->count(),
                'answered' => $questions->where('status', 'answered')->count(),
                'waiting_answer' => $questions->where('status', 'waiting_answer')->count(),
                'has_offers' => $questions->where('status', 'has_offers')->count(),
                'no_offers' => $questions->where('status', 'no_offers')->count(),
                'cancelled' => $questions->where('status', 'cancelled')->count(),
                'expired' => $questions->where('status', 'expired')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'questions' => $questions,
                    'stats' => $stats,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض الأسئلة', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض الأسئلة',
            ], 500);
        }
    }

    /**
     * إضافة سؤال مع الموقع
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->is_asker) {
            return response()->json([
                'success' => false,
                'message' => 'إضافة الأسئلة متاحة للسائلين فقط',
            ], 403);
        }

        // التحقق من وجود أسئلة تم الرد عليها ولم يتم الدفع لها
        $unpaidAnsweredOrders = Order::where('asker_id', $request->user()->id)
            ->where('status', 'answered')
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'paid');
            })
            ->exists();

        if ($unpaidAnsweredOrders) {
            return response()->json([
                'success' => false,
                'message' => 'لديك أسئلة تم الإجابة عليها ولم يتم الدفع لها بعد. يرجى الدفع أولاً قبل إضافة سؤال جديد.',
                'error_code' => 'UNPAID_ANSWERS_EXIST',
            ], 403);
        }

        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], [
            'question.required' => 'نص السؤال مطلوب',
            'price.required' => 'السعر مطلوب',
            'latitude.required' => 'خط العرض مطلوب',
            'longitude.required' => 'خط الطول مطلوب',
        ]);

        try {
            $user = $request->user();

            // البحث عن موقع موجود بنفس الإحداثيات (تقريباً)
            $existingLocation = $user->locations()
                ->whereBetween('latitude', [
                    $validated['latitude'] - 0.0001,
                    $validated['latitude'] + 0.0001
                ])
                ->whereBetween('longitude', [
                    $validated['longitude'] - 0.0001,
                    $validated['longitude'] + 0.0001
                ])
                ->first();

            if ($existingLocation) {
                $location = $existingLocation;
                // جعله الموقع الحالي
                $user->locations()->update(['is_current' => false]);
                $location->update(['is_current' => true]);
            } else {
                // إنشاء موقع جديد
                $user->locations()->update(['is_current' => false]);
                $location = $user->locations()->create([
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'is_current' => true,
                ]);
            }

            // إنشاء السؤال
            $question = $user->questions()->create([
                'location_id' => $location->id,
                'question' => $validated['question'],
                'price' => $validated['price'],
                'is_active' => true,
            ]);

            // ✅ إرسال إشعار للمجيبين القريبين
            $nearbyAnswerers = \App\Models\User::where('is_asker', false)
                ->whereNotNull('fcm_token')
                ->whereHas('locations', function ($query) use ($location) {
                    $query->selectRaw(
                        "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
                        [$location->latitude, $location->longitude, $location->latitude]
                    )->havingRaw('distance <= ?', [5]); // 5 كم
                })
                ->get()
                ->map(function ($answerer) use ($location) {
                    $answererLocation = $answerer->locations()->first();
                    if ($answererLocation) {
                        $distance = \App\Models\UserLocation::calculateDistance(
                            $answererLocation->latitude,
                            $answererLocation->longitude,
                            $location->latitude,
                            $location->longitude
                        );
                        $answerer->distance_km = round($distance, 2);
                    }
                    return $answerer;
                });

            if ($nearbyAnswerers->isNotEmpty()) {
                \App\Helpers\NotificationHelper::notifyNearbyAnswerers($question, $nearbyAnswerers);
            }

            Log::info('✅ سؤال تم إضافته', [
                'question_id' => $question->id,
                'user_id' => $user->id,
                'location_id' => $location->id,
                'notified_answerers' => $nearbyAnswerers->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة السؤال بنجاح',
                'data' => [
                    'question' => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                        'is_active' => $question->is_active,
                        'views_count' => 0,
                        'location' => [
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
                        ],
                        'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إضافة السؤال', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة السؤال',
            ], 500);
        }
    }

    /**
     * إضافة عدة أسئلة بنفس السعر والموقع (JSON Array)
     * السعر هو للمجموعة كلها، مش لكل سؤال
     */
    public function storeMultiple(Request $request): JsonResponse
    {
        if (!$request->user()->is_asker) {
            return response()->json([
                'success' => false,
                'message' => 'إضافة الأسئلة متاحة للسائلين فقط',
            ], 403);
        }

        // التحقق من وجود أسئلة تم الرد عليها ولم يتم الدفع لها
        $unpaidAnsweredOrders = Order::where('asker_id', $request->user()->id)
            ->where('status', 'answered')
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'paid');
            })
            ->exists();

        if ($unpaidAnsweredOrders) {
            return response()->json([
                'success' => false,
                'message' => 'لديك أسئلة تم الإجابة عليها ولم يتم الدفع لها بعد. يرجى الدفع أولاً قبل إضافة سؤال جديد.',
                'error_code' => 'UNPAID_ANSWERS_EXIST',
            ], 403);
        }

        $validated = $request->validate([
            'questions' => 'required|array|min:1|max:20',
            'questions.*' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $user = $request->user();

            // البحث عن الموقع أو إنشاء واحد جديد
            $existingLocation = $user->locations()
                ->whereBetween('latitude', [
                    $validated['latitude'] - 0.0001,
                    $validated['latitude'] + 0.0001
                ])
                ->whereBetween('longitude', [
                    $validated['longitude'] - 0.0001,
                    $validated['longitude'] + 0.0001
                ])
                ->first();

            if ($existingLocation) {
                $location = $existingLocation;
                $user->locations()->update(['is_current' => false]);
                $location->update(['is_current' => true]);
            } else {
                $user->locations()->update(['is_current' => false]);
                $location = $user->locations()->create([
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'is_current' => true,
                ]);
            }

            // ✅ حفظ الأسئلة كـ JSON في column واحد
            $question = $user->questions()->create([
                'location_id' => $location->id,
                'question' => json_encode($validated['questions'], JSON_UNESCAPED_UNICODE), // JSON
                'price' => $validated['price'], // السعر للمجموعة كلها
                'is_active' => true,
            ]);
            
            // فك JSON للعرض
            $questionsArray = json_decode($question->question, true);

            Log::info('✅ أسئلة متعددة تم إضافتها (JSON)', [
                'count' => count($questionsArray),
                'user_id' => $user->id,
                'question_id' => $question->id,
                'total_price' => $validated['price'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة ' . count($questionsArray) . ' أسئلة بنجاح',
                'data' => [
                    'question_id' => $question->id,
                    'questions' => $questionsArray,
                    'question_count' => count($questionsArray),
                    'location' => [
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                    ],
                    'total_price' => $validated['price'],
                    'price_per_question' => round($validated['price'] / count($questionsArray), 2),
                    'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إضافة الأسئلة', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الأسئلة',
            ], 500);
        }
    }

    /**
     * عرض سؤال معين (مع تسجيل المشاهدة للمجيب)
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $viewer = $request->user();

            $question = UserQuestion::with(['user', 'location'])
                ->withCount('views')
                ->find($id);

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'السؤال غير موجود',
                ], 404);
            }

            if (!$question->user || !$question->location) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات السؤال غير مكتملة',
                ], 422);
            }

            $isNewView = false;

            // تسجيل المشاهدة للمجيبين فقط
            if (!$viewer->is_asker && $viewer->id !== $question->user_id) {
                $view = QuestionView::firstOrCreate(
                    [
                        'question_id' => $question->id,
                        'viewer_id' => $viewer->id,
                    ],
                    [
                        'asker_id' => $question->user_id,
                        'viewed_at' => now(),
                    ]
                );

                $isNewView = $view->wasRecentlyCreated;

                if ($isNewView) {
                    $question->loadCount('views');
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'question' => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                        'is_active' => $question->is_active,
                        'views_count' => $question->views_count,
                        'is_new_view' => $isNewView,
                        'asker' => [
                            'id' => $question->user->id,
                            'name' => $question->user->name,
                            'phone' => $question->user->phone ?? null,
                        ],
                        'location' => [
                            'latitude' => $question->location->latitude,
                            'longitude' => $question->location->longitude,
                        ],
                        'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض السؤال', [
                'error' => $e->getMessage(),
                'question_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض السؤال',
            ], 500);
        }
    }

    /**
     * تحديث سؤال
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!$request->user()->is_asker) {
            return response()->json([
                'success' => false,
                'message' => 'تحديث الأسئلة متاح للسائلين فقط',
            ], 403);
        }

        $validated = $request->validate([
            'question' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $question = $request->user()->questions()->findOrFail($id);

            $question->update(array_filter([
                'question' => $validated['question'] ?? $question->question,
                'price' => $validated['price'] ?? $question->price,
                'is_active' => $validated['is_active'] ?? $question->is_active,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث السؤال بنجاح',
                'data' => [
                    'question' => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                        'is_active' => $question->is_active,
                        'updated_at' => $question->updated_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'السؤال غير موجود',
            ], 404);
        }
    }

    /**
     * حذف سؤال
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        if (!$request->user()->is_asker) {
            return response()->json([
                'success' => false,
                'message' => 'حذف الأسئلة متاح للسائلين فقط',
            ], 403);
        }

        try {
            $question = $request->user()->questions()->findOrFail($id);
            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السؤال بنجاح',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'السؤال غير موجود',
            ], 404);
        }
    }

    /**
     * حذف جميع الأسئلة
     */
    public function destroyAll(Request $request): JsonResponse
    {
        if (!$request->user()->is_asker) {
            return response()->json([
                'success' => false,
                'message' => 'حذف الأسئلة متاح للسائلين فقط',
            ], 403);
        }

        try {
            $count = $request->user()->questions()->count();
            $request->user()->questions()->delete();

            return response()->json([
                'success' => true,
                'message' => "تم حذف جميع الأسئلة ({$count}) بنجاح",
                'deleted_count' => $count,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الأسئلة',
            ], 500);
        }
    }

    /**
     * الأسئلة القريبة (للمجيبين)
     */
    public function getNearbyQuestions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->is_asker) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمجيبين فقط',
                ], 403);
            }

            // الموقع الحالي للمجيب
            $myLocation = $user->locations()->where('is_current', true)->first();

            if (!$myLocation) {
                $myLocation = $user->locations()->latest()->first();
            }

            if (!$myLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إضافة موقع أولاً',
                ], 400);
            }

            $maxDistance = 1; // كيلومتر

            // البحث عن أسئلة قريبة
            $nearbyLocations = UserLocation::with(['user' => function ($query) {
                $query->where('is_asker', true);
            }, 'questions' => function ($query) {
                $query->where('is_active', true)->withCount('views');
            }])
                ->whereHas('user', function ($query) {
                    $query->where('is_asker', true);
                })
                ->where('user_id', '!=', $user->id)
                ->nearby($myLocation->latitude, $myLocation->longitude, $maxDistance)
                ->get();

            $nearbyQuestions = collect();

            foreach ($nearbyLocations as $location) {
                $distance = UserLocation::calculateDistance(
                    $myLocation->latitude,
                    $myLocation->longitude,
                    $location->latitude,
                    $location->longitude
                );

                if ($distance <= $maxDistance && $location->questions->isNotEmpty()) {
                    foreach ($location->questions as $question) {
                        $hasViewed = QuestionView::where('question_id', $question->id)
                            ->where('viewer_id', $user->id)
                            ->exists();

                        $nearbyQuestions->push([
                            'id' => $question->id,
                            'question' => $question->question,
                            'price' => $question->price,
                            'views_count' => $question->views_count,
                            'has_viewed' => $hasViewed,
                            'asker' => [
                                'id' => $location->user->id,
                                'name' => $location->user->name,
                                'phone' => $location->user->phone,
                            ],
                            'location' => [
                                'latitude' => $location->latitude,
                                'longitude' => $location->longitude,
                            ],
                            'distance_km' => round($distance, 3),
                            'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            $nearbyQuestions = $nearbyQuestions->sortBy('distance_km')->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'my_location' => [
                        'latitude' => $myLocation->latitude,
                        'longitude' => $myLocation->longitude,
                    ],
                    'max_distance_km' => $maxDistance,
                    'questions' => $nearbyQuestions,
                    'total' => $nearbyQuestions->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في الأسئلة القريبة', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأسئلة القريبة',
            ], 500);
        }
    }

    /**
     * مشاهدات سؤال معين
     */
    public function getViews(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->is_asker) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للسائلين فقط',
                ], 403);
            }

            $question = $user->questions()
                ->with(['views.viewer'])
                ->withCount('views')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'question' => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                        'views_count' => $question->views_count,
                    ],
                    'views' => $question->views->map(function ($view) {
                        return [
                            'viewer' => [
                                'id' => $view->viewer->id,
                                'name' => $view->viewer->name,
                                'phone' => $view->viewer->phone,
                            ],
                            'viewed_at' => \Carbon\Carbon::parse($view->viewed_at)->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'total_views' => $question->views_count,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'السؤال غير موجود',
            ], 404);
        }
    }
}