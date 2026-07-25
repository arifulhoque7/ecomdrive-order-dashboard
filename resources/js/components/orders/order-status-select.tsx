import { router } from '@inertiajs/react';
import { useState } from 'react';
import status from '@/actions/App/Http/Controllers/OrderStatusController';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { OrderDetail } from '@/types/orders';

/**
 * Only the transitions the order's current status allows are offered, so an
 * illegal move is impossible to pick rather than merely rejected server-side.
 */
export function OrderStatusSelect({ order }: { order: OrderDetail }) {
    const [pending, setPending] = useState(false);

    if (order.allowed_transitions.length === 0) {
        return (
            <p className="text-xs text-muted-foreground">
                {order.status_label} is a final status.
            </p>
        );
    }

    return (
        <Select
            disabled={pending}
            value=""
            onValueChange={(value) => {
                setPending(true);

                router.patch(
                    status.update.url(order.id),
                    { status: value },
                    {
                        preserveScroll: true,
                        onFinish: () => setPending(false),
                    },
                );
            }}
        >
            <SelectTrigger
                aria-label="Move order to another status"
                className="h-10 w-56 border-border bg-card text-sm"
            >
                <SelectValue placeholder="Move to…" />
            </SelectTrigger>
            <SelectContent>
                {order.allowed_transitions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
