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
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image
                                    |mimes:png,jpg,jpeg
                                    |mimetypes:image/jpeg,image/png,image/jpg
                                    |max:5000',
            'variants' => 'required|array|min:1',
            'variants.*.property' => 'required|string|max:255',
            'variants.*.price' => 'required|numeric|min:1',
            'variants.*.is_dollar' => 'nullable|boolean',
            'variants.*.stock' => 'required|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'الفئة',
            'name' => 'الاسم',
            'description' => 'الوصف',
            'image' => 'الصورة',
            'variants' => 'الأنواع',
            'variants.*.property' => 'النوع,الحجم',
            'variants.*.price' => 'سعر النوع',
            'variants.*.is_dollar' => 'حالة السعر بالدولار',
            'variants.*.stock' => 'مخزون النوع',
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
            'variants.required' => 'يجب ادخال حقل :attribute.',
            'variants.array' => 'حقل :attribute يجب أن يكون مصفوفة.',
            'variants.min' => 'يجب أن يحتوي حقل :attribute على الأقل :min عنصر.',
            'variants.*.property.required' => 'حقل  النوع/الحجم مطلوب لكل نوع.',
            'variants.*.property.string' => 'حقل اسم النوع/الحجم يجب أن يكون نصاً لكل نوع.',
            'variants.*.property.max' => 'حقل اسم النوع/الحجم يجب ألا يتجاوز :max حرف/حروف لكل نوع.',
            'variants.*.price.required' => 'حقل سعر النوع مطلوب لكل نوع.',
            'variants.*.price.numeric' => 'حقل سعر النوع يجب أن يكون رقماً لكل نوع.',
            'variants.*.price.min' => 'حقل سعر النوع يجب أن يكون على الأقل :min لكل نوع.',
            'variants.*.is_dollar.boolean' => 'حقل حالة السعر بالدولار يجب أن يكون صحيحاً أو خاطئاً.',
            'variants.*.stock.required' => 'حقل مخزون النوع مطلوب لكل نوع.',
            'variants.*.stock.integer' => 'حقل مخزون النوع يجب أن يكون عدداً صحيحاً لكل نوع.',
            'variants.*.stock.min' => 'حقل مخزون النوع يجب أن يكون على الأقل :min لكل نوع.',
        ];
    }
}
