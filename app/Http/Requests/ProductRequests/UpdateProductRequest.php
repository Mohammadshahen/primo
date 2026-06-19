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


            'update_variants' => 'nullable|array',
            'update_variants.*.id' => 'required|integer|exists:variants,id',
            'update_variants.*.property' => 'nullable|string|max:255', 
            'update_variants.*.price' => 'nullable|numeric|min:0',
            'update_variants.*.stock' => 'nullable|integer|min:0',  
            'update_variants.*.is_active' => 'nullable|boolean',

            'add_variants' => 'nullable|array',
            'add_variants.*.property' => 'required|string|max:255',
            'add_variants.*.price' => 'required|numeric|min:0',
            'add_variants.*.stock' => 'required|integer|min:0',
                            
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
            'update_variants' => 'تحديث الأنواع',
            'update_variants.*.property' => 'النوع/الحجم',
            'update_variants.*.price' => 'السعر',
            'update_variants.*.stock' => 'الكمية',
            'update_variants.*.is_active' => 'الحالة',
            'add_variants' => 'إضافة أنواع',
            'add_variants.*.property' => 'النوع/الحجم',
            'add_variants.*.price' => 'السعر',
            'add_variants.*.stock' => 'الكمية',
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
            'unique' => 'حقل :attribute يجب أن يكون فريداً.',
            'update_variants.*.property.required' => 'حقل  النوع/الحجم مطلوب لكل نوع.',
            'update_variants.*.property.string' => 'حقل اسم النوع/الحجم يجب أن يكون نصاً لكل نوع.',
            'update_variants.*.property.max' => 'حقل اسم النوع/الحجم يجب ألا يتجاوز :max حرف/حروف لكل نوع.',
            'update_variants.*.price.numeric' => 'حقل سعر النوع يجب أن يكون رقماً لكل نوع.',
            'update_variants.*.price.min' => 'حقل سعر النوع يجب أن يكون على الأقل :min لكل نوع.',
            'update_variants.*.stock.integer' => 'حقل مخزون النوع يجب أن يكون عدداً صحيحاً لكل نوع.',
            'update_variants.*.stock.min' => 'حقل مخزون النوع يجب أن يكون على الأقل :min لكل نوع.',
            'add_variants.*.property.required' => 'حقل  النوع/الحجم مطلوب لكل نوع.',
            'add_variants.*.property.string' => 'حقل اسم النوع/الحجم يجب أن يكون نصاً لكل نوع.',
            'delete_variants.*.exists' => 'حقل  نوع/الحجم مطلوب لكل نوع.',
        ];
    }
}
