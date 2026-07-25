<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonInterface;

/**
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property OrderStatus $status
 * @property string $currency
 * @property int $subtotal_cents
 * @property int $discount_cents
 * @property int $shipping_cents
 * @property int $tax_cents
 * @property int $total_cents
 * @property array<string, string>|null $shipping_address
 * @property string|null $notes
 * @property CarbonInterface $placed_at
 * @property array<string, mixed>|null $ai_insight
 * @property CarbonInterface|null $ai_insight_generated_at
 * @property-read Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderActivity> $activities
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'USD',
        'status' => 'pending',
        'subtotal_cents' => 0,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 0,
    ];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class)->latest('created_at');
    }

    /**
     * Match an order number, or the customer's name or email.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when(
            filled($term),
            fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('order_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                    )
                )
        );
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, ?OrderStatus $status): void
    {
        $query->when($status, fn (Builder $query) => $query->where('status', $status));
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function placedBetween(Builder $query, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $query
            ->when($from, fn (Builder $query) => $query->where('placed_at', '>=', $from->startOfDay()))
            ->when($to, fn (Builder $query) => $query->where('placed_at', '<=', $to->endOfDay()));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'placed_at' => 'datetime',
            'shipping_address' => 'array',
            'ai_insight' => 'array',
            'ai_insight_generated_at' => 'datetime',
        ];
    }
}
