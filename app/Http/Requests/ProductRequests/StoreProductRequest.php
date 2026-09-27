<?php

namespace App\Http\Requests\ProductRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image
                                    |mimes:png,jpg,jpeg
                                    |mimetypes:image/jpeg,image/png,image/jpg
                                    |max:5000',
            'variant_ids' => 'required|array|min:1',
            'variant_ids.*' => 'required|integer|distinct|exists:variants,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'الفئة',
            'name' => 'الاسم',
            'description' => 'الوصف',
            'image' => 'الصورة',
            'variant_ids' => 'الأنواع الموجودة',
            'variant_ids.*' => 'معرف النوع',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرف/حروف.',
            'image' => 'حقل :attribute يجب أن يكون صورة.',
            'mimes' => 'حقل :attribute يجب أن يكون من النوع: :values.',
            'exists' => 'حقل :attribute غير موجود.',
            'unique' => 'حقل :attribute يجب أن يكون فريداً.',
            'variant_ids.required' => 'يجب تحديد الأنواع الموجودة للمنتج.',
            'variant_ids.array' => 'يجب إرسال معرفات الأنواع ضمن مصفوفة.',
            'variant_ids.min' => 'يجب ربط نوع واحد على الأقل بالمنتج.',
            'variant_ids.*.exists' => 'أحد الأنواع المحددة غير موجود.',
            'variant_ids.*.distinct' => 'لا يمكن تكرار معرف النوع.',
        ];
    }
}
