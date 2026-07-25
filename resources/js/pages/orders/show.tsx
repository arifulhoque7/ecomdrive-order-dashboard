import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MapPin, Phone, StickyNote, User } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import orders from '@/actions/App/Http/Controllers/OrderController';
import { AiInsightCard } from '@/components/orders/ai-insight-card';
import { OrderActivityTimeline } from '@/components/orders/order-activity-timeline';
import { OrderItemsTable } from '@/components/orders/order-items-table';
import { OrderStatusBadge } from '@/components/orders/order-status-badge';
import { OrderStatusSelect } from '@/components/orders/order-status-select';
import { OrderTotals } from '@/components/orders/order-totals';
import type { OrderDetail } from '@/types/orders';

function DetailCard({
    title,
    children,
    className,
}: {
    title: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            className={`rounded-lg border border-border bg-card p-6 shadow-sm ${className ?? ''}`}
        >
            <h2 className="text-lg font-semibold text-foreground">{title}</h2>
            <div className="mt-4 mb-4 h-px w-full bg-border" />
            {children}
        </section>
    );
}

function DetailItem({
    icon: Icon,
    label,
    value,
}: {
    icon: LucideIcon;
    label: string;
    value: string | null;
}) {
    return (
        <div className="flex items-start gap-2.5">
            <span className="mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                <Icon size={14} aria-hidden="true" />
            </span>
            <div className="flex min-w-0 flex-col gap-0.5">
                <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {label}
                </dt>
                <dd className="text-sm break-words text-foreground">
                    {value && value.trim() !== '' ? value : '—'}
                </dd>
            </div>
        </div>
    );
}

export default function OrderShow({ order }: { order: OrderDetail }) {
    const placedAt = new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(order.placed_at));

    const address = order.shipping_address
        ? Object.values(order.shipping_address).join(', ')
        : null;

    return (
        <>
            <Head title={`Order ${order.order_number}`} />

            <section className="mx-auto w-full max-w-full p-4">
                <Link
                    href={orders.index()}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft size={14} aria-hidden="true" />
                    Back to orders
                </Link>

                <header className="mt-4 mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="font-mono text-2xl leading-8 font-bold text-foreground">
                                {order.order_number}
                            </h1>
                            <OrderStatusBadge
                                label={order.status_label}
                                badgeClass={order.status_badge_class}
                            />
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Placed{' '}
                            <span className="font-mono tabular-nums">
                                {placedAt}
                            </span>{' '}
                            by {order.customer.name}
                        </p>
                    </div>

                    <OrderStatusSelect order={order} />
                </header>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <DetailCard title="Items">
                            <OrderItemsTable
                                items={order.items}
                                currency={order.currency}
                            />
                            <OrderTotals
                                totals={order.totals}
                                currency={order.currency}
                            />
                        </DetailCard>

                        <DetailCard title="Activity">
                            <OrderActivityTimeline
                                activities={order.activities}
                            />
                        </DetailCard>
                    </div>

                    <div className="space-y-6">
                        <DetailCard title="Customer">
                            <dl className="grid grid-cols-1 gap-x-6 gap-y-5">
                                <DetailItem
                                    icon={User}
                                    label="Name"
                                    value={order.customer.name}
                                />
                                <DetailItem
                                    icon={StickyNote}
                                    label="Email"
                                    value={order.customer.email}
                                />
                                <DetailItem
                                    icon={Phone}
                                    label="Phone"
                                    value={order.customer.phone}
                                />
                                <DetailItem
                                    icon={MapPin}
                                    label="Shipping address"
                                    value={address}
                                />
                                <DetailItem
                                    icon={StickyNote}
                                    label="Operator notes"
                                    value={order.notes}
                                />
                            </dl>
                        </DetailCard>

                        <AiInsightCard order={order} />
                    </div>
                </div>
            </section>
        </>
    );
}

OrderShow.layout = {
    breadcrumbs: [
        {
            title: 'Orders',
            href: orders.index(),
        },
    ],
};
