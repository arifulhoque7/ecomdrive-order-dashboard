<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * The catalogue, as the merchandiser sees it.
     */
    public function index(): Response
    {
        return Inertia::render('products/index', [
            'products' => Product::query()
                ->with('category')
                ->withCount('orderLines')
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'image_url' => $product->image_url,
                    'price_cents' => $product->price_cents,
                    'is_active' => $product->is_active,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category->name,
                    'sold_count' => (int) $product->order_lines_count,
                ]),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product added.')]);

        return to_route('products.index');
    }

    public function update(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('products.index');
    }
}
