<?php

namespace App\Http\Requests\OfferRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => 'nullable|required|exists:variants,id|unique:offers,variant_id,' . $this->route('offer')?->id,
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'discount_percentage' => 'nullable|numeric|min:0|max:100|prohibited_unless:discount_value,null',
            'discount_value' => 'nullable|numeric|min:0|prohibited_unless:discount_percentage,null',
        ];
    }

    public function attributes(): array
    {
        return [
            'variant_id' => 'نوع المنتج',
            'from' => 'تاريخ البداية',
            'to' => 'تاريخ النهاية',
            'discount_percentage' => 'نسبة الخصم',
            'discount_value' => 'قيمة الخصم',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'required_without' => 'يجب إدخال إما نسبة الخصم أو قيمة الخصم على الأقل.',
            'prohibited_unless' => 'لا يمكن إرسال نسبة الخصم وقيمة الخصم معًا.',
            'date' => 'حقل :attribute يجب أن يكون تاريخاً.',
            'numeric' => 'حقل :attribute يجب أن يكون رقماً.',
            'min' => 'حقل :attribute لا يجب أن يكون أقل من :min.',
            'max' => 'حقل :attribute لا يجب أن يتجاوز :max.',
            'exists' => 'القيمة المختارة لـ :attribute غير موجودة.',
            'unique' => 'هذا النوع لديه عرض بالفعل.',
            'before_or_equal' => 'يجب أن يكون تاريخ البداية قبل أو يساوي تاريخ النهاية.',
            'after_or_equal' => 'يجب أن يكون تاريخ النهاية بعد أو يساوي تاريخ البداية.',
        ];
    }
}