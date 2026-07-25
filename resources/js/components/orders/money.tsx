import { cn } from '@/lib/utils';

/**
 * Money is the data font: mono with tabular figures so every column of
 * currency lines up digit for digit.
 */
export function formatMoney(cents: number, currency = 'USD'): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(cents / 100);
}

export function Money({
    cents,
    currency = 'USD',
    className,
}: {
    cents: number;
    currency?: string;
    className?: string;
}) {
    return (
        <span className={cn('font-mono tabular-nums', className)}>
            {formatMoney(cents, currency)}
        </span>
    );
}
