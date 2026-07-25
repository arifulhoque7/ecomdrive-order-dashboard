import { PackageSearch } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function OrdersEmpty({
    hasFilters,
    onClear,
}: {
    hasFilters: boolean;
    onClear: () => void;
}) {
    return (
        <div className="flex flex-col items-center px-6 py-16 text-center">
            <span className="inline-flex size-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                <PackageSearch size={28} aria-hidden="true" />
            </span>

            <h2 className="mt-8 text-2xl leading-8 font-bold text-foreground">
                {hasFilters ? 'No orders match your filters' : 'No orders yet'}
            </h2>
            <p className="mt-3 max-w-md text-sm leading-6 text-muted-foreground">
                {hasFilters
                    ? 'Try a different search term, widen the date range, or clear the active filters.'
                    : 'Orders will appear here as soon as customers start checking out.'}
            </p>

            {hasFilters ? (
                <Button
                    type="button"
                    variant="outline"
                    onClick={onClear}
                    className="mt-6"
                >
                    Clear filters
                </Button>
            ) : null}
        </div>
    );
}
