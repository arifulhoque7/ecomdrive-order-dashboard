import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import orders from '@/actions/App/Http/Controllers/OrderController';
import { OrderFilters } from '@/components/orders/order-filters';
import { OrderSummaryCards } from '@/components/orders/order-summary-cards';
import { OrdersEmpty } from '@/components/orders/orders-empty';
import { OrdersTable } from '@/components/orders/orders-table';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useOrderFilters } from '@/hooks/use-order-filters';
import type {
    OrderFilters as Filters,
    OrderListItem,
    OrderSummary,
    Paginated,
    StatusCounts,
    StatusOption,
} from '@/types/orders';

const PAGE_SIZES = ['10', '20', '50', '100'];

type Props = {
    orders: Paginated<OrderListItem>;
    filters: Filters;
    summary: OrderSummary;
    statusCounts: StatusCounts;
    statuses: StatusOption[];
};

export default function OrdersIndex({
    orders: page,
    filters,
    summary,
    statusCounts,
    statuses,
}: Props) {
    const applyFilters = useOrderFilters(filters);
    const { meta } = page;

    const today = new Intl.DateTimeFormat('en-US', {
        dateStyle: 'long',
    }).format(new Date());

    const hasFilters = Boolean(
        filters.q || filters.status || filters.date_from || filters.date_to,
    );

    return (
        <>
            <Head title="Orders" />

            <section className="mx-auto w-full max-w-full p-4">
                <header className="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-foreground">
                            Orders
                        </h1>
                        <p className="text-sm text-muted-foreground">{today}</p>
                    </div>

                    <Button asChild>
                        <Link href={orders.create()}>
                            <Plus size={16} aria-hidden="true" />
                            New order
                        </Link>
                    </Button>
                </header>

                <OrderSummaryCards summary={summary} />

                <div className="rounded-lg border border-border bg-card">
                    <OrderFilters
                        filters={filters}
                        statuses={statuses}
                        counts={statusCounts}
                        onChange={applyFilters}
                    />

                    {page.data.length === 0 ? (
                        <OrdersEmpty
                            hasFilters={hasFilters}
                            onClear={() =>
                                applyFilters({
                                    q: null,
                                    status: null,
                                    date_from: null,
                                    date_to: null,
                                })
                            }
                        />
                    ) : (
                        <OrdersTable
                            rows={page.data}
                            filters={filters}
                            onSort={applyFilters}
                        />
                    )}

                    <footer className="flex flex-wrap items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm text-muted-foreground">
                        <span className="font-mono text-xs tabular-nums">
                            Showing {meta.from ?? 0}–{meta.to ?? 0} of{' '}
                            {meta.total}
                        </span>

                        <div className="flex items-center gap-3">
                            <label className="flex items-center gap-2">
                                <span className="text-xs">Rows per page</span>
                                <Select
                                    value={filters.per_page}
                                    onValueChange={(value) =>
                                        applyFilters({ per_page: value })
                                    }
                                >
                                    <SelectTrigger
                                        aria-label="Rows per page"
                                        className="h-8 w-20 cursor-pointer rounded-md border border-border bg-card text-xs font-medium focus:border-primary"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent align="start">
                                        {PAGE_SIZES.map((size) => (
                                            <SelectItem key={size} value={size}>
                                                {size}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </label>

                            <div className="inline-flex items-center gap-1">
                                <button
                                    type="button"
                                    aria-label="Previous page"
                                    disabled={meta.current_page <= 1}
                                    onClick={() =>
                                        applyFilters({
                                            page: meta.current_page - 1,
                                        })
                                    }
                                    className="inline-flex size-8 cursor-pointer items-center justify-center rounded-md border border-border bg-card text-foreground hover:bg-muted disabled:cursor-default disabled:opacity-40"
                                >
                                    <ChevronLeft size={14} aria-hidden="true" />
                                </button>
                                <span className="min-w-20 px-2 text-center font-mono text-xs font-medium text-foreground">
                                    {meta.current_page} of{' '}
                                    {Math.max(meta.last_page, 1)}
                                </span>
                                <button
                                    type="button"
                                    aria-label="Next page"
                                    disabled={
                                        meta.current_page >= meta.last_page
                                    }
                                    onClick={() =>
                                        applyFilters({
                                            page: meta.current_page + 1,
                                        })
                                    }
                                    className="inline-flex size-8 cursor-pointer items-center justify-center rounded-md border border-border bg-card text-foreground hover:bg-muted disabled:cursor-default disabled:opacity-40"
                                >
                                    <ChevronRight
                                        size={14}
                                        aria-hidden="true"
                                    />
                                </button>
                            </div>
                        </div>
                    </footer>
                </div>
            </section>
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Orders',
            href: orders.index(),
        },
    ],
};
