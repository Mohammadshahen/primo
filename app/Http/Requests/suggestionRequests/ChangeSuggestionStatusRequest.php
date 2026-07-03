<?php

namespace App\Http\Requests\suggestionRequests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeSuggestionStatusRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'حالة الاقتراح مطلوبة.',
            'status.string' => 'حالة الاقتراح يجب أن تكون نصًا.',
            'status.in' => 'حالة الاقتراح يجب أن تكون واحدة من: pending, approved, rejected.',
        ];
    } 
}
