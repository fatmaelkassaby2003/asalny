<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TermsAndConditions;
use App\Models\PrivacyPolicy;
use App\Models\Faq;
use App\Models\AboutApp;

class StaticPageController extends Controller
{
    /**
     * Get Terms and Conditions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function terms()
    {
        $page = TermsAndConditions::active()->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Terms and conditions not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Terms and conditions retrieved successfully',
            'data' => [
                'title_ar' => $page->title_ar,
                'title_en' => $page->title_en,
                'content_ar' => $page->content_ar,
                'content_en' => $page->content_en,
            ],
        ]);
    }

    /**
     * Get Privacy Policy.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function privacy()
    {
        $page = PrivacyPolicy::active()->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Privacy policy not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Privacy policy retrieved successfully',
            'data' => [
                'title_ar' => $page->title_ar,
                'title_en' => $page->title_en,
                'content_ar' => $page->content_ar,
                'content_en' => $page->content_en,
            ],
        ]);
    }

    /**
     * Get FAQs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function faqs()
    {
        $faqs = Faq::active()->get();

        return response()->json([
            'status' => true,
            'message' => 'FAQs retrieved successfully',
            'data' => $faqs->map(function ($faq) {
                return [
                    'id' => $faq->id,
                    'question_ar' => $faq->question_ar,
                    'question_en' => $faq->question_en,
                    'answer_ar' => $faq->answer_ar,
                    'answer_en' => $faq->answer_en,
                ];
            }),
        ]);
    }

    /**
     * Get About App.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function about()
    {
        $page = AboutApp::active()->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'About app not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'About app retrieved successfully',
            'data' => [
                'title_ar' => $page->title_ar,
                'title_en' => $page->title_en,
                'content_ar' => $page->content_ar,
                'content_en' => $page->content_en,
                'app_version' => $page->app_version,
                'contact_email' => $page->contact_email,
                'contact_phone' => $page->contact_phone,
            ],
        ]);
    }
}
