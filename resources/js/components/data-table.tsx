import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import type { ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

/**
 * The order table's metrics, extracted so every table in the app reads the
 * same: 40px header row, 72px body rows, 12px uppercase headers, and edge
 * cells that breathe at px-4 while the middle stays at px-2.
 */
export function DataTable({
    label,
    caption,
    className,
    children,
}: {
    label: string;
    caption?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className="overflow-x-auto bg-card">
            <table
                className={cn('w-full text-left', className)}
                role="grid"
                aria-label={label}
            >
                {caption ? (
                    <caption className="sr-only">{caption}</caption>
                ) : null}
                {children}
            </table>
        </div>
    );
}

export function THead({ children }: { children: ReactNode }) {
    return (
        <thead className="border-b border-border bg-card">
            <tr className="h-10">{children}</tr>
        </thead>
    );
}

type SortState = {
    active: boolean;
    direction: 'asc' | 'desc';
    onSort: () => void;
};

export function Th({
    children,
    align,
    edge,
    sort,
    className,
    ...props
}: ThHTMLAttributes<HTMLTableCellElement> & {
    align?: 'right';
    edge?: 'start' | 'end';
    sort?: SortState;
}) {
    const ariaSort = sort
        ? sort.active
            ? sort.direction === 'asc'
                ? 'ascending'
                : 'descending'
            : 'none'
        : undefined;

    return (
        <th
            scope="col"
            aria-sort={ariaSort}
            className={cn(
                'px-2 text-[12px] leading-[1.4] font-normal tracking-normal whitespace-nowrap text-muted-foreground uppercase',
                edge === 'start' && 'px-4',
                edge === 'end' && 'pr-4',
                align === 'right' && 'text-right',
                className,
            )}
            {...props}
        >
            {sort ? (
                <button
                    type="button"
                    onClick={sort.onSort}
                    className="inline-flex cursor-pointer items-center gap-1 uppercase hover:text-foreground"
                >
                    {children}
                    {!sort.active ? (
                        <ArrowUpDown size={12} aria-hidden="true" />
                    ) : sort.direction === 'asc' ? (
                        <ArrowUp size={12} aria-hidden="true" />
                    ) : (
                        <ArrowDown size={12} aria-hidden="true" />
                    )}
                </button>
            ) : (
                <span className="uppercase">{children}</span>
            )}
        </th>
    );
}

export function Tr({
    children,
    className,
    onClick,
    dense,
}: {
    children: ReactNode;
    className?: string;
    onClick?: () => void;
    dense?: boolean;
}) {
    return (
        <tr
            onClick={onClick}
            className={cn(
                'group border-b border-border bg-card last:border-b-0 hover:bg-muted/40',
                dense ? 'h-14' : 'h-18',
                onClick && 'cursor-pointer',
                className,
            )}
        >
            {children}
        </tr>
    );
}

export function Td({
    children,
    align,
    edge,
    className,
    ...props
}: TdHTMLAttributes<HTMLTableCellElement> & {
    align?: 'right';
    edge?: 'start' | 'end';
}) {
    return (
        <td
            className={cn(
                'px-2 align-middle text-sm text-foreground',
                edge === 'start' && 'px-4',
                edge === 'end' && 'pr-4 pl-2',
                align === 'right' && 'text-right',
                className,
            )}
            {...props}
        >
            {children}
        </td>
    );
}
