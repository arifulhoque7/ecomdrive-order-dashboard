<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderActivity>
 */
class OrderActivityFactory extends Factory
{
    /**
     * The operator credited with seeded status changes.
     */
    protected static ?int $actorId = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => null,
            'type' => ActivityType::Note,
            'from_status' => null,
            'to_status' => null,
            'description' => fake()->sentence(),
            'meta' => null,
        ];
    }

    public function statusChanged(OrderStatus $from, OrderStatus $to): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ActivityType::StatusChanged,
            'from_status' => $from,
            'to_status' => $to,
            'description' => "Status changed from {$from->label()} to {$to->label()}.",
        ]);
    }

    /**
     * Write the history an order in this status would really have accumulated:
     * placement, payment, then one entry per status hop, spaced out over time.
     */
    public static function trailFor(Order $order): void
    {
        $placedAt = $order->placed_at->copy();

        $entries = collect([self::entry(
            type: ActivityType::OrderPlaced,
            description: "Order placed by {$order->customer->name}.",
            at: $placedAt,
        )]);

        if ($order->status !== OrderStatus::Pending) {
            $entries->push(self::entry(
                type: ActivityType::Payment,
                description: 'Payment captured in full.',
                at: $placedAt->copy()->addHours(1),
                meta: ['amount_cents' => $order->total_cents, 'method' => 'card'],
            ));
        }

        foreach (self::hopsTo($order->status) as $index => [$from, $to]) {
            $entries->push(self::entry(
                type: ActivityType::StatusChanged,
                description: "Status changed from {$from->label()} to {$to->label()}.",
                at: $placedAt->copy()->addHours(6)->addDays($index),
                fromStatus: $from,
                toStatus: $to,
                byOperator: true,
            ));
        }

        $entries->each(fn (array $entry) => OrderActivity::query()->create([
            'order_id' => $order->id,
            'user_id' => $entry['by_operator'] ? self::actorId() : null,
            'type' => $entry['type'],
            'from_status' => $entry['from_status'],
            'to_status' => $entry['to_status'],
            'description' => $entry['description'],
            'meta' => $entry['meta'],
            'created_at' => $entry['at'],
            'updated_at' => $entry['at'],
        ]));
    }

    /**
     * One row of the trail, with every column spelled out.
     *
     * @param  array<string, mixed>|null  $meta
     * @return array{type: ActivityType, description: string, at: CarbonInterface, from_status: OrderStatus|null, to_status: OrderStatus|null, meta: array<string, mixed>|null, by_operator: bool}
     */
    protected static function entry(
        ActivityType $type,
        string $description,
        CarbonInterface $at,
        ?OrderStatus $fromStatus = null,
        ?OrderStatus $toStatus = null,
        ?array $meta = null,
        bool $byOperator = false,
    ): array {
        return [
            'type' => $type,
            'description' => $description,
            'at' => $at,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'meta' => $meta,
            'by_operator' => $byOperator,
        ];
    }

    /**
     * The status hops an order took to reach its current status.
     *
     * @return array<int, array{0: OrderStatus, 1: OrderStatus}>
     */
    protected static function hopsTo(OrderStatus $status): array
    {
        return match ($status) {
            OrderStatus::Pending => [],
            OrderStatus::Processing => [
                [OrderStatus::Pending, OrderStatus::Processing],
            ],
            OrderStatus::Shipped => [
                [OrderStatus::Pending, OrderStatus::Processing],
                [OrderStatus::Processing, OrderStatus::Shipped],
            ],
            OrderStatus::Delivered => [
                [OrderStatus::Pending, OrderStatus::Processing],
                [OrderStatus::Processing, OrderStatus::Shipped],
                [OrderStatus::Shipped, OrderStatus::Delivered],
            ],
            OrderStatus::Cancelled => [
                [OrderStatus::Pending, OrderStatus::Cancelled],
            ],
            OrderStatus::Refunded => [
                [OrderStatus::Pending, OrderStatus::Processing],
                [OrderStatus::Processing, OrderStatus::Shipped],
                [OrderStatus::Shipped, OrderStatus::Refunded],
            ],
        };
    }

    protected static function actorId(): ?int
    {
        return self::$actorId ??= User::query()->value('id');
    }
}
