import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import products from '@/actions/App/Http/Controllers/ProductController';
import { DataTable, Td, Th, THead, Tr } from '@/components/data-table';
import { formatMoney } from '@/components/orders/money';
import { TableFooter, TableToolbar } from '@/components/table-toolbar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

type Category = { id: number; name: string };

type ProductRow = {
    id: number;
    sku: string;
    name: string;
    image_url: string;
    price_cents: number;
    is_active: boolean;
    category_id: number;
    category_name: string;
    sold_count: number;
};

type ProductForm = {
    name: string;
    sku: string;
    category_id: string;
    price: string;
    image_url: string;
    is_active: boolean;
};

const BLANK: ProductForm = {
    name: '',
    sku: '',
    category_id: '',
    price: '',
    image_url: '',
    is_active: true,
};

export default function ProductsIndex({
    products: rows,
    categories,
}: {
    products: ProductRow[];
    categories: Category[];
}) {
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState<string | null>(null);
    const [editing, setEditing] = useState<ProductRow | null>(null);
    const [open, setOpen] = useState(false);

    const form = useForm<ProductForm>({ ...BLANK });
    const { data, setData, errors, processing } = form;

    // The price input holds dollars while the server validates `price_cents`,
    // so its error arrives under a key the form shape does not declare.
    const priceError = (errors as Record<string, string | undefined>)
        .price_cents;

    const tabs = [
        { value: null, label: 'All products', count: rows.length },
        ...categories.map((entry) => ({
            value: String(entry.id),
            label: entry.name,
            count: rows.filter((row) => row.category_id === entry.id).length,
        })),
    ];

    const visible = rows.filter((row) => {
        const term = search.trim().toLowerCase();
        const matchesCategory =
            category === null || row.category_id === Number(category);

        return (
            matchesCategory &&
            (term === '' ||
                row.name.toLowerCase().includes(term) ||
                row.sku.toLowerCase().includes(term) ||
                row.category_name.toLowerCase().includes(term))
        );
    });

    const openFor = (product: ProductRow | null) => {
        setEditing(product);
        form.setDefaults(
            product
                ? {
                      name: product.name,
                      sku: product.sku,
                      category_id: String(product.category_id),
                      price: (product.price_cents / 100).toFixed(2),
                      image_url: product.image_url,
                      is_active: product.is_active,
                  }
                : { ...BLANK, category_id: String(categories[0]?.id ?? '') },
        );
        form.reset();
        form.clearErrors();
        setOpen(true);
    };

    const submit = () => {
        form.transform((current: ProductForm) => ({
            name: current.name,
            sku: current.sku,
            category_id: Number(current.category_id),
            price_cents: Math.round(Number(current.price) * 100),
            image_url: current.image_url,
            is_active: current.is_active,
        }));

        const onSuccess = () => setOpen(false);

        if (editing) {
            form.put(products.update.url(editing.id), { onSuccess });

            return;
        }

        form.post(products.store.url(), { onSuccess });
    };

    return (
        <>
            <Head title="Products" />

            <div className="p-4">
                <header className="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-foreground">
                            Products
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {rows.length} product{rows.length === 1 ? '' : 's'}{' '}
                            in the catalogue the counter sells from.
                        </p>
                    </div>

                    <Button onClick={() => openFor(null)}>
                        <Plus size={16} aria-hidden="true" />
                        Add product
                    </Button>
                </header>

                <div className="overflow-hidden rounded-lg border border-border bg-card">
                    <TableToolbar
                        tabs={tabs}
                        activeTab={category}
                        tabsLabel="Product category"
                        onSelectTab={setCategory}
                        search={search}
                        onSearch={setSearch}
                        searchPlaceholder="Search products or SKUs"
                    />

                    <DataTable label="Products" className="min-w-3xl">
                        <THead>
                            <Th edge="start">Product</Th>
                            <Th>Category</Th>
                            <Th align="right">Price</Th>
                            <Th align="right">Sold</Th>
                            <Th>Status</Th>
                            <Th edge="end" align="right">
                                Edit
                            </Th>
                        </THead>

                        <tbody>
                            {visible.map((product) => (
                                <Tr key={product.id}>
                                    <Td edge="start">
                                        <div className="flex items-center gap-3">
                                            <img
                                                src={product.image_url}
                                                alt=""
                                                loading="lazy"
                                                className="size-10 shrink-0 rounded-md object-cover"
                                            />
                                            <div className="min-w-0">
                                                <p className="truncate font-medium text-foreground">
                                                    {product.name}
                                                </p>
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {product.sku}
                                                </p>
                                            </div>
                                        </div>
                                    </Td>
                                    <Td>{product.category_name}</Td>
                                    <Td
                                        align="right"
                                        className="font-mono tabular-nums"
                                    >
                                        {formatMoney(product.price_cents)}
                                    </Td>
                                    <Td
                                        align="right"
                                        className="font-mono text-muted-foreground tabular-nums"
                                    >
                                        {product.sold_count}
                                    </Td>
                                    <Td>
                                        <span
                                            className={cn(
                                                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium',
                                                product.is_active
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300'
                                                    : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-500/15 dark:text-neutral-300',
                                            )}
                                        >
                                            {product.is_active
                                                ? 'Active'
                                                : 'Hidden'}
                                        </span>
                                    </Td>
                                    <Td edge="end" align="right">
                                        <Button
                                            variant="ghost"
                                            size="compact"
                                            onClick={() => openFor(product)}
                                        >
                                            <Pencil
                                                size={13}
                                                aria-hidden="true"
                                            />
                                            Edit
                                        </Button>
                                    </Td>
                                </Tr>
                            ))}
                        </tbody>
                    </DataTable>

                    {visible.length === 0 ? (
                        <p className="bg-card px-4 py-10 text-center text-sm text-muted-foreground">
                            No products match that search.
                        </p>
                    ) : null}

                    <TableFooter>
                        <span className="font-mono text-xs tabular-nums">
                            Showing {visible.length} of {rows.length}
                        </span>
                    </TableFooter>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Edit product' : 'Add product'}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <Field
                            label="Name"
                            value={data.name}
                            error={errors.name}
                            onChange={(value) => setData('name', value)}
                        />

                        <div className="grid grid-cols-2 gap-4">
                            <Field
                                label="SKU"
                                value={data.sku}
                                error={errors.sku}
                                onChange={(value) => setData('sku', value)}
                            />
                            <Field
                                label="Price"
                                type="number"
                                value={data.price}
                                error={priceError}
                                onChange={(value) => setData('price', value)}
                            />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-sm font-medium text-foreground">
                                Category
                            </Label>
                            <Select
                                value={data.category_id}
                                onValueChange={(value) =>
                                    setData('category_id', value)
                                }
                            >
                                <SelectTrigger className="h-10 w-full border-border bg-background text-sm">
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem
                                            key={category.id}
                                            value={String(category.id)}
                                        >
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.category_id ? (
                                <p className="text-xs text-destructive">
                                    {errors.category_id}
                                </p>
                            ) : null}
                        </div>

                        <Field
                            label="Image URL"
                            value={data.image_url}
                            error={errors.image_url}
                            onChange={(value) => setData('image_url', value)}
                        />

                        <label className="flex items-center gap-2 text-sm text-foreground">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(event) =>
                                    setData('is_active', event.target.checked)
                                }
                                className="size-4 rounded border-border"
                            />
                            Sell this product at the counter
                        </label>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            size="sm"
                            disabled={processing}
                            onClick={submit}
                        >
                            {editing ? 'Save changes' : 'Add product'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
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

ProductsIndex.layout = {
    breadcrumbs: [{ title: 'Products', href: products.index() }],
};
