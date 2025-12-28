<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// ==================== RegisterRequest ====================
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'gender' => 'required|in:male,female',
            'is_asker' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرف',
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.unique' => 'رقم الجوال مستخدم من قبل',
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'gender.required' => 'الجنس مطلوب',
            'gender.in' => 'الجنس يجب أن يكون male أو female',
            'is_asker.boolean' => 'نوع المستخدم يجب أن يكون true أو false',
            'description.max' => 'الوصف يجب ألا يتجاوز 1000 حرف',
        ];
    }
}

// ==================== SendCodeRequest ====================
class SendCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|exists:users,phone',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.exists' => 'رقم الجوال غير مسجل',
        ];
    }
}

// ==================== VerifyCodeRequest ====================
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
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'كود التحقق مطلوب',
            'code.size' => 'كود التحقق يجب أن يكون 5 أرقام',
        ];
    }
}