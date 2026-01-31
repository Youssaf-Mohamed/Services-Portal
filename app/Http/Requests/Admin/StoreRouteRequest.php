<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'price_one_way' => 'required|numeric|min:0',
            'monthly_discount_percent' => 'nullable|numeric|min:0|max:100',
            'term_discount_percent' => 'nullable|numeric|min:0|max:100',
            'active' => 'boolean',
        ];
    }
}
