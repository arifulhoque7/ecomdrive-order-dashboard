import { UnderlineTabs } from '@/components/underline-tabs';
import type { OrderFilters, StatusCounts, StatusOption } from '@/types/orders';

export function OrderStatusTabs({
    statuses,
    counts,
    filters,
    onSelect,
}: {
    statuses: StatusOption[];
    counts: StatusCounts;
    filters: OrderFilters;
    onSelect: (status: string | null) => void;
}) {
    return (
        <UnderlineTabs
            label="Order status"
            active={filters.status}
            onSelect={onSelect}
            items={[
                { value: null, label: 'All', count: counts.all ?? 0 },
                ...statuses.map((status) => ({
                    value: status.value,
                    label: status.label,
                    count: counts[status.value] ?? 0,
                })),
            ]}
        />
    );
}
