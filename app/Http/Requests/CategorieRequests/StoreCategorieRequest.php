<?php

namespace App\Http\Requests\CategorieRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image
                                    |mimes:png,jpg,jpeg
                                    |mimetypes:image/jpeg,image/png,image/jpg
                                    |max:5000',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'image' => 'الصورة',
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
        ];
    }
}
