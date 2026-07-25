import { Search } from 'lucide-react';
import type { ReactNode } from 'react';
import { Input } from '@/components/ui/input';
import { UnderlineTabs } from '@/components/underline-tabs';
import type { TabItem } from '@/components/underline-tabs';

/**
 * The filter row that sits at the top of every table card: tabs on the left,
 * search and any extra controls on the right.
 */
export function TableToolbar({
    tabs,
    activeTab,
    tabsLabel,
    onSelectTab,
    search,
    onSearch,
    searchPlaceholder = 'Search',
    children,
}: {
    tabs?: TabItem[];
    activeTab?: string | null;
    tabsLabel?: string;
    onSelectTab?: (value: string | null) => void;
    search: string;
    onSearch: (value: string) => void;
    searchPlaceholder?: string;
    children?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 pt-3 pb-2">
            {tabs && onSelectTab ? (
                <UnderlineTabs
                    items={tabs}
                    active={activeTab ?? null}
                    label={tabsLabel ?? 'Filter'}
                    onSelect={onSelectTab}
                />
            ) : (
                <span className="h-11" />
            )}

            <div className="flex items-center gap-3">
                <div className="relative">
                    <Search
                        size={16}
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        type="search"
                        value={search}
                        onChange={(event) => onSearch(event.target.value)}
                        placeholder={searchPlaceholder}
                        className="h-9 w-60 rounded-md border-border pl-9 text-sm"
                    />
                </div>

                {children}
            </div>
        </div>
    );
}

export function TableFooter({ children }: { children: ReactNode }) {
    return (
        <footer className="flex flex-wrap items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm text-muted-foreground">
            {children}
        </footer>
    );
}
