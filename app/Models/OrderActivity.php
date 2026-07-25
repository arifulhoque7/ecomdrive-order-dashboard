<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Enums\OrderStatus;
use Database\Factories\OrderActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $user_id
 * @property ActivityType $type
 * @property OrderStatus|null $from_status
 * @property OrderStatus|null $to_status
 * @property string $description
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class OrderActivity extends Model
{
    /** @use HasFactory<OrderActivityFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'from_status' => OrderStatus::class,
            'to_status' => OrderStatus::class,
            'meta' => 'array',
        ];
    }
}
