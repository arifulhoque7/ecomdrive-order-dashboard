<?php

namespace App\Actions\Orders;

use App\Enums\ActivityType;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ring up an order at the counter.
 *
 * Prices come from the catalogue rather than the request, so a tampered payload
 * cannot discount itself, and each line snapshots the product as it was sold.
 */
class CreateOrder
{
    public function __construct(protected RecalculateOrderTotals $recalculateOrderTotals) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @param  array<string, string>|null  $shippingAddress
     */
    public function execute(
        Customer $customer,
        array $lines,
        User $actor,
        int $discountCents = 0,
        ?array $shippingAddress = null,
        ?string $notes = null,
    ): Order {
        $products = Product::query()
            ->whereIn('id', Collection::make($lines)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($customer, $lines, $actor, $discountCents, $shippingAddress, $notes, $products): Order {
            $order = Order::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'customer_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'discount_cents' => $discountCents,
                'shipping_address' => $shippingAddress,
                'notes' => $notes,
                'placed_at' => Carbon::now(),
            ]);

            Collection::make($lines)->each(function (array $line) use ($order, $products): void {
                $product = $products->get($line['product_id']);

                $order->items()->create([
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'image_url' => $product->image_url,
                    'quantity' => $line['quantity'],
                    'unit_price_cents' => $product->price_cents,
                    'line_total_cents' => $line['quantity'] * $product->price_cents,
                ]);
            });

            $this->recalculateOrderTotals->execute($order);

            $order->activities()->create([
                'user_id' => $actor->id,
                'type' => ActivityType::OrderPlaced,
                'description' => "Order placed at the counter for {$customer->name}.",
            ]);

            return $order;
        });
    }

    protected function nextOrderNumber(): string
    {
        do {
            $number = 'ORD-'.Str::upper(Str::random(2).random_int(1000, 9999));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
