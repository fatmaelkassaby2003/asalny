<?php

// app/Http/Requests/LocationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
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
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_current' => 'nullable|boolean',
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
            'latitude.required' => 'خط العرض مطلوب',
            'latitude.numeric' => 'خط العرض يجب أن يكون رقم',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90',
            
            'longitude.required' => 'خط الطول مطلوب',
            'longitude.numeric' => 'خط الطول يجب أن يكون رقم',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180',
        ];
    }
}