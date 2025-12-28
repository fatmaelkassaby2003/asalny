<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|size:5',
            'phone' => 'required|string|exists:users,phone', // ✅ إضافة phone
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'كود التحقق مطلوب',
            'code.size' => 'كود التحقق يجب أن يكون 5 أرقام',
            'phone.required' => 'رقم الجوال مطلوب',        // ✅
            'phone.exists' => 'رقم الجوال غير مسجل',      // ✅
        ];
    }
}