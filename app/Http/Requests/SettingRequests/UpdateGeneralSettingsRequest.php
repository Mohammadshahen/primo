<?php

namespace App\Http\Requests\SettingRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin middleware protects the route
    }

    public function rules(): array
    {
        return [
            'facebook_account' => ['nullable', 'string', 'max:255'],
            'admin_phone' => ['nullable', 'string', 'max:50'],
            'customer_service_phone' => ['nullable', 'string', 'max:50'],
            'working_hours' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'dollar_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'facebook_account' => 'حساب فيسبوك',
            'admin_phone' => 'رقم الادارة',
            'customer_service_phone' => 'رقم خدمة الزبائن',
            'working_hours' => 'أوقات العمل',
            'location' => 'الموقع',
            'dollar_value' => 'قيمة الدولار',
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'max' => 'حقل :attribute يجب أن لا يتجاوز :max حرفاً.',
            'numeric' => 'حقل :attribute يجب أن يكون رقماً.',
            'min' => 'حقل :attribute يجب أن يكون أكبر من أو يساوي :min.',
        ];
    }
}
