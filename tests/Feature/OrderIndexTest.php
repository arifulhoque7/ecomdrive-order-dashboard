<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot browse the order book', function () {
    auth()->logout();

    $this->get(route('orders.index'))->assertRedirect(route('login'));
});

test('search matches an order number or the customer behind it', function () {
    $wanted = Order::factory()
        ->for(Customer::factory()->create(['name' => 'Marguerite Doyle']))
        ->create(['order_number' => 'ORD-WANTED']);

    Order::factory()
        ->for(Customer::factory()->create(['name' => 'Sabine Ferrer']))
        ->create(['order_number' => 'ORD-OTHER']);

    $this->get(route('orders.index', ['q' => 'ORD-WANTED']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('orders/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $wanted->id)
        );

    $this->get(route('orders.index', ['q' => 'Marguerite']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.order_number', 'ORD-WANTED')
        );
});

test('search ignores case regardless of the database driver', function () {
    Order::factory()
        ->for(Customer::factory()->create(['name' => 'Marguerite Doyle']))
        ->create(['order_number' => 'ORD-WANTED']);

    Order::factory()
        ->for(Customer::factory()->create(['name' => 'Sabine Ferrer']))
        ->create(['order_number' => 'ORD-OTHER']);

    $this->get(route('orders.index', ['q' => 'ord-wanted']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.order_number', 'ORD-WANTED')
        );

    $this->get(route('orders.index', ['q' => 'MARGUERITE']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.order_number', 'ORD-WANTED')
        );
});

test('the status tab narrows the list and the counts stay filter aware', function () {
    Order::factory()->count(3)->pending()->create();
    Order::factory()->count(2)->delivered()->create();

    $this->get(route('orders.index', ['status' => 'pending']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('orders.data', 3)
            ->where('statusCounts.pending', 3)
            ->where('statusCounts.delivered', 2)
            ->where('statusCounts.all', 5)
        );
});

test('the date range only returns orders placed inside it', function () {
    Order::factory()->create(['placed_at' => now()->subDays(40)]);
    $recent = Order::factory()->create(['placed_at' => now()->subDay()]);

    $this->get(route('orders.index', [
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->toDateString(),
    ]))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('orders.data', 1)
        ->where('orders.data.0.id', $recent->id)
    );
});

test('an end date before the start date is rejected', function () {
    $this->get(route('orders.index', [
        'date_from' => now()->toDateString(),
        'date_to' => now()->subWeek()->toDateString(),
    ]))->assertSessionHasErrors('date_to');
});

test('the summary cards hold still when the status tab changes', function () {
    Order::factory()->count(3)->pending()->create();
    Order::factory()->count(2)->delivered()->create();

    $all = $this->get(route('orders.index'))->viewData('page')['props']['summary'];

    $this->get(route('orders.index', ['status' => 'pending']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('orders.data', 3)
            ->where('summary.orders_count', $all['orders_count'])
            ->where('summary.pending_orders', $all['pending_orders'])
            ->where('summary.delivered_orders', $all['delivered_orders'])
            ->where('summary.revenue_cents', $all['revenue_cents'])
            ->where('summary.open_orders', $all['open_orders'])
        );
});

test('the summary cards still follow the search and date filters', function () {
    Order::factory()->create(['placed_at' => now()->subDays(40)]);
    Order::factory()->count(2)->create(['placed_at' => now()->subDay()]);

    $this->get(route('orders.index', ['date_from' => now()->subWeek()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.orders_count', 2));
});

test('the summary cards describe the filtered set and exclude lost revenue', function () {
    Order::factory()->count(2)->delivered()->create();
    Order::factory()->count(3)->pending()->create();
    Order::factory()->status(OrderStatus::Cancelled)->create();

    $delivered = Order::query()->where('status', OrderStatus::Delivered)->get();

    $this->get(route('orders.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('summary.orders_count', 6)
            ->where('summary.pending_orders', 3)
            ->where('summary.delivered_orders', 2)
            ->where('summary.revenue_cents', (int) ($delivered->sum('total_cents') + Order::query()->where('status', OrderStatus::Pending)->sum('total_cents')))
            ->where('summary.open_orders', 3)
        );
});
