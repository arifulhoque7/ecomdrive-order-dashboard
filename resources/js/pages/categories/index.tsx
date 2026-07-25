import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import categories from '@/actions/App/Http/Controllers/CategoryController';
import { DataTable, Td, Th, THead, Tr } from '@/components/data-table';
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

type CategoryRow = {
    id: number;
    name: string;
    description: string | null;
    products_count: number;
};

type CategoryForm = { name: string; description: string };

export default function CategoriesIndex({
    categories: rows,
    errors: pageErrors,
}: {
    categories: CategoryRow[];
    errors: Record<string, string>;
}) {
    const [search, setSearch] = useState('');
    const [editing, setEditing] = useState<CategoryRow | null>(null);
    const [open, setOpen] = useState(false);

    const visible = rows.filter((row) => {
        const term = search.trim().toLowerCase();

        return (
            term === '' ||
            row.name.toLowerCase().includes(term) ||
            (row.description ?? '').toLowerCase().includes(term)
        );
    });

    const form = useForm<CategoryForm>({ name: '', description: '' });
    const { data, setData, errors, processing } = form;

    const openFor = (category: CategoryRow | null) => {
        setEditing(category);
        form.setDefaults({
            name: category?.name ?? '',
            description: category?.description ?? '',
        });
        form.reset();
        form.clearErrors();
        setOpen(true);
    };

    const submit = () => {
        const onSuccess = () => setOpen(false);

        if (editing) {
            form.put(categories.update.url(editing.id), { onSuccess });

            return;
        }

        form.post(categories.store.url(), { onSuccess });
    };

    return (
        <>
            <Head title="Categories" />

            <div className="p-4">
                <header className="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-foreground">
                            Categories
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            How the counter groups the catalogue.
                        </p>
                    </div>

                    <Button onClick={() => openFor(null)}>
                        <Plus size={16} aria-hidden="true" />
                        Add category
                    </Button>
                </header>

                {pageErrors?.category ? (
                    <p className="mb-4 rounded-lg border border-destructive/40 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                        {pageErrors.category}
                    </p>
                ) : null}

                <div className="overflow-hidden rounded-lg border border-border bg-card">
                    <TableToolbar
                        search={search}
                        onSearch={setSearch}
                        searchPlaceholder="Search categories"
                    />

                    <DataTable label="Categories" className="min-w-2xl">
                        <THead>
                            <Th edge="start">Category</Th>
                            <Th align="right">Products</Th>
                            <Th edge="end" align="right">
                                Actions
                            </Th>
                        </THead>

                        <tbody>
                            {visible.map((category) => (
                                <Tr key={category.id} dense>
                                    <Td edge="start">
                                        <span className="block font-medium text-foreground">
                                            {category.name}
                                        </span>
                                        <span className="block truncate text-xs text-muted-foreground">
                                            {category.description ??
                                                'No description'}
                                        </span>
                                    </Td>
                                    <Td
                                        align="right"
                                        className="font-mono text-muted-foreground tabular-nums"
                                    >
                                        {category.products_count}
                                    </Td>
                                    <Td edge="end" align="right">
                                        <div className="inline-flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="compact"
                                                onClick={() =>
                                                    openFor(category)
                                                }
                                            >
                                                <Pencil
                                                    size={13}
                                                    aria-hidden="true"
                                                />
                                                Edit
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="compact"
                                                disabled={
                                                    category.products_count > 0
                                                }
                                                title={
                                                    category.products_count > 0
                                                        ? 'Move its products first'
                                                        : undefined
                                                }
                                                onClick={() =>
                                                    form.delete(
                                                        categories.destroy.url(
                                                            category.id,
                                                        ),
                                                    )
                                                }
                                                className="text-muted-foreground hover:text-destructive"
                                            >
                                                <Trash2
                                                    size={13}
                                                    aria-hidden="true"
                                                />
                                                Delete
                                            </Button>
                                        </div>
                                    </Td>
                                </Tr>
                            ))}
                        </tbody>
                    </DataTable>

                    {visible.length === 0 ? (
                        <p className="bg-card px-4 py-10 text-center text-sm text-muted-foreground">
                            No categories match that search.
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
                            {editing ? 'Edit category' : 'Add category'}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-sm font-medium text-foreground">
                                Name
                            </Label>
                            <Input
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                className="h-10 w-full border-border bg-background px-4 text-sm"
                            />
                            {errors.name ? (
                                <p className="text-xs text-destructive">
                                    {errors.name}
                                </p>
                            ) : null}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-sm font-medium text-foreground">
                                Description
                            </Label>
                            <Input
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                                className="h-10 w-full border-border bg-background px-4 text-sm"
                            />
                            {errors.description ? (
                                <p className="text-xs text-destructive">
                                    {errors.description}
                                </p>
                            ) : null}
                        </div>
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
                            {editing ? 'Save changes' : 'Add category'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Categories', href: categories.index() }],
};
