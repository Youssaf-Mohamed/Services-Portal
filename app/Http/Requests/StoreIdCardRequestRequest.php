<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdCardRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id_card_type_id' => ['required', 'integer', 'exists:id_card_types,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'proof_image' => ['required', 'string'], // Base64 string
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'id_card_type_id.required' => 'Please select an ID card type',
            'id_card_type_id.exists' => 'Selected ID card type is invalid',
            'payment_method_id.required' => 'Please select a payment method',
            'payment_method_id.exists' => 'Selected payment method is invalid',
            'proof_image.required' => 'Proof image is required',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        // Validate base64 image size (max 2MB)
        if ($this->has('proof_image')) {
            $base64 = $this->input('proof_image');
            $imageSize = (int) (strlen(rtrim($base64, '=')) * 3 / 4);
            $maxSize = 2 * 1024 * 1024; // 2MB

            if ($imageSize > $maxSize) {
                throw new \Illuminate\Validation\ValidationException(
                    validator($this->all(), []),
                    new \Illuminate\Contracts\Validation\Validator()
                );
            }
        }
    }
}
