import { Money } from '@/components/orders/money';
import type { OrderDetail } from '@/types/orders';

export function OrderTotals({
    totals,
    currency,
}: {
    totals: OrderDetail['totals'];
    currency: string;
}) {
    const rows = [
        { label: 'Subtotal', cents: totals.subtotal_cents },
        { label: 'Discount', cents: -totals.discount_cents },
        { label: 'Shipping', cents: totals.shipping_cents },
        { label: 'Tax', cents: totals.tax_cents },
    ];

    return (
        <dl className="mt-4 ml-auto w-full max-w-xs">
            {rows.map((row) => (
                <div
                    key={row.label}
                    className="flex justify-between py-1 text-sm"
                >
                    <dt className="text-muted-foreground">{row.label}</dt>
                    <dd>
                        <Money cents={row.cents} currency={currency} />
                    </dd>
                </div>
            ))}

            <div className="mt-2 flex justify-between border-t border-border pt-2 text-lg font-bold">
                <dt>Total</dt>
                <dd>
                    <Money cents={totals.total_cents} currency={currency} />
                </dd>
            </div>
        </dl>
    );
}
