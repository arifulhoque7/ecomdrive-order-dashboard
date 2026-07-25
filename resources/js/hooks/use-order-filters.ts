import { router } from '@inertiajs/react';
import { useCallback } from 'react';
import orders from '@/actions/App/Http/Controllers/OrderController';
import type { OrderFilters } from '@/types/orders';

type FilterPatch = Partial<OrderFilters> & { page?: number };

/**
 * Every filter lives in the query string, so the browser back button and a
 * pasted URL both reproduce exactly what the operator was looking at.
 */
export function useOrderFilters(filters: OrderFilters) {
    return useCallback(
        (patch: FilterPatch) => {
            const merged: Record<string, string | number> = {};

            Object.entries({ ...filters, page: 1, ...patch }).forEach(
                ([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        merged[key] = value as string | number;
                    }
                },
            );

            router.get(
                orders.index.url({ query: merged }),
                {},
                { preserveState: true, preserveScroll: true, replace: true },
            );
        },
        [filters],
    );
}
