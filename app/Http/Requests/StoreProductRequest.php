<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($this->route('product')),
            ],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'price_cents' => ['required', 'integer', 'min:1', 'max:100000000'],
            'image_url' => ['required', 'url', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'Another product already uses that SKU.',
            'price_cents.min' => 'Set a price above zero.',
            'image_url.url' => 'Paste a full image URL, including https://.',
        ];
    }
}
