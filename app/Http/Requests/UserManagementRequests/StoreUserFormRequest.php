<?php

namespace App\Http\Requests\UserManagementRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|max:255|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'avatar'   => 'nullable|image
                                    |mimes:png,jpg,jpeg
                                    |mimetypes:image/jpeg,image/png,image/jpg
                                    |max:5000',
            'fcm_token' => 'nullable|string|min:10|max:255',
        ];
    }
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'phone' => 'رقم الواتساب',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'fcm_token' => ' توكين الاشعارات',
            'avatar' => 'الصورة الشخصية',

        ];
    }
    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرف/أحرف.',
            'unique' => ':attribute مسجل مسبقاً.',
            'min' => 'حقل :attribute يجب ألا يقل عن :min حرف/أحرف.',

            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'in' => 'حقل :attribute يجب أن يكون أحد قيمتين ذكر أو أنثى',

            'avatar.image' => 'حقل :attribute يجب أن يكون صورة.',
            'avatar.mimes' => 'الصورة يجب أن تكون من نوع: :values.',
            'avatar.max' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت (ما يعادل 5 ميجابايت).',
            'avatar.mimetypes' => 'نوع ملف الصورة غير مسموح به. الأنواع المسموحة: :values.',
            'fcm_token.string' => 'حقل :attribute يجب أن يكون نصاً.',
            'fcm_token.min' => 'توكين الاشعارات غير صالح',
            'fcm_token.max' => 'توكين الاشعارات غير صالح',
        ];
    }
}
