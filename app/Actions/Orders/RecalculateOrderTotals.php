<?php

namespace App\Actions\Orders;

use App\Models\Order;

/**
 * Single source of truth for order money.
 *
 * Every total on an order is derived from its line items, so a seeded order and
 * an edited order can never disagree with the items the customer actually bought.
 */
class RecalculateOrderTotals
{
    /**
     * Orders at or above this net value ship free.
     */
    public const int FREE_SHIPPING_THRESHOLD_CENTS = 7_500;

    public const int FLAT_SHIPPING_CENTS = 995;

    public const float TAX_RATE = 0.0825;

    public function execute(Order $order): Order
    {
        $subtotalCents = (int) $order->items()->sum('line_total_cents');
        $discountCents = min($order->discount_cents, $subtotalCents);
        $netCents = $subtotalCents - $discountCents;

        $shippingCents = $netCents >= self::FREE_SHIPPING_THRESHOLD_CENTS
            ? 0
            : self::FLAT_SHIPPING_CENTS;

        $taxCents = (int) round($netCents * self::TAX_RATE);

        $order->forceFill([
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => $discountCents,
            'shipping_cents' => $shippingCents,
            'tax_cents' => $taxCents,
            'total_cents' => $netCents + $shippingCents + $taxCents,
        ])->save();

        return $order;
    }
}
