<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendCodeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone' => 'required|string|exists:users,phone|regex:/^[0-9+]+$/',
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.exists' => 'رقم الجوال غير مسجل',
        ];
    }
}
