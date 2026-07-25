<?php

namespace Database\Factories;

use App\Actions\Orders\RecalculateOrderTotals;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $placedAt = fake()->dateTimeBetween('-90 days', 'now');

        return [
            'order_number' => 'ORD-'.Str::upper(fake()->unique()->bothify('??####')),
            'customer_id' => Customer::factory(),
            'status' => fake()->randomElement(OrderStatus::cases()),
            'currency' => 'USD',
            'discount_cents' => 0,
            'shipping_address' => [
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->randomElement(['CA', 'NY', 'TX', 'WA', 'IL', 'FL', 'CO', 'MA']),
                'postal_code' => fake()->postcode(),
                'country' => 'US',
            ],
            'notes' => fake()->boolean(25) ? fake()->sentence() : null,
            'placed_at' => $placedAt,
        ];
    }

    /**
     * Give every order line items, derive its money from them, then write an
     * activity trail that actually matches the status it ended up in.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Order $order): void {
            if ($order->items()->doesntExist()) {
                OrderItem::factory()->count(fake()->numberBetween(1, 4))->for($order)->create();
            }

            if (fake()->boolean(30)) {
                $subtotalCents = (int) $order->items()->sum('line_total_cents');
                $order->discount_cents = (int) round($subtotalCents * 0.1);
            }

            app(RecalculateOrderTotals::class)->execute($order);

            OrderActivityFactory::trailFor($order);
        });
    }

    public function withItems(int $count): static
    {
        return $this->has(OrderItem::factory()->count($count), 'items');
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function pending(): static
    {
        return $this->status(OrderStatus::Pending);
    }

    public function processing(): static
    {
        return $this->status(OrderStatus::Processing);
    }

    public function delivered(): static
    {
        return $this->status(OrderStatus::Delivered);
    }

    /**
     * An order the operator cannot fulfil yet, so the AI insight has gaps to flag.
     */
    public function missingShippingAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_address' => null,
        ]);
    }
}
