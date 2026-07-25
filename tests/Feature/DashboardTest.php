<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('the dashboard reports today, the open queue and the order book', function () {
    $this->actingAs(User::factory()->create());

    Order::factory()->count(2)->pending()->create(['placed_at' => now()]);
    Order::factory()->delivered()->create(['placed_at' => now()->subDays(3)]);
    Order::factory()->status(OrderStatus::Cancelled)->create(['placed_at' => now()]);

    $earned = Order::query()
        ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded])
        ->whereDate('placed_at', now())
        ->sum('total_cents');

    $this->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('headline.orders_today', 3)
            ->where('headline.revenue_today', (int) $earned)
            ->where('headline.open_orders', 2)
            ->where('headline.awaiting_payment', 2)
            ->has('revenueTrend', 14)
            ->has('statusBreakdown', 6)
            ->has('needsAttention', 2)
            ->has('recentOrders', 4)
            ->has('topCustomers')
        );
});

test('cancelled orders never count as revenue on the dashboard', function () {
    $this->actingAs(User::factory()->create());

    Order::factory()->status(OrderStatus::Cancelled)->create(['placed_at' => now()]);

    $this->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('headline.revenue_today', 0)
            ->where('headline.revenue_30d', 0)
        );
});
