import { cn } from '@/lib/utils';

export type TabItem = {
    value: string | null;
    label: string;
    count?: number;
};

/**
 * The filter row's tabs: underline rather than pills, so the row reads as
 * navigation instead of competing with the search field beside it.
 */
export function UnderlineTabs({
    items,
    active,
    label,
    onSelect,
}: {
    items: TabItem[];
    active: string | null;
    label: string;
    onSelect: (value: string | null) => void;
}) {
    return (
        <div
            role="tablist"
            aria-label={label}
            className="-mb-2 flex max-w-full min-w-0 flex-1 scrollbar-none items-stretch overflow-x-auto pb-2"
        >
            {items.map((item) => {
                const selected = item.value === active;

                return (
                    <button
                        key={item.value ?? 'all'}
                        type="button"
                        role="tab"
                        aria-selected={selected}
                        onClick={() => onSelect(item.value)}
                        className={cn(
                            'relative inline-flex h-11 shrink-0 cursor-pointer items-center gap-1.5 px-4 text-sm font-medium whitespace-nowrap transition-colors',
                            selected
                                ? 'text-primary'
                                : 'text-foreground hover:text-primary',
                        )}
                    >
                        <span>{item.label}</span>
                        {item.count === undefined ? null : (
                            <span className="font-mono font-normal text-muted-foreground">
                                ({item.count})
                            </span>
                        )}
                        <span
                            aria-hidden="true"
                            className={cn(
                                'absolute inset-x-0 -bottom-2 h-0.5',
                                selected ? 'bg-primary' : 'bg-transparent',
                            )}
                        />
                    </button>
                );
            })}
        </div>
    );
}
