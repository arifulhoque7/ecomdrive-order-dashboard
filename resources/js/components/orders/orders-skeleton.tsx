import { Skeleton } from '@/components/ui/skeleton';

export function OrdersSkeleton({ rows = 8 }: { rows?: number }) {
    return (
        <div
            role="status"
            aria-busy="true"
            aria-live="polite"
            className="divide-y divide-border"
        >
            <span className="sr-only">Loading orders</span>
            {Array.from({ length: rows }).map((_, index) => (
                <div key={index} className="flex items-center gap-4 px-6 py-4">
                    <Skeleton className="h-3.5 w-28" />
                    <Skeleton className="h-3.5 flex-1" />
                    <Skeleton className="hidden h-3.5 w-24 md:block" />
                    <Skeleton className="h-5 w-16 rounded-md" />
                    <Skeleton className="hidden h-3.5 w-24 sm:block" />
                </div>
            ))}
        </div>
    );
}
