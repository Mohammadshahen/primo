<?php

namespace App\Http\Requests\UserManagementRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePasswordUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string|min:6',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'كلمة المرور الحالية',
            'password' => 'كلمة المرور الجديدة',
            'password_confirmation' => 'تأكيد كلمة المرور الجديدة',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min حرف/أحرف.',
            'confirmed' => 'تأكيد :attribute غير متطابق.',
        ];
    }
}
