<?php

namespace App\Http\Requests\ProductRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => 'nullable|integer|exists:categories,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image
                                    |mimes:png,jpg,jpeg
                                    |mimetypes:image/jpeg,image/png,image/jpg
                                    |max:5000',

            'variant_ids' => 'sometimes|array',
            'variant_ids.*' => 'required|integer|distinct|exists:variants,id',
            'add_variants' => 'prohibited',
            'update_variants' => 'prohibited',

        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'الفئة',
            'name' => 'الاسم',
            'description' => 'الوصف',
            'sku_code' => 'رمز SKU',
            'image' => 'الصورة',
            'variant_ids' => 'الأنواع الموجودة',
            'variant_ids.*' => 'معرف النوع',
            'delete_variants' => 'حذف الأنواع',
            'delete_variants.*' => 'معرف النوع',
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
            'variant_ids.*.distinct' => 'لا يمكن تكرار معرف النوع.',
            'add_variants.prohibited' => 'إنشاء أنواع جديدة مع تحديث المنتج غير مسموح؛ أرسل variant_ids للأنواع الموجودة.',
            'update_variants.prohibited' => 'تحديث بيانات الأنواع من خلال تحديث المنتج غير مسموح.',
            'unique' => 'حقل :attribute يجب أن يكون فريداً.',
            'delete_variants.*.exists' => 'حقل  نوع/الحجم مطلوب لكل نوع.',
        ];
    }
}
