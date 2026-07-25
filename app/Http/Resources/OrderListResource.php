<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer->name,
            'customer_email' => $this->customer->email,
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'status_badge_class' => $this->status->badgeClass(),
            'items_count' => (int) $this->items_count,
            'currency' => $this->currency,
            'total_cents' => $this->total_cents,
            'placed_at' => $this->placed_at->toIso8601String(),
        ];
    }
}
