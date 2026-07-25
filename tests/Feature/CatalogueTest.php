<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the catalogue lists products with their category and sales', function () {
    $category = Category::factory()->create(['name' => 'Audio']);
    $product = Product::factory()->for($category)->create(['sku' => 'AUD-1']);

    OrderItem::factory()->for(Order::factory()->create())->create([
        'sku' => $product->sku,
        'name' => $product->name,
        'quantity' => 2,
        'unit_price_cents' => $product->price_cents,
        'line_total_cents' => 2 * $product->price_cents,
    ]);

    $soldLines = OrderItem::query()->where('sku', $product->sku)->count();

    $this->get(route('products.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('products/index')
            ->where('products.0.category_name', 'Audio')
            ->where('products.0.sold_count', $soldLines)
            ->has('categories', 1)
        );
});

test('a product can be added and edited', function () {
    $category = Category::factory()->create();

    $this->post(route('products.store'), [
        'name' => 'Aurora Headphones',
        'sku' => 'AUR-999',
        'category_id' => $category->id,
        'price_cents' => 18_900,
        'image_url' => 'https://burst.shopifycdn.com/photos/black-headphones-closeup.jpg',
        'is_active' => true,
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('sku', 'AUR-999')->firstOrFail();

    expect($product->name)->toBe('Aurora Headphones')
        ->and($product->price_cents)->toBe(18_900)
        ->and($product->category_id)->toBe($category->id);

    $this->put(route('products.update', $product), [
        'name' => 'Aurora Headphones v2',
        'sku' => 'AUR-999',
        'category_id' => $category->id,
        'price_cents' => 20_000,
        'image_url' => 'https://burst.shopifycdn.com/photos/black-headphones-closeup.jpg',
        'is_active' => false,
    ])->assertRedirect(route('products.index'));

    expect($product->refresh()->name)->toBe('Aurora Headphones v2')
        ->and($product->is_active)->toBeFalse();
});

test('two products cannot share a sku', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create(['sku' => 'TAKEN-1']);

    $this->post(route('products.store'), [
        'name' => 'Copycat',
        'sku' => 'TAKEN-1',
        'category_id' => $category->id,
        'price_cents' => 1_000,
        'image_url' => 'https://example.com/a.jpg',
        'is_active' => true,
    ])->assertSessionHasErrors('sku');
});

test('hidden products never reach the counter', function () {
    Product::factory()->create(['is_active' => true]);
    Product::factory()->create(['is_active' => false]);

    $this->get(route('orders.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('products', 1));
});

test('a category can be added, renamed and counted', function () {
    $this->post(route('categories.store'), [
        'name' => 'Lighting',
        'description' => 'Lamps and strips.',
    ])->assertRedirect(route('categories.index'));

    $category = Category::query()->where('name', 'Lighting')->firstOrFail();
    Product::factory()->for($category)->create();

    $this->put(route('categories.update', $category), ['name' => 'Lighting & Lamps'])
        ->assertRedirect(route('categories.index'));

    expect($category->refresh()->name)->toBe('Lighting & Lamps');

    $this->get(route('categories.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('categories/index')
            ->where('categories.0.products_count', 1)
        );
});

test('a category holding products cannot be deleted', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    $this->delete(route('categories.destroy', $category))->assertSessionHasErrors('category');

    expect(Category::query()->whereKey($category->id)->exists())->toBeTrue();
});

test('an empty category can be deleted', function () {
    $category = Category::factory()->create();

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse();
});

test('guests cannot touch the catalogue', function () {
    auth()->logout();

    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('categories.index'))->assertRedirect(route('login'));
});
