<?php

// app/Http/Requests/RegisterRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone|regex:/^[0-9+]+$/',
            'email' => 'required|email|unique:users,email|max:255',
            'gender' => 'required|in:male,female',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم بالكامل مطلوب',
            'name.string' => 'الاسم بالكامل يجب أن يكون نص',
            'name.max' => 'الاسم بالكامل يجب ألا يتجاوز 255 حرف',
            
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.unique' => 'رقم الجوال مستخدم من قبل',
            'phone.regex' => 'رقم الجوال غير صحيح',
            
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            
            'gender.required' => 'النوع مطلوب',
            'gender.in' => 'النوع يجب أن يكون ذكر أو أنثى',
        ];
    }
}