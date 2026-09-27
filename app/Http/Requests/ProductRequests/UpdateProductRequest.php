<?php

namespace App\Http\Requests\ProductRequests;

use App\Models\Variant;
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
            'update_variants.*.is_dollar' => 'nullable|boolean',
            'update_variants.*.stock' => 'nullable|integer|min:0',
            'update_variants.*.is_active' => 'nullable|boolean',
            'update_variants.*.bayan_id' => 'nullable|integer',
            'update_variants.*.bayan_variant_key' => 'nullable|string|size:64',

            'add_variants' => 'nullable|array',
            'add_variants.*.property' => 'required|string|max:255',
            'add_variants.*.price' => 'required|numeric|min:0',
            'add_variants.*.is_dollar' => 'nullable|boolean',
            'add_variants.*.stock' => 'required|integer|min:0',
            'add_variants.*.bayan_id' => 'nullable|integer',
            'add_variants.*.bayan_variant_key' => 'nullable|string|size:64',

        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $claimedIds = [];
            foreach (['update_variants', 'add_variants'] as $group) {
                foreach ($this->input($group, []) as $index => $variantData) {
                    $bayanId = $variantData['bayan_id'] ?? null;
                    if ($bayanId === null || ! is_numeric($bayanId)) {
                        continue;
                    }

                    $localVariantId = $group === 'update_variants' ? ($variantData['id'] ?? null) : null;
                    $query = Variant::where('bayan_id', (int) $bayanId);
                    if ($localVariantId) {
                        $query->where('id', '!=', $localVariantId);
                    }

                    $attribute = "{$group}.{$index}.bayan_id";
                    if ($query->exists() || isset($claimedIds[(int) $bayanId])) {
                        $validator->errors()->add($attribute, 'معرف البيان مرتبط بنوع آخر.');
                    }
                    $claimedIds[(int) $bayanId] = true;
                }
            }
        }];
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
            'update_variants.*.is_dollar' => 'حالة السعر بالدولار',
            'update_variants.*.stock' => 'الكمية',
            'update_variants.*.is_active' => 'الحالة',
            'update_variants.*.bayan_id' => 'معرف البيان',
            'update_variants.*.bayan_variant_key' => 'مفتاح نوع البيان',
            'add_variants' => 'إضافة أنواع',
            'add_variants.*.property' => 'النوع/الحجم',
            'add_variants.*.price' => 'السعر',
            'add_variants.*.is_dollar' => 'حالة السعر بالدولار',
            'add_variants.*.stock' => 'الكمية',
            'add_variants.*.bayan_id' => 'معرف البيان',
            'add_variants.*.bayan_variant_key' => 'مفتاح نوع البيان',
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
            'update_variants.*.is_dollar.boolean' => 'حقل حالة السعر بالدولار يجب أن يكون صحيحاً أو خاطئاً.',
            'update_variants.*.stock.integer' => 'حقل مخزون النوع يجب أن يكون عدداً صحيحاً لكل نوع.',
            'update_variants.*.stock.min' => 'حقل مخزون النوع يجب أن يكون على الأقل :min لكل نوع.',
            'add_variants.*.property.required' => 'حقل  النوع/الحجم مطلوب لكل نوع.',
            'add_variants.*.property.string' => 'حقل اسم النوع/الحجم يجب أن يكون نصاً لكل نوع.',
            'delete_variants.*.exists' => 'حقل  نوع/الحجم مطلوب لكل نوع.',
        ];
    }
}
