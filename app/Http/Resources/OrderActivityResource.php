<?php

namespace App\Http\Resources;

use App\Models\OrderActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderActivity
 */
class OrderActivityResource extends JsonResource
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
            'type' => $this->type,
            'type_label' => $this->type->label(),
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'to_status_badge_class' => $this->to_status?->badgeClass(),
            'description' => $this->description,
            'actor' => $this->user?->name,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
