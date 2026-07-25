<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Line items snapshot the product as it was sold, so a later price change
     * never rewrites history.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::query()->active()->inRandomOrder()->first() ?? Product::factory()->create();
        $quantity = fake()->numberBetween(1, 4);

        return [
            'order_id' => Order::factory(),
            'sku' => $product->sku,
            'name' => $product->name,
            'image_url' => $product->image_url,
            'quantity' => $quantity,
            'unit_price_cents' => $product->price_cents,
            'line_total_cents' => $quantity * $product->price_cents,
        ];
    }
}
