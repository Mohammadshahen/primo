<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuggestionRequest extends FormRequest
{
    public function authorize()
    {
        // authorize authenticated users; adjust if you have specific policies
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'اسم الاقتراح مطلوب.',
            'name.string' => 'اسم الاقتراح يجب أن يكون نصًا.',
            'name.max' => 'اسم الاقتراح لا يمكن أن يتجاوز 255 حرفًا.',
            'description.required' => 'وصف الاقتراح مطلوب.',
            'description.string' => 'وصف الاقتراح يجب أن يكون نصًا.',
        ];
    }
}
