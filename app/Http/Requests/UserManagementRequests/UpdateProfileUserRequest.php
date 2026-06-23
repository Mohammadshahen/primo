<?php

namespace App\Http\Requests\UserManagementRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProfileUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'phone')->ignore(Auth::id()),
            ],
            'avatar' => 'nullable|image|mimes:png,jpg,jpeg|mimetypes:image/jpeg,image/png,image/jpg|max:5000',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المستخدم',
            'phone' => 'رقم الواتساب',
            'avatar' => 'الصورة الشخصية',
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرف/أحرف.',
            'unique' => ':attribute مسجل مسبقاً.',
            'image' => 'حقل :attribute يجب أن يكون صورة.',
            'mimes' => 'الصورة يجب أن تكون من نوع: :values.',
            'mimetypes' => 'نوع ملف الصورة غير مسموح به. الأنواع المسموحة: :values.',
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
