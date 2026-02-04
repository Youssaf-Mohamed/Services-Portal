<?php

namespace App\Http\Requests\IdCard;

use App\Models\IdCardRequest;
use App\Models\IdCardSetting;
use App\Models\IdCardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdCardRequestRequest extends FormRequest
{
    /**
     * The resolved ID card type.
     */
    protected ?IdCardType $resolvedType = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\IdCardRequest::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type_code' => [
                'required',
                'string',
                Rule::in(['lost', 'photo_change', 'damaged']),
            ],
            'transaction_number' => [
                'required',
                'string',
                'min:3',
                'max:50',
            ],
            'paid_from_number' => [
                'required',
                'string',
                'min:11',
                'max:15',
            ],
            'transfer_time' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
            'transfer_screenshot' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120', // 5MB
            ],
            // Conditional fields - validated in withValidator
            'new_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048', // 2MB
            ],
            'issue_description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Check if service is enabled
            if (!IdCardSetting::isServiceEnabled()) {
                $validator->errors()->add('service', 'ID Card services are temporarily unavailable.');
                return;
            }

            // Resolve the type by code
            $this->resolvedType = IdCardType::findActiveByCode($this->type_code);

            if (!$this->resolvedType) {
                $validator->errors()->add('type_code', 'The selected service type is not available.');
                return;
            }

            // Validate conditional fields based on type requirements
            if ($this->resolvedType->requires_photo && !$this->hasFile('new_photo')) {
                $validator->errors()->add('new_photo', 'A new photo is required for this service type.');
            }

            if ($this->resolvedType->requires_description && empty($this->issue_description)) {
                $validator->errors()->add('issue_description', 'A description of the issue is required for this service type.');
            }

            // Check for duplicate open requests of the same type
            $hasOpenRequest = IdCardRequest::where('user_id', $this->user()->id)
                ->where('type_id', $this->resolvedType->id)
                ->open()
                ->exists();

            if ($hasOpenRequest) {
                $validator->errors()->add(
                    'type_code',
                    'You already have an open request for this service type. Please wait for it to be completed.'
                );
            }

            // Validate that uploaded files are real images
            if ($this->hasFile('transfer_screenshot')) {
                $screenshot = $this->file('transfer_screenshot');
                if (!$this->isRealImage($screenshot)) {
                    $validator->errors()->add('transfer_screenshot', 'The transfer screenshot must be a valid image file.');
                }
            }

            if ($this->hasFile('new_photo')) {
                $photo = $this->file('new_photo');
                if (!$this->isRealImage($photo)) {
                    $validator->errors()->add('new_photo', 'The new photo must be a valid image file.');
                }
            }
        });
    }

    /**
     * Get the resolved type (available after validation).
     */
    public function getResolvedType(): ?IdCardType
    {
        return $this->resolvedType;
    }

    /**
     * Check if an uploaded file is a real image.
     */
    protected function isRealImage($file): bool
    {
        if (!$file || !$file->isValid()) {
            return false;
        }

        $info = @getimagesize($file->getPathname());
        return $info !== false && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type_code' => 'service type',
            'transaction_number' => 'transaction number',
            'transfer_time' => 'transfer time',
            'transfer_screenshot' => 'transfer screenshot',
            'new_photo' => 'new photo',
            'issue_description' => 'issue description',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'transfer_screenshot.max' => 'The transfer screenshot must not be larger than 5MB.',
            'new_photo.max' => 'The new photo must not be larger than 2MB.',
        ];
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim string inputs
        if ($this->has('transaction_number')) {
            $this->merge([
                'transaction_number' => trim($this->transaction_number),
            ]);
        }

        if ($this->has('issue_description')) {
            $this->merge([
                'issue_description' => trim($this->issue_description),
            ]);
        }
    }
}
