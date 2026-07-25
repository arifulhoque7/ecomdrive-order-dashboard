<?php

use App\Actions\Orders\RecalculateOrderTotals;
use App\Enums\ActivityType;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->operator = User::factory()->create();
    $this->actingAs($this->operator);
});

test('the counter offers the active catalogue and the pricing rules', function () {
    Product::factory()->count(3)->create();
    Product::factory()->create(['is_active' => false]);

    $this->get(route('orders.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('orders/create')
            ->has('products', 3)
            ->where('pricing.tax_rate', RecalculateOrderTotals::TAX_RATE)
            ->where('pricing.flat_shipping_cents', RecalculateOrderTotals::FLAT_SHIPPING_CENTS)
        );
});

test('an order is rung up for an existing customer with catalogue prices', function () {
    $customer = Customer::factory()->create();
    $keyboard = Product::factory()->create(['price_cents' => 14_500]);
    $mug = Product::factory()->create(['price_cents' => 4_200]);

    $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $keyboard->id, 'quantity' => 2],
            ['product_id' => $mug->id, 'quantity' => 1],
        ],
        'discount_cents' => 1_000,
    ])->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->customer_id)->toBe($customer->id)
        ->and($order->items()->count())->toBe(2)
        ->and($order->subtotal_cents)->toBe(33_200)
        ->and($order->discount_cents)->toBe(1_000)
        ->and($order->shipping_cents)->toBe(0)
        ->and($order->tax_cents)->toBe((int) round(32_200 * RecalculateOrderTotals::TAX_RATE))
        ->and($order->total_cents)->toBe(32_200 + $order->tax_cents)
        ->and($order->activities()->where('type', ActivityType::OrderPlaced)->exists())->toBeTrue();
});

test('prices come from the catalogue, never from the request', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price_cents' => 9_900]);

    $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price_cents' => 1],
        ],
    ])->assertRedirect();

    expect(Order::query()->latest('id')->firstOrFail()->subtotal_cents)->toBe(9_900);
});

test('a small order pays flat shipping', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price_cents' => 2_000]);

    $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertRedirect();

    expect(Order::query()->latest('id')->firstOrFail()->shipping_cents)
        ->toBe(RecalculateOrderTotals::FLAT_SHIPPING_CENTS);
});

test('a walk-in customer is opened alongside the order', function () {
    $product = Product::factory()->create();

    $this->post(route('orders.store'), [
        'customer' => [
            'name' => 'Walk In Wanda',
            'email' => 'wanda@example.com',
            'phone' => '+1-555-0100',
            'city' => 'Austin',
        ],
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertRedirect();

    $customer = Customer::query()->where('email', 'wanda@example.com')->firstOrFail();

    expect(Order::query()->latest('id')->firstOrFail()->customer_id)->toBe($customer->id);
});

test('an order without lines or a customer is rejected', function () {
    $this->post(route('orders.store'), ['items' => []])
        ->assertSessionHasErrors(['items', 'customer_id']);

    expect(Order::query()->count())->toBe(0);
});

test('guests cannot ring up orders', function () {
    auth()->logout();

    $product = Product::factory()->create();

    $this->post(route('orders.store'), [
        'customer' => ['name' => 'X', 'email' => 'x@example.com'],
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertRedirect(route('login'));

    expect(Order::query()->count())->toBe(0);
});
