<?php

// app/Http/Controllers/Api/Locations/QuestionController.php

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
use Carbon\Carbon;

class QuestionController extends Controller
{
    /**
     * عرض جميع أسئلة السائل (المستخدم الحالي)
     * ✅ للسائلين فقط
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

            $questions = $asker->questions()
                        ->with(['location', 'views'])
                        ->withCount('views')
                        ->orderBy('created_at', 'desc')
                        ->get();

            // تحديث الطلبات المنتهية
            Order::updateExpiredOrders();

            $questions = UserQuestion::with(['location', 'offers', 'offers.order'])
                ->where('user_id', $asker->id)
                ->withCount(['offers as pending_offers_count' => function ($query) {
                    $query->where('status', 'pending');
                }])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($question) {
                    // تحديد حالة السؤال
                    $acceptedOffer = $question->offers->firstWhere('status', 'accepted');
                    $order = $acceptedOffer ? $acceptedOffer->order : null;

                    if ($order) {
                        if ($order->status === 'answered') {
                            $questionStatus = 'answered'; // تم الرد
                        } elseif ($order->status === 'pending') {
                            $questionStatus = 'waiting_answer'; // في انتظار الرد
                        } elseif ($order->status === 'cancelled') {
                            $questionStatus = 'cancelled'; // ملغي
                        } elseif ($order->status === 'expired') {
                            $questionStatus = 'expired'; // منتهي
                        } else {
                            $questionStatus = 'unknown';
                        }
                    } elseif ($question->pending_offers_count > 0) {
                        $questionStatus = 'has_offers'; // يوجد عروض بانتظار القبول
                    } else {
                        $questionStatus = 'no_offers'; // لا يوجد عروض
                    }

                    return [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                        'is_active' => $question->is_active,
                        'status' => $questionStatus,
                        'views_count' => $question->views_count,
                        'pending_offers_count' => $question->pending_offers_count,
                        'location' => [
                            'id' => $question->location->id,
                            'title' => $question->location->title,
                            'address' => $question->location->address,
                        ],
                        'order' => $order ? [
                            'id' => $order->id,
                            'status' => $order->status,
                            'remaining_minutes' => $order->remaining_time,
                            'answer_text' => $order->answer_text,
                            'answer_image' => $order->answer_image ? Storage::url($order->answer_image) : null,
                        ] : null,
                        'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // إحصائيات
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
            Log::error('❌ خطأ في عرض أسئلة السائل', [
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
     * إضافة سؤال جديد
     * ✅ للسائلين فقط
     */
    /**
     * إضافة سؤال جديد
     * ✅ للسائلين فقط
     */
    public function store(Request $request): JsonResponse
    {
        // ✅ التحقق: المستخدم لازم يكون سائل
        if (!$request->user()->is_asker) {
            return response()->json([
                'success' => false,
                'message' => 'إضافة الأسئلة متاحة للسائلين فقط',
            ], 403);
        }

        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0|max:999999.99',

            // ✅ إما location_id أو بيانات الموقع كاملة
            'location_id' => 'nullable|exists:user_locations,id',
            'location' => 'nullable|array',
            'location.title' => 'required_with:location|string|max:255',
            'location.latitude' => 'required_with:location|numeric|between:-90,90',
            'location.longitude' => 'required_with:location|numeric|between:-180,180',
            'location.address' => 'nullable|string|max:500',
        ], [
            'question.required' => 'نص السؤال مطلوب',
            'question.max' => 'السؤال يجب ألا يتجاوز 1000 حرف',
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقم',
            'price.min' => 'السعر يجب أن يكون صفر أو أكثر',
            'location_id.exists' => 'الموقع المحدد غير موجود',
            'location.title.required_with' => 'عنوان الموقع مطلوب',
            'location.latitude.required_with' => 'خط العرض مطلوب',
            'location.longitude.required_with' => 'خط الطول مطلوب',
            'location.latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90',
            'location.longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180',
        ]);

        try {
            $user = $request->user();
            $selectedLocation = null;

            // ✅ الحالة 1: إذا تم إرسال location_id
            if (isset($validated['location_id'])) {
                $selectedLocation = $user->locations()->find($validated['location_id']);

                if (!$selectedLocation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الموقع المحدد لا ينتمي لحسابك',
                    ], 403);
                }
            }
            // ✅ الحالة 2: إذا تم إرسال بيانات الموقع الكاملة
            elseif (isset($validated['location'])) {
                $locationData = $validated['location'];

                // البحث عن موقع موجود بنفس الإحداثيات (تقريباً)
                $existingLocation = $user->locations()
                    ->whereBetween('latitude', [
                        $locationData['latitude'] - 0.0001,  // فرق حوالي 11 متر
                        $locationData['latitude'] + 0.0001
                    ])
                    ->whereBetween('longitude', [
                        $locationData['longitude'] - 0.0001,
                        $locationData['longitude'] + 0.0001
                    ])
                    ->first();

                if ($existingLocation) {
                    // ✅ الموقع موجود - استخدمه
                    $selectedLocation = $existingLocation;

                    Log::info('📍 Existing location found and used', [
                        'location_id' => $existingLocation->id,
                        'user_id' => $user->id
                    ]);
                } else {
                    // ✅ الموقع غير موجود - أنشئ موقع جديد
                    $selectedLocation = $user->locations()->create([
                        'title' => $locationData['title'],
                        'latitude' => $locationData['latitude'],
                        'longitude' => $locationData['longitude'],
                        'address' => $locationData['address'] ?? null,
                        'is_default' => $user->locations()->count() === 0, // افتراضي لو أول موقع
                    ]);

                    Log::info('📍 New location created', [
                        'location_id' => $selectedLocation->id,
                        'user_id' => $user->id,
                        'title' => $selectedLocation->title
                    ]);
                }
            }
            // ✅ الحالة 3: لم يتم إرسال أي بيانات موقع
            else {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إرسال location_id أو بيانات الموقع الكاملة',
                ], 400);
            }

            // ✅ إنشاء السؤال ونشره (is_active = true)
            $question = $user->questions()->create([
                'location_id' => $selectedLocation->id,
                'question' => $validated['question'],
                'price' => $validated['price'],
                'is_active' => true, // ✅ منشور مباشرة
            ]);

            Log::info('✅ Question added and published', [
                'asker_id' => $user->id,
                'question_id' => $question->id,
                'location_id' => $selectedLocation->id,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة السؤال ونشره بنجاح',
                'data' => [
                    'question' => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'price' => $question->price,
                        'is_active' => $question->is_active,
                        'views_count' => 0,
                        'location' => [
                            'id' => $selectedLocation->id,
                            'title' => $selectedLocation->title,
                            'latitude' => $selectedLocation->latitude,
                            'longitude' => $selectedLocation->longitude,
                            'address' => $selectedLocation->address,
                            'is_new' => !isset($validated['location_id']) && !isset($existingLocation), // ✅ علامة: هل موقع جديد؟
                        ],
                        'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('❌ Error adding question: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة السؤال',
            ], 500);
        }
    }
    // في QuestionController.php
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $viewer = $request->user();

            // ✅ البحث عن السؤال مع التحقق من وجود العلاقات
            $question = UserQuestion::with(['user', 'location'])
                ->withCount('views')
                ->find($id);

            // ✅ التحقق من وجود السؤال
            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'السؤال غير موجود',
                ], 404);
            }

            // ✅ التحقق من وجود المستخدم والموقع
            if (!$question->user || !$question->location) {
                Log::error('بيانات السؤال غير مكتملة', [
                    'question_id' => $id,
                    'has_user' => !is_null($question->user),
                    'has_location' => !is_null($question->location),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'بيانات السؤال غير مكتملة',
                ], 422);
            }

            $isNewView = false;

            // ✅ تسجيل المشاهدة فقط للمجيبين
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
                    Log::info('✅ تم تسجيل مشاهدة جديدة', [
                        'question_id' => $question->id,
                        'viewer_id' => $viewer->id,
                    ]);

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
                            'id' => $question->location->id,
                            'title' => $question->location->title,
                            'latitude' => $question->location->latitude ?? null,
                            'longitude' => $question->location->longitude ?? null,
                            'address' => $question->location->address ?? null,
                        ],
                        'created_at' => $question->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض السؤال', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'question_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض السؤال',
            ], 500);
        }
    }

    /**
     * تحديث سؤال (للسائل فقط)
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

            Log::info('✅ Question updated: ' . $question->id);

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
     * حذف سؤال (للسائل فقط)
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

            Log::info('✅ Question deleted: ' . $id);

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
     * حذف جميع الأسئلة (للسائل فقط)
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

            Log::info("✅ All questions deleted for asker: {$request->user()->id}, count: {$count}");

            return response()->json([
                'success' => true,
                'message' => "تم حذف جميع الأسئلة ({$count}) بنجاح",
                'deleted_count' => $count,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Error deleting all questions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الأسئلة',
            ], 500);
        }
    }

    /**
     * جلب أسئلة السائلين القريبين (للمجيبين فقط)
     * ✅ للمجيبين فقط
     */
    public function getNearbyQuestions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // ✅ التحقق: المستخدم لازم يكون مجيب
            if ($user->is_asker) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمجيبين فقط',
                ], 403);
            }

            // الحصول على الموقع الحالي
            $myLocation = $user->locations()->where('is_default', true)->first();

            if (!$myLocation) {
                $myLocation = $user->locations()->first();
            }

            if (!$myLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إضافة موقع أولاً',
                ], 400);
            }

            $maxDistance = 1; // كيلومتر واحد

            // البحث عن مواقع السائلين القريبين
            $nearbyLocations = UserLocation::with(['user' => function ($query) {
                $query->where('is_asker', true);  // السائلين فقط
            }, 'questions' => function ($query) {
                $query->where('is_active', true)->withCount('views');
            }])
                ->whereHas('user', function ($query) {
                    $query->where('is_asker', true);
                })
                ->where('user_id', '!=', $user->id)
                ->nearby($myLocation->latitude, $myLocation->longitude, $maxDistance)
                ->get();

            // جمع الأسئلة من المواقع القريبة
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
                        // ✅ التحقق: هل المجيب شاف السؤال قبل كده؟
                        $hasViewed = QuestionView::where('question_id', $question->id)
                            ->where('viewer_id', $user->id)
                            ->exists();

                        $nearbyQuestions->push([
                            'question_id' => $question->id,
                            'question' => $question->question,
                            'price' => $question->price,
                            'views_count' => $question->views_count,
                            'has_viewed' => $hasViewed,  // علامة: شفت السؤال قبل كده؟
                            'asker' => [
                                'id' => $location->user->id,
                                'name' => $location->user->name,
                                'phone' => $location->user->phone,
                            ],
                            'location' => [
                                'title' => $location->title,
                                'address' => $location->address,
                            ],
                            'distance_km' => round($distance, 3),
                            'distance_meters' => round($distance * 1000),
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
                        'address' => $myLocation->address,
                    ],
                    'max_distance_km' => $maxDistance,
                    'questions' => $nearbyQuestions,
                    'total' => $nearbyQuestions->count(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Error getting nearby questions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأسئلة القريبة',
            ], 500);
        }
    }

    /**
     * عرض مشاهدات سؤال معين (للسائل صاحب السؤال فقط)
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
                            'viewed_at' => \Carbon\Carbon::parse($view->view_at)->format('Y-m-d H:i:s'),
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
