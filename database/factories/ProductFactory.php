<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'sku' => Str::of($name)->substr(0, 3)->upper()->append('-')->append(fake()->unique()->numerify('#####'))->value(),
            'name' => $name,
            'image_url' => 'https://burst.shopifycdn.com/photos/black-headphones-closeup.jpg?width=640&format=pjpg',
            'category_id' => Category::factory(),
            'price_cents' => fake()->numberBetween(1_500, 24_900),
            'is_active' => true,
        ];
    }
}
