<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    /**
     * Get all active support categories
     */
    public function getCategories()
    {
        $categories = SupportCategory::active()
            ->select('id', 'name_ar', 'name_en', 'icon')
            ->get();

        // Append full url to icon if exists
        $categories->transform(function ($category) {
            if ($category->icon) {
                $category->icon = asset('storage/' . $category->icon);
            }
            return $category;
        });

        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع أنواع المشاكل بنجاح',
            'data' => $categories
        ]);
    }

    /**
     * Create a new support ticket
     */
    public function createTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:support_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['category_id', 'title', 'description']);
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('support-tickets', 'public');
            $data['image'] = $path;
        }

        $ticket = SupportTicket::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم استلام بلاغك بنجاح، سنقوم بالرد عليك قريباً',
            'data' => $ticket
        ], 201);
    }

    /**
     * Get user's tickets
     */
    public function getMyTickets()
    {
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->with(['category:id,name_ar,name_en'])
            ->orderBy('created_at', 'desc')
            ->get();

        $tickets->transform(function ($ticket) {
            if ($ticket->image) {
                $ticket->image = asset('storage/' . $ticket->image);
            }
            return $ticket;
        });

        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع بلاغاتك بنجاح',
            'data' => $tickets
        ]);
    }
}
