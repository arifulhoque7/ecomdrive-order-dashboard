<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required_without:customer', 'nullable', 'integer', Rule::exists('customers', 'id')],
            'customer.name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'customer.email' => ['required_without:customer_id', 'nullable', 'email', 'max:255', Rule::unique('customers', 'email')],
            'customer.phone' => ['nullable', 'string', 'max:32'],
            'customer.city' => ['nullable', 'string', 'max:120'],

            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],

            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one product to the order.',
            'items.min' => 'Add at least one product to the order.',
            'customer_id.required_without' => 'Pick an existing customer or enter a new one.',
            'customer.email.unique' => 'That email already belongs to a customer — pick them from the list instead.',
        ];
    }

    /**
     * Reuse the chosen customer, or open an account for the walk-in.
     */
    public function customer(): Customer
    {
        $existing = $this->integer('customer_id');

        if ($existing > 0) {
            return Customer::query()->findOrFail($existing);
        }

        return Customer::query()->create($this->validated('customer'));
    }

    /**
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public function lines(): array
    {
        return $this->validated('items');
    }
}
