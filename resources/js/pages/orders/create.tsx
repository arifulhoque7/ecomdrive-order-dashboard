import { Head, useForm } from '@inertiajs/react';
import { Minus, Plus, Search, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import orders from '@/actions/App/Http/Controllers/OrderController';
import { formatMoney, Money } from '@/components/orders/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type Product = {
    id: number;
    sku: string;
    name: string;
    category: string;
    price_cents: number;
    image_url: string;
};

type CustomerOption = {
    id: number;
    name: string;
    email: string;
};

type Pricing = {
    free_shipping_threshold_cents: number;
    flat_shipping_cents: number;
    tax_rate: number;
};

type Line = { product_id: number; quantity: number };

type OrderForm = {
    customer_id: number | null;
    customer: { name: string; email: string; phone: string; city: string };
    items: Line[];
    discount_cents: number;
    notes: string;
};

const NEW_CUSTOMER = 'new';

export default function OrdersCreate({
    products,
    customers,
    pricing,
}: {
    products: Product[];
    customers: CustomerOption[];
    pricing: Pricing;
}) {
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState<string | null>(null);

    const form = useForm<OrderForm>({
        customer_id: null,
        customer: { name: '', email: '', phone: '', city: '' },
        items: [],
        discount_cents: 0,
        notes: '',
    });

    const { data, setData, errors, processing } = form;

    const categories = useMemo(() => {
        const counts = new Map<string, number>();
        products.forEach((product) =>
            counts.set(
                product.category,
                (counts.get(product.category) ?? 0) + 1,
            ),
        );

        return Array.from(counts, ([name, count]) => ({ name, count })).sort(
            (a, b) => a.name.localeCompare(b.name),
        );
    }, [products]);

    const visible = products.filter((product) => {
        const matchesCategory =
            category === null || product.category === category;
        const term = search.trim().toLowerCase();
        const matchesSearch =
            term === '' ||
            product.name.toLowerCase().includes(term) ||
            product.sku.toLowerCase().includes(term);

        return matchesCategory && matchesSearch;
    });

    const productsById = useMemo(
        () => new Map(products.map((product) => [product.id, product])),
        [products],
    );

    const addLine = (productId: number) => {
        const existing = data.items.find(
            (line) => line.product_id === productId,
        );

        setData(
            'items',
            existing
                ? data.items.map((line) =>
                      line.product_id === productId
                          ? {
                                ...line,
                                quantity: Math.min(line.quantity + 1, 99),
                            }
                          : line,
                  )
                : [...data.items, { product_id: productId, quantity: 1 }],
        );
    };

    const setQuantity = (productId: number, quantity: number) => {
        setData(
            'items',
            quantity <= 0
                ? data.items.filter((line) => line.product_id !== productId)
                : data.items.map((line) =>
                      line.product_id === productId
                          ? { ...line, quantity: Math.min(quantity, 99) }
                          : line,
                  ),
        );
    };

    // Mirrors RecalculateOrderTotals so the counter shows what the server will charge.
    const totals = useMemo(() => {
        const subtotal = data.items.reduce(
            (sum, line) =>
                sum +
                (productsById.get(line.product_id)?.price_cents ?? 0) *
                    line.quantity,
            0,
        );
        const discount = Math.min(data.discount_cents || 0, subtotal);
        const net = subtotal - discount;
        const shipping =
            net >= pricing.free_shipping_threshold_cents
                ? 0
                : pricing.flat_shipping_cents;
        const tax = Math.round(net * pricing.tax_rate);

        return {
            subtotal,
            discount,
            shipping,
            tax,
            total: net + shipping + tax,
        };
    }, [data.items, data.discount_cents, productsById, pricing]);

    const submit = () =>
        form.post(orders.store.url(), { preserveScroll: true });

    return (
        <>
            <Head title="New order" />

            <div className="flex h-full flex-col gap-4 p-4 xl:flex-row">
                <section className="min-w-0 flex-1">
                    <header className="mb-4 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h1 className="text-xl font-semibold text-foreground">
                                New order
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Pick products, choose the customer, place the
                                order.
                            </p>
                        </div>

                        <div className="relative">
                            <Search
                                size={16}
                                aria-hidden="true"
                                className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search products"
                                className="h-9 w-60 rounded-md border-border pl-9 text-sm"
                            />
                        </div>
                    </header>

                    <div className="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        <CategoryCard
                            label="All products"
                            count={products.length}
                            active={category === null}
                            onSelect={() => setCategory(null)}
                        />
                        {categories.map((entry) => (
                            <CategoryCard
                                key={entry.name}
                                label={entry.name}
                                count={entry.count}
                                active={category === entry.name}
                                onSelect={() => setCategory(entry.name)}
                            />
                        ))}
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 2xl:grid-cols-4">
                        {visible.map((product) => {
                            const line = data.items.find(
                                (item) => item.product_id === product.id,
                            );

                            return (
                                <button
                                    key={product.id}
                                    type="button"
                                    onClick={() => addLine(product.id)}
                                    className={cn(
                                        'group cursor-pointer overflow-hidden rounded-lg border bg-card text-left transition-colors',
                                        line
                                            ? 'border-primary ring-1 ring-primary'
                                            : 'border-border hover:border-foreground/20',
                                    )}
                                >
                                    <div className="relative aspect-4/3 overflow-hidden bg-muted">
                                        <img
                                            src={product.image_url}
                                            alt=""
                                            loading="lazy"
                                            className="size-full object-cover"
                                        />
                                        {line ? (
                                            <span className="absolute top-2 right-2 inline-flex size-6 items-center justify-center rounded-md bg-primary font-mono text-xs font-medium text-primary-foreground">
                                                {line.quantity}
                                            </span>
                                        ) : null}
                                    </div>
                                    <div className="p-3">
                                        <p className="truncate text-sm font-medium text-foreground">
                                            {product.name}
                                        </p>
                                        <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                            {product.sku}
                                        </p>
                                        <p className="mt-1 font-mono text-sm font-semibold text-foreground tabular-nums">
                                            {formatMoney(product.price_cents)}
                                        </p>
                                    </div>
                                </button>
                            );
                        })}
                    </div>

                    {visible.length === 0 ? (
                        <p className="rounded-lg border border-border bg-card px-4 py-10 text-center text-sm text-muted-foreground">
                            No products match that search.
                        </p>
                    ) : null}
                </section>

                <aside className="flex w-full shrink-0 flex-col gap-4 xl:w-96">
                    <div className="rounded-lg border border-border bg-card p-4">
                        <h2 className="text-sm font-semibold text-foreground">
                            Customer
                        </h2>

                        <Select
                            value={
                                data.customer_id === null
                                    ? NEW_CUSTOMER
                                    : String(data.customer_id)
                            }
                            onValueChange={(value) =>
                                setData(
                                    'customer_id',
                                    value === NEW_CUSTOMER
                                        ? null
                                        : Number(value),
                                )
                            }
                        >
                            <SelectTrigger
                                aria-label="Customer"
                                className="mt-3 h-10 w-full border-border bg-background text-sm"
                            >
                                <SelectValue placeholder="Select customer" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NEW_CUSTOMER}>
                                    + New customer
                                </SelectItem>
                                {customers.map((customer) => (
                                    <SelectItem
                                        key={customer.id}
                                        value={String(customer.id)}
                                    >
                                        {customer.name} — {customer.email}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {data.customer_id === null ? (
                            <div className="mt-3 grid gap-3">
                                <Field
                                    label="Name"
                                    value={data.customer.name}
                                    error={errors['customer.name']}
                                    onChange={(value) =>
                                        setData('customer', {
                                            ...data.customer,
                                            name: value,
                                        })
                                    }
                                />
                                <Field
                                    label="Email"
                                    type="email"
                                    value={data.customer.email}
                                    error={errors['customer.email']}
                                    onChange={(value) =>
                                        setData('customer', {
                                            ...data.customer,
                                            email: value,
                                        })
                                    }
                                />
                                <div className="grid grid-cols-2 gap-3">
                                    <Field
                                        label="Phone"
                                        value={data.customer.phone}
                                        error={errors['customer.phone']}
                                        onChange={(value) =>
                                            setData('customer', {
                                                ...data.customer,
                                                phone: value,
                                            })
                                        }
                                    />
                                    <Field
                                        label="City"
                                        value={data.customer.city}
                                        error={errors['customer.city']}
                                        onChange={(value) =>
                                            setData('customer', {
                                                ...data.customer,
                                                city: value,
                                            })
                                        }
                                    />
                                </div>
                            </div>
                        ) : null}

                        {errors.customer_id ? (
                            <p className="mt-2 text-xs text-destructive">
                                {errors.customer_id}
                            </p>
                        ) : null}
                    </div>

                    <div className="flex min-h-0 flex-1 flex-col rounded-lg border border-border bg-card">
                        <div className="flex items-center justify-between border-b border-border px-4 py-3">
                            <h2 className="text-sm font-semibold text-foreground">
                                Order
                            </h2>
                            <span className="font-mono text-xs text-muted-foreground">
                                {data.items.length} line
                                {data.items.length === 1 ? '' : 's'}
                            </span>
                        </div>

                        {data.items.length === 0 ? (
                            <p className="px-4 py-10 text-center text-sm text-muted-foreground">
                                Tap a product to start the order.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border">
                                {data.items.map((line) => {
                                    const product = productsById.get(
                                        line.product_id,
                                    );

                                    if (!product) {
                                        return null;
                                    }

                                    return (
                                        <li
                                            key={line.product_id}
                                            className="flex items-center gap-3 px-4 py-3"
                                        >
                                            <img
                                                src={product.image_url}
                                                alt=""
                                                loading="lazy"
                                                className="size-10 shrink-0 rounded-md object-cover"
                                            />

                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium text-foreground">
                                                    {product.name}
                                                </p>
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {formatMoney(
                                                        product.price_cents,
                                                    )}
                                                </p>
                                            </div>

                                            <div className="inline-flex items-center gap-1">
                                                <QuantityButton
                                                    label="Decrease quantity"
                                                    onClick={() =>
                                                        setQuantity(
                                                            line.product_id,
                                                            line.quantity - 1,
                                                        )
                                                    }
                                                >
                                                    <Minus
                                                        size={12}
                                                        aria-hidden="true"
                                                    />
                                                </QuantityButton>
                                                <span className="w-6 text-center font-mono text-sm tabular-nums">
                                                    {line.quantity}
                                                </span>
                                                <QuantityButton
                                                    label="Increase quantity"
                                                    onClick={() =>
                                                        setQuantity(
                                                            line.product_id,
                                                            line.quantity + 1,
                                                        )
                                                    }
                                                >
                                                    <Plus
                                                        size={12}
                                                        aria-hidden="true"
                                                    />
                                                </QuantityButton>
                                            </div>

                                            <span className="w-20 text-right font-mono text-sm font-medium tabular-nums">
                                                {formatMoney(
                                                    product.price_cents *
                                                        line.quantity,
                                                )}
                                            </span>

                                            <button
                                                type="button"
                                                aria-label="Remove line"
                                                onClick={() =>
                                                    setQuantity(
                                                        line.product_id,
                                                        0,
                                                    )
                                                }
                                                className="cursor-pointer text-muted-foreground transition-colors hover:text-destructive"
                                            >
                                                <Trash2
                                                    size={14}
                                                    aria-hidden="true"
                                                />
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}

                        {errors.items ? (
                            <p className="px-4 pb-3 text-xs text-destructive">
                                {errors.items}
                            </p>
                        ) : null}
                    </div>

                    <div className="rounded-lg border border-border bg-card p-4">
                        <div className="flex items-center justify-between gap-3">
                            <Label
                                htmlFor="discount"
                                className="text-xs text-muted-foreground"
                            >
                                Discount (cents)
                            </Label>
                            <Input
                                id="discount"
                                type="number"
                                min={0}
                                value={data.discount_cents}
                                onChange={(event) =>
                                    setData(
                                        'discount_cents',
                                        Number(event.target.value) || 0,
                                    )
                                }
                                className="h-10 w-32 border-border bg-background text-right font-mono text-sm"
                            />
                        </div>

                        <Textarea
                            value={data.notes}
                            onChange={(event) =>
                                setData('notes', event.target.value)
                            }
                            placeholder="Notes for the fulfilment team (optional)"
                            rows={2}
                            className="mt-3 text-sm"
                        />

                        <dl className="mt-4 space-y-1 border-t border-border pt-3 text-sm">
                            <Row label="Subtotal" cents={totals.subtotal} />
                            <Row label="Discount" cents={-totals.discount} />
                            <Row
                                label={
                                    totals.shipping === 0
                                        ? 'Shipping (free)'
                                        : 'Shipping'
                                }
                                cents={totals.shipping}
                            />
                            <Row label="Tax" cents={totals.tax} />
                            <div className="flex items-center justify-between border-t border-border pt-2 text-base font-semibold">
                                <dt>Total</dt>
                                <dd>
                                    <Money cents={totals.total} />
                                </dd>
                            </div>
                        </dl>

                        <Button
                            type="button"
                            onClick={submit}
                            disabled={processing || data.items.length === 0}
                            className="mt-4 w-full"
                        >
                            Place order
                        </Button>
                    </div>
                </aside>
            </div>
        </>
    );
}

function CategoryCard({
    label,
    count,
    active,
    onSelect,
}: {
    label: string;
    count: number;
    active: boolean;
    onSelect: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onSelect}
            className={cn(
                'cursor-pointer rounded-lg border px-4 py-3 text-left transition-colors',
                active
                    ? 'border-primary bg-primary/5 text-primary'
                    : 'border-border bg-card text-foreground hover:border-foreground/20',
            )}
        >
            <span className="block truncate text-sm font-medium">{label}</span>
            <span className="mt-0.5 block font-mono text-xs text-muted-foreground">
                {count} item{count === 1 ? '' : 's'}
            </span>
        </button>
    );
}

function QuantityButton({
    label,
    onClick,
    children,
}: {
    label: string;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            onClick={onClick}
            className="inline-flex size-6 cursor-pointer items-center justify-center rounded-md border border-border text-foreground transition-colors hover:bg-muted"
        >
            {children}
        </button>
    );
}

function Row({ label, cents }: { label: string; cents: number }) {
    return (
        <div className="flex items-center justify-between">
            <dt className="text-muted-foreground">{label}</dt>
            <dd>
                <Money cents={cents} />
            </dd>
        </div>
    );
}

function Field({
    label,
    value,
    onChange,
    error,
    type = 'text',
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    type?: string;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label className="text-sm font-medium text-foreground">
                {label}
            </Label>
            <Input
                type={type}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="h-10 w-full border-border bg-background px-4 text-sm"
            />
            {error ? <p className="text-xs text-destructive">{error}</p> : null}
        </div>
    );
}

OrdersCreate.layout = {
    breadcrumbs: [
        { title: 'Orders', href: orders.index() },
        { title: 'New order', href: orders.create() },
    ],
};
