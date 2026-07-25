import { cn } from '@/lib/utils';

/**
 * Light status pill. The colour contract lives on the OrderStatus enum, so the
 * server and the client can never disagree about what "shipped" looks like.
 */
export function OrderStatusBadge({
    label,
    badgeClass,
    className,
}: {
    label: string;
    badgeClass: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium',
                badgeClass,
                className,
            )}
        >
            {label}
        </span>
    );
}
