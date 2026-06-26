<?php

namespace App\Http\Requests\CartRequests;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => 'required|integer|exists:variants,id',
            'count' => 'nullable|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'variant_id' => 'النوع',
            'count' => 'الكمية',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عدداً صحيحاً.',
            'exists' => 'حقل :attribute غير موجود.',
            'min' => 'حقل :attribute يجب أن يكون على الأقل :min.',
        ];
    }
}
