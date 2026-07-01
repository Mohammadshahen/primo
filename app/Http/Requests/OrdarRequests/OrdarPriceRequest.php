<?php

namespace App\Http\Requests\OrdarRequests;

use Illuminate\Foundation\Http\FormRequest;

class OrdarPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_delivery' => 'required|boolean',
            //اذا كان الطلب توصيل، يجب تحديد العنوان
            'address_id' => 'nullable|integer|exists:addresses,id|required_if:is_delivery,true',
        ];
    }

    public function messages(): array
    {
        return [
            'is_delivery.required' => 'حقل is_delivery مطلوب',
            'is_delivery.boolean' => 'حقل is_delivery يجب أن يكون صحيح أو خاطئ',
            'address_id.integer' => 'العنوان غير صالح',
            'address_id.exists' => 'العنوان غير موجود',
            'address_id.required_if' => 'يجب تحديد العنوان إذا كان الطلب توصيل',
        ];
    }
}
