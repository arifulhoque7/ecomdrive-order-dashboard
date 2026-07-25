import { DataTable, Td, Th, THead, Tr } from '@/components/data-table';
import { Money } from '@/components/orders/money';
import type { OrderItem } from '@/types/orders';

export function OrderItemsTable({
    items,
    currency,
}: {
    items: OrderItem[];
    currency: string;
}) {
    return (
        <DataTable label="Order items" className="min-w-xl">
            <THead>
                <Th edge="start">Item</Th>
                <Th align="right">Qty</Th>
                <Th align="right">Unit price</Th>
                <Th edge="end" align="right">
                    Line total
                </Th>
            </THead>

            <tbody>
                {items.map((item) => (
                    <Tr key={item.id} dense>
                        <Td edge="start">
                            <div className="flex items-center gap-3">
                                {item.image_url ? (
                                    <img
                                        src={item.image_url}
                                        alt=""
                                        loading="lazy"
                                        className="size-10 shrink-0 rounded-md object-cover"
                                    />
                                ) : (
                                    <span className="size-10 shrink-0 rounded-md bg-muted" />
                                )}
                                <div className="min-w-0">
                                    <span className="block truncate font-medium text-foreground">
                                        {item.name}
                                    </span>
                                    <span className="block font-mono text-xs text-muted-foreground">
                                        {item.sku}
                                    </span>
                                </div>
                            </div>
                        </Td>
                        <Td align="right" className="font-mono tabular-nums">
                            {item.quantity}
                        </Td>
                        <Td align="right">
                            <Money
                                cents={item.unit_price_cents}
                                currency={currency}
                            />
                        </Td>
                        <Td edge="end" align="right" className="font-medium">
                            <Money
                                cents={item.line_total_cents}
                                currency={currency}
                            />
                        </Td>
                    </Tr>
                ))}
            </tbody>
        </DataTable>
    );
}
