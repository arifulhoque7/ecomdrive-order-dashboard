<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class OrderSeeder extends Seeder
{
    /**
     * How a healthy store's order book actually splits, rather than an even
     * spread across every status.
     *
     * @var array<string, int>
     */
    protected array $mix = [
        'delivered' => 42,
        'shipped' => 20,
        'processing' => 22,
        'confirmed' => 14,
        'pending' => 16,
        'cancelled' => 12,
        'refunded' => 8,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::factory()->count(30)->create();

        Collection::make($this->mix)->each(fn (int $count, string $status) => Order::factory()
            ->count($count)
            ->status(OrderStatus::from($status))
            ->recycle($customers)
            ->create()
        );

        Order::factory()
            ->count(4)
            ->pending()
            ->missingShippingAddress()
            ->recycle(Customer::factory()->count(2)->withoutPhone()->create())
            ->create();
    }
}
