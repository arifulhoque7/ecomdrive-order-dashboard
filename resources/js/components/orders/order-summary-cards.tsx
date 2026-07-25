import { CircleDollarSign, Clock3, Receipt, ShoppingBag } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { formatMoney } from '@/components/orders/money';
import type { OrderSummary } from '@/types/orders';

function SummaryCard({
    icon: Icon,
    label,
    value,
    meta,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
    meta: string;
}) {
    return (
        <div className="flex items-center gap-3 rounded-lg border border-border bg-card p-4">
            <span className="inline-flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                <Icon size={16} aria-hidden="true" />
            </span>

            <div className="min-w-0">
                <dt className="text-xs text-muted-foreground">{label}</dt>
                <dd className="font-mono text-xl font-semibold text-foreground tabular-nums">
                    {value}
                </dd>
                <p className="truncate text-[11px] text-muted-foreground">
                    {meta}
                </p>
            </div>
        </div>
    );
}

export function OrderSummaryCards({ summary }: { summary: OrderSummary }) {
    return (
        <dl className="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <SummaryCard
                icon={ShoppingBag}
                label="Total orders"
                value={summary.orders_count.toLocaleString()}
                meta="Matching current filters"
            />
            <SummaryCard
                icon={CircleDollarSign}
                label="Revenue"
                value={formatMoney(summary.revenue_cents)}
                meta="Cancelled and refunded excluded"
            />
            <SummaryCard
                icon={Receipt}
                label="Avg order value"
                value={formatMoney(summary.avg_order_value_cents)}
                meta="Across revenue-earning orders"
            />
            <SummaryCard
                icon={Clock3}
                label="Open orders"
                value={summary.open_orders.toLocaleString()}
                meta="Pending, processing or shipped"
            />
        </dl>
    );
}
