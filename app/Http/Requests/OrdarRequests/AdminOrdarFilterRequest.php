<?php

namespace App\Http\Requests\OrdarRequests;

use Illuminate\Foundation\Http\FormRequest;

class AdminOrdarFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:pending,processing,completed,canceled',
        ];
    }

    public function messages(): array
    {
        return [
            'status.string' => 'حالة الطلب يجب أن تكون نص',
            'status.in' => 'حالة الطلب يجب أن تكون واحدة من القيم التالية: pending, processing, completed, canceled',
        ];
    }
}
