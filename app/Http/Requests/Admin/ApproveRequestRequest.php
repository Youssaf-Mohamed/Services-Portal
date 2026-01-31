<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
        ];
    }
}
