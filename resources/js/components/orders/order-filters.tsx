import { Filter, Search, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { OrderStatusTabs } from '@/components/orders/order-status-tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type {
    OrderFilters as Filters,
    StatusCounts,
    StatusOption,
} from '@/types/orders';

const SEARCH_DEBOUNCE_MS = 300;

const SORT_OPTIONS = [
    { value: 'placed_at', label: 'Date placed' },
    { value: 'total_cents', label: 'Order total' },
    { value: 'order_number', label: 'Order number' },
    { value: 'status', label: 'Status' },
];

export function OrderFilters({
    filters,
    statuses,
    counts,
    onChange,
}: {
    filters: Filters;
    statuses: StatusOption[];
    counts: StatusCounts;
    onChange: (patch: Partial<Filters>) => void;
}) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [showSecondary, setShowSecondary] = useState(false);

    const activeSecondary =
        (filters.date_from ? 1 : 0) +
        (filters.date_to ? 1 : 0) +
        (filters.sort !== 'placed_at' || filters.direction !== 'desc' ? 1 : 0);

    useEffect(() => {
        const id = window.setTimeout(() => {
            if (search === (filters.q ?? '')) {
                return;
            }

            onChange({ q: search });
        }, SEARCH_DEBOUNCE_MS);

        return () => window.clearTimeout(id);
    }, [search, filters.q, onChange]);

    const filterButtonActive = showSecondary || activeSecondary > 0;

    return (
        <div>
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 pt-3 pb-2">
                <OrderStatusTabs
                    statuses={statuses}
                    counts={counts}
                    filters={filters}
                    onSelect={(status) =>
                        onChange({ status: status as Filters['status'] })
                    }
                />

                <div className="flex items-center gap-3">
                    <div className="relative">
                        <Search
                            size={16}
                            aria-hidden="true"
                            className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            type="search"
                            placeholder="Search orders or customers"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="h-9 w-60 rounded-md border-border pl-9 text-sm"
                        />
                    </div>

                    <button
                        type="button"
                        aria-label="Toggle filters"
                        aria-pressed={filterButtonActive}
                        onClick={() =>
                            setShowSecondary((previous) => !previous)
                        }
                        className={cn(
                            'relative inline-flex size-5 cursor-pointer items-center justify-center transition-colors',
                            filterButtonActive
                                ? 'text-primary'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        <Filter
                            size={20}
                            strokeWidth={1.75}
                            aria-hidden="true"
                        />
                        {activeSecondary > 0 ? (
                            <span className="absolute -top-1.5 -right-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 font-mono text-[10px] font-medium text-primary-foreground">
                                {activeSecondary}
                            </span>
                        ) : null}
                    </button>
                </div>
            </div>

            {filterButtonActive ? (
                <div className="flex flex-wrap items-center gap-2 border-b border-border bg-muted/20 px-4 py-3">
                    <label className="flex items-center gap-2 text-xs text-muted-foreground">
                        From
                        <Input
                            type="date"
                            value={filters.date_from ?? ''}
                            max={filters.date_to ?? undefined}
                            onChange={(event) =>
                                onChange({
                                    date_from: event.target.value || null,
                                })
                            }
                            className="h-9 w-40 font-mono text-sm"
                        />
                    </label>

                    <label className="flex items-center gap-2 text-xs text-muted-foreground">
                        To
                        <Input
                            type="date"
                            value={filters.date_to ?? ''}
                            min={filters.date_from ?? undefined}
                            onChange={(event) =>
                                onChange({
                                    date_to: event.target.value || null,
                                })
                            }
                            className="h-9 w-40 font-mono text-sm"
                        />
                    </label>

                    <Select
                        value={filters.sort}
                        onValueChange={(value) => onChange({ sort: value })}
                    >
                        <SelectTrigger
                            aria-label="Sort by"
                            className="h-9 w-44 border-border bg-card text-sm"
                        >
                            <SelectValue placeholder="Sort by" />
                        </SelectTrigger>
                        <SelectContent>
                            {SORT_OPTIONS.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.direction}
                        onValueChange={(value) =>
                            onChange({
                                direction: value as Filters['direction'],
                            })
                        }
                    >
                        <SelectTrigger
                            aria-label="Sort direction"
                            className="h-9 w-36 border-border bg-card text-sm"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="desc">Newest first</SelectItem>
                            <SelectItem value="asc">Oldest first</SelectItem>
                        </SelectContent>
                    </Select>

                    {activeSecondary > 0 ? (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                onChange({
                                    date_from: null,
                                    date_to: null,
                                    sort: 'placed_at',
                                    direction: 'desc',
                                })
                            }
                            className="ml-auto border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground"
                        >
                            <X size={14} strokeWidth={2} aria-hidden="true" />
                            Clear
                        </Button>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
