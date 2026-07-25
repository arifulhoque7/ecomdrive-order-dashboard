<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
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
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'status_badge_class' => $this->status->badgeClass(),
            'allowed_transitions' => Collection::make($this->status->transitions())
                ->map(fn (OrderStatus $status) => [
                    'value' => $status,
                    'label' => $status->label(),
                ])
                ->all(),
            'currency' => $this->currency,
            'totals' => [
                'subtotal_cents' => $this->subtotal_cents,
                'discount_cents' => $this->discount_cents,
                'shipping_cents' => $this->shipping_cents,
                'tax_cents' => $this->tax_cents,
                'total_cents' => $this->total_cents,
            ],
            'customer' => [
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
                'city' => $this->customer->city,
            ],
            'shipping_address' => $this->shipping_address,
            'notes' => $this->notes,
            'placed_at' => $this->placed_at->toIso8601String(),
            'items' => OrderItemResource::collection($this->items)->resolve(),
            'activities' => OrderActivityResource::collection($this->activities)->resolve(),
            'ai_insight' => $this->ai_insight,
            'ai_insight_generated_at' => $this->ai_insight_generated_at?->toIso8601String(),
        ];
    }
}
