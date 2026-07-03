<?php

namespace App\Http\Requests\UserManagementRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notification_offer' => 'nullable|boolean',
            'notification_order' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'notification_offer' => 'إشعارات العروض',
            'notification_order' => 'إشعارات الطلبات',
        ];
    }

    public function messages(): array
    {
        return [
            'boolean' => 'حقل :attribute يجب أن يكون صحيحاً أو خاطئاً.',
        ];
    }
}
