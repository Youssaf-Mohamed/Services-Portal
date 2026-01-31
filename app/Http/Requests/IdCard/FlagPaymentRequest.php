<?php

namespace App\Http\Requests\IdCard;

use Illuminate\Foundation\Http\FormRequest;

class FlagPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth middleware + policy handles this
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'reason' => 'flag reason',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.min' => 'Please provide a more detailed reason (at least 10 characters).',
        ];
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('reason')) {
            $this->merge([
                'reason' => trim($this->reason),
            ]);
        }
    }
}
