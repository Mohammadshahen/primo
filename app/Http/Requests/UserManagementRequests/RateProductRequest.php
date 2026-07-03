<?php

namespace App\Http\Requests\UserManagementRequests;

use Illuminate\Foundation\Http\FormRequest;

class RateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required','integer','between:1,5'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'حقل التقييم مطلوب',
            'rating.between' => 'قيمة التقييم يجب أن تكون بين 1 و 5',
        ];
    }
}
