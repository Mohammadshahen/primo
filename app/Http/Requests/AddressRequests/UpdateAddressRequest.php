<?php

namespace App\Http\Requests\AddressRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location_lat' => 'nullable|string|max:255',
            'location_lng' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم العنوان',
            'description' => 'الوصف',
            'location_lat' => 'خط العرض',
            'location_lng' => 'خط الطول',
            'phone' => 'رقم الجوال',
        ];
    }

    public function messages()
    {
        return [
            'phone.string' => 'حقل :attribute يجب أن يكون نصًا.',
            'phone.max' => 'حقل :attribute لا يمكن أن يتجاوز 255 حرف.',
            'location_lat.string' => 'حقل :attribute يجب أن يكون نصًا.',
            'location_lat.max' => 'حقل :attribute لا يمكن أن يتجاوز 255 حرف.',
            'location_lng.string' => 'حقل :attribute يجب أن يكون نصًا.',
            'location_lng.max' => 'حقل :attribute لا يمكن أن يتجاوز 255 حرف.',
            'name.string' => 'حقل :attribute يجب أن يكون نصًا.',
            'name.max' => 'حقل :attribute لا يمكن أن يتجاوز 255 حرف.',
        ];
    }
}
