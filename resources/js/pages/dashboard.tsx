import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Plus } from 'lucide-react';
import orders from '@/actions/App/Http/Controllers/OrderController';
import { formatMoney } from '@/components/orders/money';
import { OrderStatusBadge } from '@/components/orders/order-status-badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { OrderListItem, OrderStatus } from '@/types/orders';

type Headline = {
    orders_today: number;
    revenue_today: number;
    revenue_30d: number;
    avg_order_value_30d: number;
    open_orders: number;
    awaiting_payment: number;
};

type TrendPoint = { day: string; label: string; revenue_cents: number };

type StatusSlice = {
    value: OrderStatus;
    label: string;
    badge_class: string;
    count: number;
};

type TopCustomer = {
    name: string;
    email: string;
    orders_count: number;
    revenue_cents: number;
};

type Props = {
    headline: Headline;
    revenueTrend: TrendPoint[];
    statusBreakdown: StatusSlice[];
    needsAttention: OrderListItem[];
    recentOrders: OrderListItem[];
    topCustomers: TopCustomer[];
};

const dayFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: '2-digit',
});

function greeting(): string {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
}

export default function Dashboard({
    headline,
    revenueTrend,
    statusBreakdown,
    needsAttention,
    recentOrders,
    topCustomers,
}: Props) {
    const { auth } = usePage().props;
    const peak = Math.max(
        ...revenueTrend.map((point) => point.revenue_cents),
        1,
    );
    const totalOrders = statusBreakdown.reduce(
        (sum, slice) => sum + slice.count,
        0,
    );

    return (
        <>
            <Head title="Dashboard" />

            <div className="p-4">
                <header className="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-foreground">
                            {greeting()}, {auth.user.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {headline.open_orders} open order
                            {headline.open_orders === 1 ? '' : 's'} need moving
                            today.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={orders.index()}>View all orders</Link>
                        </Button>
                        <Button asChild>
                            <Link href={orders.create()}>
                                <Plus size={16} aria-hidden="true" />
                                New order
                            </Link>
                        </Button>
                    </div>
                </header>

                <dl className="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Metric
                        label="Orders today"
                        value={headline.orders_today.toLocaleString()}
                        meta={`${formatMoney(headline.revenue_today)} taken today`}
                    />
                    <Metric
                        label="Revenue (30 days)"
                        value={formatMoney(headline.revenue_30d)}
                        meta="Cancelled and refunded excluded"
                    />
                    <Metric
                        label="Avg order value"
                        value={formatMoney(headline.avg_order_value_30d)}
                        meta="Last 30 days"
                    />
                    <Metric
                        label="Awaiting payment"
                        value={headline.awaiting_payment.toLocaleString()}
                        meta={`${headline.open_orders} open in total`}
                    />
                </dl>

                <div className="grid gap-4 xl:grid-cols-3">
                    <div className="space-y-4 xl:col-span-2">
                        <section className="rounded-lg border border-border bg-card p-4">
                            <div className="flex items-baseline justify-between gap-3">
                                <h2 className="text-sm font-semibold text-foreground">
                                    Revenue, last 14 days
                                </h2>
                                <span className="font-mono text-xs text-muted-foreground">
                                    peak {formatMoney(peak)}
                                </span>
                            </div>

                            <div className="mt-6 flex h-44 items-end gap-1.5">
                                {revenueTrend.map((point) => (
                                    <div
                                        key={point.day}
                                        className="group flex h-full flex-1 flex-col justify-end gap-2"
                                        title={`${point.label}: ${formatMoney(point.revenue_cents)}`}
                                    >
                                        <div
                                            className={cn(
                                                'w-full rounded-t-md transition-colors',
                                                point.revenue_cents === peak
                                                    ? 'bg-primary'
                                                    : 'bg-primary/25 group-hover:bg-primary/50',
                                            )}
                                            style={{
                                                height: `${Math.max((point.revenue_cents / peak) * 100, 2)}%`,
                                            }}
                                        />
                                    </div>
                                ))}
                            </div>

                            <div className="mt-2 flex gap-1.5">
                                {revenueTrend.map((point, index) => (
                                    <span
                                        key={point.day}
                                        className="flex-1 text-center font-mono text-[10px] text-muted-foreground"
                                    >
                                        {index % 2 === 0
                                            ? dayFormatter.format(
                                                  new Date(point.day),
                                              )
                                            : ''}
                                    </span>
                                ))}
                            </div>
                        </section>

                        <OrderList
                            title="Needs attention"
                            caption="Oldest open orders first"
                            orders={needsAttention}
                            empty="Nothing is waiting — the queue is clear."
                        />

                        <OrderList
                            title="Recent orders"
                            caption="Newest first"
                            orders={recentOrders}
                            empty="No orders yet."
                        />
                    </div>

                    <div className="space-y-4">
                        <section className="rounded-lg border border-border bg-card p-4">
                            <h2 className="text-sm font-semibold text-foreground">
                                Order book
                            </h2>

                            <ul className="mt-4 space-y-3">
                                {statusBreakdown.map((slice) => {
                                    const share =
                                        totalOrders === 0
                                            ? 0
                                            : (slice.count / totalOrders) * 100;

                                    return (
                                        <li key={slice.value}>
                                            <Link
                                                href={orders.index({
                                                    query: {
                                                        status: slice.value,
                                                    },
                                                })}
                                                className="flex items-center justify-between gap-3 text-sm"
                                            >
                                                <span className="text-foreground">
                                                    {slice.label}
                                                </span>
                                                <span className="font-mono text-xs text-muted-foreground tabular-nums">
                                                    {slice.count} ·{' '}
                                                    {share.toFixed(0)}%
                                                </span>
                                            </Link>
                                            <div className="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className="h-full rounded-full bg-primary/70"
                                                    style={{
                                                        width: `${share}%`,
                                                    }}
                                                />
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        </section>

                        <section className="rounded-lg border border-border bg-card p-4">
                            <h2 className="text-sm font-semibold text-foreground">
                                Top customers
                            </h2>

                            <ul className="mt-3 divide-y divide-border">
                                {topCustomers.map((customer) => (
                                    <li
                                        key={customer.email}
                                        className="flex items-center justify-between gap-3 py-2.5"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-foreground">
                                                {customer.name}
                                            </p>
                                            <p className="truncate font-mono text-xs text-muted-foreground">
                                                {customer.orders_count} order
                                                {customer.orders_count === 1
                                                    ? ''
                                                    : 's'}
                                            </p>
                                        </div>
                                        <span className="shrink-0 font-mono text-sm text-foreground tabular-nums">
                                            {formatMoney(
                                                customer.revenue_cents,
                                            )}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}

function Metric({
    label,
    value,
    meta,
}: {
    label: string;
    value: string;
    meta: string;
}) {
    return (
        <div className="rounded-lg border border-border bg-card p-4">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-mono text-2xl font-semibold text-foreground tabular-nums">
                {value}
            </dd>
            <p className="mt-0.5 truncate text-[11px] text-muted-foreground">
                {meta}
            </p>
        </div>
    );
}

function OrderList({
    title,
    caption,
    orders: rows,
    empty,
}: {
    title: string;
    caption: string;
    orders: OrderListItem[];
    empty: string;
}) {
    return (
        <section className="rounded-lg border border-border bg-card">
            <header className="flex items-baseline justify-between gap-3 border-b border-border px-4 py-3">
                <h2 className="text-sm font-semibold text-foreground">
                    {title}
                </h2>
                <span className="text-xs text-muted-foreground">{caption}</span>
            </header>

            {rows.length === 0 ? (
                <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                    {empty}
                </p>
            ) : (
                <ul className="divide-y divide-border">
                    {rows.map((order) => (
                        <li key={order.id}>
                            <Link
                                href={orders.show(order.id)}
                                className="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
                            >
                                <span className="w-28 shrink-0 font-mono text-sm font-medium text-foreground">
                                    {order.order_number}
                                </span>

                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm text-foreground">
                                        {order.customer_name}
                                    </span>
                                    <span className="block font-mono text-xs text-muted-foreground">
                                        {dayFormatter.format(
                                            new Date(order.placed_at),
                                        )}
                                    </span>
                                </span>

                                <OrderStatusBadge
                                    label={order.status_label}
                                    badgeClass={order.status_badge_class}
                                />

                                <span className="w-24 shrink-0 text-right font-mono text-sm text-foreground tabular-nums">
                                    {formatMoney(
                                        order.total_cents,
                                        order.currency,
                                    )}
                                </span>

                                <ArrowUpRight
                                    size={14}
                                    aria-hidden="true"
                                    className="shrink-0 text-muted-foreground"
                                />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
