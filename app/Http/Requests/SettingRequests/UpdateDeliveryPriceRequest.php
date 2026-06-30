<?php

namespace App\Http\Requests\SettingRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'price' => 'السعر',
        ];
    }

    public function messages(): array
    {
        return [
            'numeric' => 'حقل :attribute يجب أن يكون رقماً.',
            'min' => 'حقل :attribute يجب أن يكون أكبر من أو يساوي :min.',
        ];
    }
}
