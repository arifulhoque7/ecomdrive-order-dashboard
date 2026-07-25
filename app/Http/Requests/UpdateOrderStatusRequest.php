<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(OrderStatus::class),
                fn (string $attribute, mixed $value, Closure $fail) => $this->rejectIllegalTransition($value, $fail),
            ],
        ];
    }

    public function status(): OrderStatus
    {
        return $this->enum('status', OrderStatus::class);
    }

    public function order(): ?Order
    {
        $order = $this->route('order');

        return $order instanceof Order ? $order : null;
    }

    /**
     * An order may only move along the fulfilment paths its current status allows.
     */
    protected function rejectIllegalTransition(mixed $value, Closure $fail): void
    {
        $next = OrderStatus::tryFrom((string) $value);
        $current = $this->order()?->status;

        if ($next === null || $current === null || $current->canTransitionTo($next)) {
            return;
        }

        $fail("A {$current->label()} order cannot be moved to {$next->label()}.");
    }
}
