<?php

namespace App\Http\Requests\OrdarRequests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmOrdarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_delivere' => 'required|boolean',
            'address_id' => 'nullable|integer|exists:addresses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'is_delivere.required' => 'حقل is_delivere مطلوب',
            'is_delivere.boolean' => 'حقل is_delivere يجب أن يكون صحيح أو خاطئ',
            'address_id.integer' => 'العنوان غير صالح',
            'address_id.exists' => 'العنوان غير موجود',
        ];
    }
}
