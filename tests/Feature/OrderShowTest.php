<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the detail page carries the items, activity and legal next statuses', function () {
    $order = Order::factory()->pending()->withItems(3)->create();

    $this->get(route('orders.show', $order))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('orders/show')
            ->where('order.order_number', $order->order_number)
            ->has('order.items', 3)
            ->has('order.activities', $order->activities()->count())
            ->where('order.allowed_transitions.0.value', 'processing')
            ->where('order.allowed_transitions.1.value', 'cancelled')
            ->where('order.totals.total_cents', $order->total_cents)
        );
});

test('a terminal order offers no further transitions', function () {
    $order = Order::factory()->status(App\Enums\OrderStatus::Cancelled)->create();

    $this->get(route('orders.show', $order))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('order.allowed_transitions', 0));
});

test('the detail page loads its relations without an N+1', function () {
    $order = Order::factory()->delivered()->withItems(5)->create();

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $this->get(route('orders.show', $order))->assertOk();

    expect($queries)->toBeLessThanOrEqual(8);
});
