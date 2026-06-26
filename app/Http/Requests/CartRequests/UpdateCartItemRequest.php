<?php

namespace App\Http\Requests\CartRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'count' => 'required|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'count' => 'الكمية',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عدداً صحيحاً.',
            'min' => 'حقل :attribute يجب أن يكون على الأقل :min.',
        ];
    }
}
