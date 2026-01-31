<?php

namespace App\Http\Requests\IdCard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIdCardSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth middleware handles this
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_account_number' => [
                'required',
                'string',
                'min:5',
                'max:50',
            ],
            'payment_account_name' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],
            'payment_instructions' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'service_enabled' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'payment_account_number' => 'account number',
            'payment_account_name' => 'account name',
            'payment_instructions' => 'payment instructions',
            'service_enabled' => 'service status',
        ];
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('payment_account_number')) {
            $this->merge([
                'payment_account_number' => trim($this->payment_account_number),
            ]);
        }

        if ($this->has('payment_account_name')) {
            $this->merge([
                'payment_account_name' => trim($this->payment_account_name),
            ]);
        }

        if ($this->has('payment_instructions')) {
            $this->merge([
                'payment_instructions' => trim($this->payment_instructions),
            ]);
        }
    }
}
