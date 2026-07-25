import { router } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import orders from '@/actions/App/Http/Controllers/OrderController';
import { DataTable, Td, Th, THead, Tr } from '@/components/data-table';
import { Money } from '@/components/orders/money';
import { OrderStatusBadge } from '@/components/orders/order-status-badge';
import { cn } from '@/lib/utils';
import type { OrderFilters, OrderListItem } from '@/types/orders';

const STICKY_HEAD = 'sticky z-20 bg-card';
const STICKY_BODY = 'sticky z-10 bg-card group-hover:bg-muted/40';

const dateFormatter = new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
});

export function OrdersTable({
    rows,
    filters,
    onSort,
}: {
    rows: OrderListItem[];
    filters: OrderFilters;
    onSort: (patch: Partial<OrderFilters>) => void;
}) {
    const sortBy = (column: string) => ({
        active: filters.sort === column,
        direction: filters.direction,
        onSort: () =>
            onSort({
                sort: column,
                direction:
                    filters.sort === column && filters.direction === 'asc'
                        ? 'desc'
                        : 'asc',
            }),
    });

    return (
        <DataTable label="Orders" caption="Order list" className="min-w-4xl">
            <THead>
                <Th
                    edge="start"
                    sort={sortBy('order_number')}
                    className={cn(STICKY_HEAD, 'left-0')}
                >
                    Order
                </Th>
                <Th>Customer</Th>
                <Th>Items</Th>
                <Th align="right" sort={sortBy('total_cents')}>
                    Total
                </Th>
                <Th sort={sortBy('status')}>Status</Th>
                <Th sort={sortBy('placed_at')}>Placed</Th>
                <Th edge="end" className={cn('w-10', STICKY_HEAD, 'right-0')}>
                    <span className="sr-only">Open</span>
                </Th>
            </THead>

            <tbody>
                {rows.map((order) => (
                    <Tr
                        key={order.id}
                        onClick={() => router.visit(orders.show.url(order.id))}
                    >
                        <Td
                            edge="start"
                            className={cn(
                                'font-mono font-medium',
                                STICKY_BODY,
                                'left-0',
                            )}
                        >
                            {order.order_number}
                        </Td>
                        <Td>
                            <span className="block font-medium text-foreground">
                                {order.customer_name}
                            </span>
                            <span className="block text-xs text-muted-foreground">
                                {order.customer_email}
                            </span>
                        </Td>
                        <Td className="font-mono tabular-nums">
                            {order.items_count}
                        </Td>
                        <Td align="right">
                            <Money
                                cents={order.total_cents}
                                currency={order.currency}
                            />
                        </Td>
                        <Td>
                            <OrderStatusBadge
                                label={order.status_label}
                                badgeClass={order.status_badge_class}
                            />
                        </Td>
                        <Td className="font-mono tabular-nums">
                            {dateFormatter.format(new Date(order.placed_at))}
                        </Td>
                        <Td
                            edge="end"
                            align="right"
                            className={cn(
                                'text-muted-foreground',
                                STICKY_BODY,
                                'right-0',
                            )}
                        >
                            <ChevronRight size={16} aria-hidden="true" />
                        </Td>
                    </Tr>
                ))}
            </tbody>
        </DataTable>
    );
}
