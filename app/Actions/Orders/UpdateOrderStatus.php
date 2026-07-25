<?php

namespace App\Actions\Orders;

use App\Enums\ActivityType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Move an order along its fulfilment path, leaving an audit trail behind.
 */
class UpdateOrderStatus
{
    public function execute(Order $order, OrderStatus $status, User $actor): Order
    {
        $from = $order->status;

        if (! $from->canTransitionTo($status)) {
            throw new RuntimeException("A {$from->label()} order cannot be moved to {$status->label()}.");
        }

        return DB::transaction(function () use ($order, $from, $status, $actor): Order {
            $order->status = $status;
            $order->save();

            $order->activities()->create([
                'user_id' => $actor->id,
                'type' => ActivityType::StatusChanged,
                'from_status' => $from,
                'to_status' => $status,
                'description' => "Status changed from {$from->label()} to {$status->label()}.",
            ]);

            return $order;
        });
    }
}
