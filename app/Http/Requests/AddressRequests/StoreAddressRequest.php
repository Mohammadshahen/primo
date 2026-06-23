<?php

namespace App\Http\Requests\AddressRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location_lat' => 'required|string|max:255',
            'location_lng' => 'required|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم العنوان',
            'description' => 'الوصف',
            'location_lat' => 'خط العرض',
            'location_lng' => 'خط الطول',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'غير مصرح لك بالقيام بهذا الإجراء.'
        ], 403));
    }
}
