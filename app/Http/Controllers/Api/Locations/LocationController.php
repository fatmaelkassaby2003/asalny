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
                ->orderBy('is_current', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'locations' => $locations->map(function($location) {
                        return [
                            'id' => $location->id,
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
                            'is_current' => $location->is_current,
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
                // إلغاء الموقع الحالي من المواقع الأخرى
                $user->locations()->update(['is_current' => false]);
                
                // تفعيل هذا الموقع كموقع حالي
                $existingLocation->update(['is_current' => true]);
                
                Log::info('✅ Existing location activated as current for user: ' . $user->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'تم تفعيل الموقع كموقع حالي',
                    'data' => [
                        'location' => [
                            'id' => $existingLocation->id,
                            'latitude' => $existingLocation->latitude,
                            'longitude' => $existingLocation->longitude,
                            'is_current' => true,
                            'is_existing' => true,  // علامة أن هذا موقع موجود مسبقاً
                            'created_at' => $existingLocation->created_at->format('Y-m-d H:i:s'),
                        ]
                    ]
                ], 200);
            }
            
            // ✅ إذا كان المستخدم يريد هذا الموقع حالي
            if ($request->is_current) {
                $user->locations()->update(['is_current' => false]);
            }
            
            // ✅ إذا لم يكن هناك مواقع أخرى، اجعل هذا الموقع الحالي
            $isFirstLocation = $user->locations()->count() === 0;

            // ✅ إنشاء الموقع الجديد
            $location = $user->locations()->create([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_current' => $request->is_current ?? $isFirstLocation,
            ]);

            Log::info('✅ New location added for user: ' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الموقع بنجاح',
                'data' => [
                    'location' => [
                        'id' => $location->id,
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'is_current' => $location->is_current,
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
     * تعيين موقع كموقع حالي
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function setDefault(Request $request): JsonResponse
    {
        try {
            $id = $request->input('id') ?? $request->input('location_id');
            
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف الموقع مطلوب في body (id أو location_id)',
                ], 422);
            }

            $user = $request->user();
            $location = $user->locations()->findOrFail($id);

            // إلغاء الموقع الحالي من جميع المواقع
            $user->locations()->update(['is_current' => false]);

            // تعيين الموقع الحالي
            $location->update(['is_current' => true]);

            Log::info('✅ Current location set: ' . $location->id);

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين الموقع كموقع حالي',
                'data' => [
                    'location' => [
                        'id' => $location->id,
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'is_current' => true,
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
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'is_current' => $location->is_current,
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

            // إذا كان هذا الموقع سيصبح موقع حالي، إلغاء الموقع الحالي من المواقع الأخرى
            if ($request->is_current) {
                $user->locations()->where('id', '!=', $id)->update(['is_current' => false]);
            }

            $location->update([
                'latitude' => $request->latitude ?? $location->latitude,
                'longitude' => $request->longitude ?? $location->longitude,
                'is_current' => $request->is_current ?? $location->is_current,
            ]);

            Log::info('✅ Location updated: ' . $location->id);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الموقع بنجاح',
                'data' => [
                    'location' => [
                        'id' => $location->id,
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'is_current' => $location->is_current,
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
            
            $wasCurrent = $location->is_current;
            $location->delete();

            // إذا كان الموقع المحذوف هو الموقع الحالي، اجعل أول موقع آخر موقع حالي
            if ($wasCurrent) {
                $firstLocation = $request->user()->locations()->first();
                if ($firstLocation) {
                    $firstLocation->update(['is_current' => true]);
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
     * جلب المستخدمين القريبين (Smart Method)
     * 
     * يعمل بطريقتين:
     * 1. إذا تم إرسال latitude و longitude → يستخدمهم
     * 2. إذا لم يتم الإرسال → يستخدم الموقع الحالي (is_current)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getNearbyUsers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // ✅ التحقق من البيانات (اختياري)
            $validated = $request->validate([
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'radius' => 'nullable|numeric|min:0.1|max:100', // من 100 متر إلى 100 كم
            ]);

            $maxDistance = $validated['radius'] ?? 1; // افتراضي 1 كم
            
            // ✅ حالة 1: إذا تم إرسال إحداثيات
            if ($request->has('latitude') && $request->has('longitude')) {
                $myLatitude = $validated['latitude'];
                $myLongitude = $validated['longitude'];
                $searchMethod = 'manual_coordinates';
                
                Log::info("🔍 Searching nearby users from manual coordinates", [
                    'user_id' => $user->id,
                    'latitude' => $myLatitude,
                    'longitude' => $myLongitude,
                    'radius' => $maxDistance
                ]);
            } 
            // ✅ حالة 2: استخدام الموقع الحالي
            else {
                $myLocation = $user->locations()->where('is_current', true)->first();
                
                // إذا لم يكن لديه موقع حالي، استخدم أول موقع
                if (!$myLocation) {
                    $myLocation = $user->locations()->first();
                }
                
                // إذا لم يكن لديه أي موقع
                if (!$myLocation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب إضافة موقع أو إرسال إحداثيات',
                    ], 400);
                }

                $myLatitude = $myLocation->latitude;
                $myLongitude = $myLocation->longitude;
                $searchMethod = 'saved_location';
                
                Log::info("🔍 Searching nearby users from saved location", [
                    'user_id' => $user->id,
                    'location_id' => $myLocation->id,
                    'latitude' => $myLatitude,
                    'longitude' => $myLongitude,
                    'radius' => $maxDistance
                ]);
            }

            // ✅ البحث عن جميع المواقع القريبة (ما عدا مواقع المستخدم نفسه)
            $nearbyLocations = UserLocation::with('user')
                ->where('user_id', '!=', $user->id)
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

                if ($distance <= $maxDistance) {
                    return [
                        'user_id' => $location->user->id,
                        'name' => $location->user->name,
                        'phone' => $location->user->phone,
                        'email' => $location->user->email,
                        'gender' => $location->user->gender,
                        'location' => [
                            'id' => $location->id,
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
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
                    'search_method' => $searchMethod, // manual_coordinates أو saved_location
                    'my_location' => [
                        'latitude' => $myLatitude,
                        'longitude' => $myLongitude,
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

}