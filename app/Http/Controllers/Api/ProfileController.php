<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * عرض بروفايل المستخدم الحالي
     */
    public function getMyProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $profileData = [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'gender' => $user->gender,
                'is_asker' => $user->is_asker,
                'description' => $user->description,
                'profile_image' => $user->profile_image ? url($user->profile_image) : null,
                'wallet_balance' => $user->wallet_balance,
                'created_at' => $user->created_at->format('Y-m-d'),
            ];

            if ($user->is_asker) {
                // إحصائيات السائل
                $profileData['stats'] = [
                    'total_questions' => $user->questions()->count(),
                    'total_orders' => $user->ordersAsAsker()->count(),
                    'completed_orders' => $user->ordersAsAsker()->where('status', 'completed')->count(),
                    'disputed_orders' => $user->ordersAsAsker()->where('status', 'disputed')->count(),
                    'ratings_given' => $user->givenRatings()->count(),
                ];
            } else {
                // إحصائيات المجيب
                $profileData['rating'] = [
                    'average' => $user->average_rating,
                    'count' => $user->ratings_count,
                ];
                $profileData['stats'] = [
                    'total_orders' => $user->ordersAsAnswerer()->count(),
                    'completed_orders' => $user->ordersAsAnswerer()->where('status', 'completed')->count(),
                    'pending_orders' => $user->ordersAsAnswerer()->where('status', 'pending')->count(),
                    'answered_orders' => $user->ordersAsAnswerer()->where('status', 'answered')->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $profileData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض البروفايل', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض البروفايل',
            ], 500);
        }
    }

    /**
     * تحديث بروفايل المستخدم
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string|max:500',
                'gender' => 'sometimes|in:male,female',
                'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            ], [
                'name.max' => 'الاسم لا يمكن أن يتجاوز 255 حرف',
                'description.max' => 'الوصف لا يمكن أن يتجاوز 500 حرف',
                'profile_image.image' => 'يجب أن يكون الملف صورة',
                'profile_image.mimes' => 'الصورة يجب أن تكون jpeg, png, jpg, أو gif',
                'profile_image.max' => 'حجم الصورة لا يمكن أن يتجاوز 5 ميجا',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $dataToUpdate = $request->only(['name', 'description', 'gender']);

            // ✅ رفع صورة البروفايل للـ public
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $imageName = 'profile_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
                
                // حفظ في public/uploads/profiles
                $image->move(public_path('uploads/profiles'), $imageName);
                
                // حذف الصورة القديمة إن وجدت
                if ($user->profile_image) {
                    $oldImagePath = public_path($user->profile_image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                $dataToUpdate['profile_image'] = 'uploads/profiles/' . $imageName;
            }

            $user->update($dataToUpdate);

            // ✅ إرجاع الرابط الكامل
            $profileImageUrl = $user->profile_image 
                ? url($user->profile_image) 
                : null;

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البروفايل بنجاح',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'description' => $user->description,
                    'gender' => $user->gender,
                    'profile_image' => $profileImageUrl,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في تحديث البروفايل', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البروفايل',
            ], 500);
        }
    }

    /**
     * عرض بروفايل مجيب معين
     */
    public function getAnswererProfile(Request $request, $userId): JsonResponse
    {
        try {
            $answerer = User::where('id', $userId)
                ->where('is_asker', false)
                ->first();

            if (!$answerer) {
                return response()->json([
                    'success' => false,
                    'message' => 'المجيب غير موجود',
                ], 404);
            }

            // آخر التقييمات
            $recentRatings = $answerer->receivedRatings()
                ->with('asker:id,name')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($rating) {
                    return [
                        'id' => $rating->id,
                        'stars' => $rating->stars,
                        'comment' => $rating->comment,
                        'asker_name' => $rating->asker->name,
                        'created_at' => $rating->created_at->format('Y-m-d'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $answerer->id,
                    'name' => $answerer->name,
                    'description' => $answerer->description,
                    'rating' => [
                        'average' => $answerer->average_rating,
                        'count' => $answerer->ratings_count,
                    ],
                    'stats' => [
                        'completed_orders' => $answerer->ordersAsAnswerer()->where('status', 'completed')->count(),
                    ],
                    'recent_ratings' => $recentRatings,
                    'member_since' => $answerer->created_at->format('Y-m-d'),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في عرض بروفايل المجيب', [
                'error' => $e->getMessage(),
                'answerer_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض البروفايل',
            ], 500);
        }
    }

    /**
     * تقييم مجيب
     */
    public function rateAnswerer(Request $request): JsonResponse
    {
        try {
            $asker = $request->user();

            $validator = Validator::make($request->all(), [
                'answerer_id' => 'required|exists:users,id',
                'stars' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ], [
                'answerer_id.required' => 'معرف المجيب مطلوب',
                'answerer_id.exists' => 'المجيب غير موجود',
                'stars.required' => 'التقييم مطلوب',
                'stars.min' => 'التقييم يجب أن يكون على الأقل 1',
                'stars.max' => 'التقييم لا يمكن أن يتجاوز 5',
                'comment.max' => 'التعليق لا يمكن أن يتجاوز 500 حرف',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // التحقق من أن المستخدم سائل
            if (!$asker->is_asker) {
                return response()->json([
                    'success' => false,
                    'message' => 'فقط السائلين يمكنهم التقييم',
                ], 403);
            }

            // التحقق من أن المجيب ليس سائل
            $answerer = User::find($request->answerer_id);
            if ($answerer->is_asker) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تقييم سائل',
                ], 400);
            }

            // إنشاء التقييم
            $rating = Rating::create([
                'asker_id' => $asker->id,
                'answerer_id' => $request->answerer_id,
                'stars' => $request->stars,
                'comment' => $request->comment,
            ]);

            Log::info('✅ تم إضافة تقييم جديد', [
                'rating_id' => $rating->id,
                'asker_id' => $asker->id,
                'answerer_id' => $request->answerer_id,
                'stars' => $request->stars,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة التقييم بنجاح',
                'data' => [
                    'rating_id' => $rating->id,
                    'answerer' => [
                        'id' => $answerer->id,
                        'name' => $answerer->name,
                        'new_average_rating' => $answerer->fresh()->average_rating,
                        'total_ratings' => $answerer->fresh()->ratings_count,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إضافة التقييم', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة التقييم',
            ], 500);
        }
    }
}
