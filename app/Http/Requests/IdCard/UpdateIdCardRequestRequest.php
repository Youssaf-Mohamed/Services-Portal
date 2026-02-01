<?php

namespace App\Http\Requests\IdCard;

use App\Models\IdCardSetting;
use App\Models\IdCardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIdCardRequestRequest extends FormRequest
{
    protected ?IdCardType $resolvedType = null;

    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\IdCardRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            // Type code is needed to resolve type rules, even if not changing
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
            'transfer_time' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
            // Screenshot optional on update
            'transfer_screenshot' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120', // 5MB
            ],
            // Photo optional on update
            'new_photo' => [
                'nullable',
                'file',
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

            // For update, we don't strictly require new_photo even if type requires it, 
            // because they might be keeping the old one. 
            // Logic to check if "old photo exists" would be complex here without fetching the model.
            // We assume the controller or UI handles the "if rejected for bad photo, user MUST upload new one" logic 
            // or we just let them update what they can.
            // Ideally, we trust the user is fixing what was asked.

            if ($this->resolvedType->requires_description && empty($this->issue_description)) {
                $validator->errors()->add('issue_description', 'A description of the issue is required for this service type.');
            }


        });
    }

    public function getResolvedType(): ?IdCardType
    {
        return $this->resolvedType;
    }

    protected function isRealImage($file): bool
    {
        if (!$file || !$file->isValid()) {
            return false;
        }

        $info = @getimagesize($file->getPathname());
        return $info !== false && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG]);
    }

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

    public function messages(): array
    {
        return [
            'transfer_screenshot.max' => 'The transfer screenshot must not be larger than 5MB.',
            'new_photo.max' => 'The new photo must not be larger than 2MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        \Illuminate\Support\Facades\Log::info('UpdateIdCardRequestRequest PREPARE:', [
            'all' => $this->all(),
            'files' => $this->allFiles(),
            'transfer_screenshot_type' => gettype($this->input('transfer_screenshot')),
            'has_file' => $this->hasFile('transfer_screenshot'),
        ]);

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
