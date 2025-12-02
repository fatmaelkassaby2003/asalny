<?php

// app/Http/Controllers/Api/LocationController.php

namespace App\Http\Controllers\Api\Locations;

use App\Http\Controllers\Controller;
use App\Http\Requests\LocationRequest;
use App\Http\Requests\SearchLocationRequest;
use App\Models\UserLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * عرض جميع مواقع المستخدم
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $locations = $request->user()->locations()
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'locations' => $locations->map(function($location) {
                        return [
                            'id' => $location->id,
                            'title' => $location->title,
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
                            'address' => $location->address,
                            'is_default' => $location->is_default,
                            'created_at' => $location->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'total' => $locations->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching locations: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المواقع',
            ], 500);
        }
    }

    /**
     * إضافة موقع جديد
     * 
     * @param LocationRequest $request
     * @return JsonResponse
     */
   public function store(LocationRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // ✅ البحث عن موقع موجود بنفس الإحداثيات (تقريباً)
            $existingLocation = $user->locations()
                ->whereBetween('latitude', [
                    $request->latitude - 0.0001,  // فرق صغير جداً (حوالي 11 متر)
                    $request->latitude + 0.0001
                ])
                ->whereBetween('longitude', [
                    $request->longitude - 0.0001,
                    $request->longitude + 0.0001
                ])
                ->first();

            // ✅ إذا الموقع موجود من قبل
            if ($existingLocation) {
                // إلغاء الافتراضية من المواقع الأخرى
                $user->locations()->update(['is_default' => false]);
                
                // تفعيل هذا الموقع كموقع حالي
                $existingLocation->update(['is_default' => true]);
                
                Log::info('✅ Existing location activated as current for user: ' . $user->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'تم تفعيل الموقع كموقع حالي',
                    'data' => [
                        'location' => [
                            'id' => $existingLocation->id,
                            'title' => $existingLocation->title,
                            'latitude' => $existingLocation->latitude,
                            'longitude' => $existingLocation->longitude,
                            'address' => $existingLocation->address,
                            'is_default' => true,
                            'is_existing' => true,  // علامة أن هذا موقع موجود مسبقاً
                            'created_at' => $existingLocation->created_at->format('Y-m-d H:i:s'),
                        ]
                    ]
                ], 200);
            }
            
            // ✅ إذا كان المستخدم يريد هذا الموقع افتراضي
            if ($request->is_default) {
                $user->locations()->update(['is_default' => false]);
            }
            
            // ✅ إذا لم يكن هناك مواقع أخرى، اجعل هذا افتراضي
            $isFirstLocation = $user->locations()->count() === 0;

            // ✅ إنشاء الموقع الجديد
            $location = $user->locations()->create([
                'title' => $request->title,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'address' => $request->address,
                'is_default' => $request->is_default ?? $isFirstLocation,
            ]);

            Log::info('✅ New location added for user: ' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الموقع بنجاح',
                'data' => [
                    'location' => [
                        'id' => $location->id,
                        'title' => $location->title,
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'address' => $location->address,
                        'is_default' => $location->is_default,
                        'is_existing' => false,  // موقع جديد
                        'created_at' => $location->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error adding location: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الموقع',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * تعيين موقع كافتراضي
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function setDefault(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $location = $user->locations()->findOrFail($id);

            // إلغاء الافتراضية من جميع المواقع
            $user->locations()->update(['is_default' => false]);

            // تعيين الموقع الحالي كافتراضي
            $location->update(['is_default' => true]);

            Log::info('✅ Current location set: ' . $location->id);

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين الموقع كموقع حالي',
                'data' => [
                    'location' => [
                        'id' => $location->id,
                        'title' => $location->title,
                        'is_default' => true,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع غير موجود',
            ], 404);
        }
    }

    /**
     * عرض موقع معين
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $location = $request->user()->locations()->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'location' => [
                        'id' => $location->id,
                        'title' => $location->title,
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'address' => $location->address,
                        'is_default' => $location->is_default,
                        'created_at' => $location->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع غير موجود',
            ], 404);
        }
    }

    /**
     * تحديث موقع
     * 
     * @param LocationRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(LocationRequest $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $location = $user->locations()->findOrFail($id);

            // إذا كان هذا الموقع سيصبح افتراضي، إلغاء الافتراضية من المواقع الأخرى
            if ($request->is_default) {
                $user->locations()->where('id', '!=', $id)->update(['is_default' => false]);
            }

            $location->update([
                'title' => $request->title ?? $location->title,
                'latitude' => $request->latitude ?? $location->latitude,
                'longitude' => $request->longitude ?? $location->longitude,
                'address' => $request->address ?? $location->address,
                'is_default' => $request->is_default ?? $location->is_default,
            ]);

            Log::info('✅ Location updated: ' . $location->id);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الموقع بنجاح',
                'data' => [
                    'location' => [
                        'id' => $location->id,
                        'title' => $location->title,
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'address' => $location->address,
                        'is_default' => $location->is_default,
                        'updated_at' => $location->updated_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع غير موجود',
            ], 404);
        }
    }

    /**
     * حذف موقع
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $location = $request->user()->locations()->findOrFail($id);
            
            $wasDefault = $location->is_default;
            $location->delete();

            // إذا كان الموقع المحذوف افتراضي، اجعل أول موقع آخر افتراضي
            if ($wasDefault) {
                $firstLocation = $request->user()->locations()->first();
                if ($firstLocation) {
                    $firstLocation->update(['is_default' => true]);
                }
            }

            Log::info('✅ Location deleted: ' . $id);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الموقع بنجاح',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع غير موجود',
            ], 404);
        }
    }

    /**
     * البحث عن المواقع القريبة
     * 
     * @param SearchLocationRequest $request
     * @return JsonResponse
     */
    public function searchNearby(SearchLocationRequest $request): JsonResponse
    {
        try {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radius = $request->radius ?? 10; // افتراضي 10 كم

            Log::info("🔍 Searching locations near: lat={$latitude}, lon={$longitude}, radius={$radius}km");

            // البحث في قاعدة البيانات
            $locations = UserLocation::with('user')
                ->nearby($latitude, $longitude, $radius)
                ->get();

            // حساب المسافة الدقيقة لكل موقع
            $results = $locations->map(function($location) use ($latitude, $longitude) {
                $distance = UserLocation::calculateDistance(
                    $latitude, 
                    $longitude, 
                    $location->latitude, 
                    $location->longitude
                );

                return [
                    'id' => $location->id,
                    'user' => [
                        'id' => $location->user->id,
                        'name' => $location->user->name,
                        'phone' => $location->user->phone,
                    ],
                    'title' => $location->title,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'address' => $location->address,
                    'distance_km' => round($distance, 2),
                ];
            })
            ->filter(function($item) use ($radius) {
                // تصفية حسب المسافة الفعلية
                return $item['distance_km'] <= $radius;
            })
            ->sortBy('distance_km')
            ->values();

            Log::info("✅ Found {$results->count()} locations within {$radius}km");

            return response()->json([
                'success' => true,
                'data' => [
                    'search_location' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'radius_km' => $radius,
                    ],
                    'locations' => $results,
                    'total' => $results->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error searching locations: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث',
            ], 500);
        }
    }

    /**
     * جلب المستخدمين القريبين من موقع المستخدم الحالي
     * المسافة القصوى: 1 كيلومتر
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getNearbyUsers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // ✅ الحصول على الموقع الافتراضي للمستخدم الحالي
            $myLocation = $user->locations()->where('is_default', true)->first();
            
            // ✅ إذا لم يكن لديه موقع افتراضي، استخدم أول موقع
            if (!$myLocation) {
                $myLocation = $user->locations()->first();
            }
            
            // ✅ إذا لم يكن لديه أي موقع
            if (!$myLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إضافة موقع أولاً',
                ], 400);
            }

            $myLatitude = $myLocation->latitude;
            $myLongitude = $myLocation->longitude;
            $maxDistance = 1; // كيلومتر واحد فقط

            Log::info("🔍 Searching nearby users from location", [
                'user_id' => $user->id,
                'latitude' => $myLatitude,
                'longitude' => $myLongitude,
                'max_distance' => $maxDistance
            ]);

            // ✅ البحث عن جميع المواقع القريبة (ما عدا مواقع المستخدم نفسه)
            $nearbyLocations = UserLocation::with('user')
                ->where('user_id', '!=', $user->id)  // استبعاد المستخدم الحالي
                ->nearby($myLatitude, $myLongitude, $maxDistance)
                ->get();

            // ✅ حساب المسافة الدقيقة وتصفية النتائج
            $nearbyUsers = $nearbyLocations->map(function($location) use ($myLatitude, $myLongitude, $maxDistance) {
                $distance = UserLocation::calculateDistance(
                    $myLatitude, 
                    $myLongitude, 
                    $location->latitude, 
                    $location->longitude
                );

                // إرجاع البيانات فقط إذا كانت المسافة ≤ 1 كم
                if ($distance <= $maxDistance) {
                    return [
                        'user_id' => $location->user->id,
                        'name' => $location->user->name,
                        'phone' => $location->user->phone,
                        'email' => $location->user->email,
                        'gender' => $location->user->gender,
                        'location' => [
                            'id' => $location->id,
                            'title' => $location->title,
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
                            'address' => $location->address,
                        ],
                        'distance_km' => round($distance, 3),  // 3 أرقام عشرية للدقة
                        'distance_meters' => round($distance * 1000),  // بالمتر
                    ];
                }
                return null;
            })
            ->filter()  // إزالة القيم null
            ->sortBy('distance_km')  // ترتيب من الأقرب للأبعد
            ->values()  // إعادة ترقيم المصفوفة
            ->unique('user_id');  // إزالة التكرار (لو المستخدم عنده أكثر من موقع)

            Log::info("✅ Found {$nearbyUsers->count()} nearby users within {$maxDistance}km");

            return response()->json([
                'success' => true,
                'data' => [
                    'my_location' => [
                        'latitude' => $myLatitude,
                        'longitude' => $myLongitude,
                        'address' => $myLocation->address,
                    ],
                    'max_distance_km' => $maxDistance,
                    'nearby_users' => $nearbyUsers,
                    'total' => $nearbyUsers->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error getting nearby users: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن المستخدمين القريبين',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * جلب المستخدمين القريبين بناءً على إحداثيات محددة
     * (اختياري - إذا كنتي عايزة ترسلي lat & lng من الموبايل مباشرة)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getNearbyUsersByCoordinates(Request $request): JsonResponse
    {
        // التحقق من البيانات
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $user = $request->user();
            $latitude = $validated['latitude'];
            $longitude = $validated['longitude'];
            $maxDistance = 1; // كيلومتر واحد

            Log::info("🔍 Searching nearby users from coordinates", [
                'user_id' => $user->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            // البحث عن المواقع القريبة
            $nearbyLocations = UserLocation::with('user')
                ->where('user_id', '!=', $user->id)
                ->nearby($latitude, $longitude, $maxDistance)
                ->get();

            // حساب المسافة وتصفية النتائج
            $nearbyUsers = $nearbyLocations->map(function($location) use ($latitude, $longitude, $maxDistance) {
                $distance = UserLocation::calculateDistance(
                    $latitude, 
                    $longitude, 
                    $location->latitude, 
                    $location->longitude
                );

                if ($distance <= $maxDistance) {
                    return [
                        'user_id' => $location->user->id,
                        'name' => $location->user->name,
                        'phone' => $location->user->phone,
                        'email' => $location->user->email,
                        'gender' => $location->user->gender,
                        'location' => [
                            'id' => $location->id,
                            'title' => $location->title,
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
                            'address' => $location->address,
                        ],
                        'distance_km' => round($distance, 3),
                        'distance_meters' => round($distance * 1000),
                    ];
                }
                return null;
            })
            ->filter()
            ->sortBy('distance_km')
            ->values()
            ->unique('user_id');

            Log::info("✅ Found {$nearbyUsers->count()} nearby users within {$maxDistance}km");

            return response()->json([
                'success' => true,
                'data' => [
                    'search_location' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ],
                    'max_distance_km' => $maxDistance,
                    'nearby_users' => $nearbyUsers,
                    'total' => $nearbyUsers->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error getting nearby users: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن المستخدمين القريبين',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

}