import { cn } from '@/lib/utils';
import type { OrderActivity } from '@/types/orders';

const timestamp = new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export function OrderActivityTimeline({
    activities,
}: {
    activities: OrderActivity[];
}) {
    return (
        <ol className="relative pl-6">
            <span
                aria-hidden="true"
                className="absolute top-2 bottom-2 left-2 w-px bg-border"
            />

            {activities.map((activity) => (
                <li key={activity.id} className="relative py-3">
                    <span
                        aria-hidden="true"
                        className={cn(
                            'absolute top-4.5 -left-[1.125rem] size-2 rounded-full',
                            activity.type === 'status_changed'
                                ? 'bg-primary'
                                : 'bg-muted-foreground/40',
                        )}
                    />
                    <p className="text-sm text-foreground">
                        {activity.description}
                    </p>
                    <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                        {timestamp.format(new Date(activity.created_at))}
                        {activity.actor ? ` · ${activity.actor}` : ' · system'}
                    </p>
                </li>
            ))}
        </ol>
    );
}
