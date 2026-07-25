<?php

use App\Enums\ActivityType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;

beforeEach(function () {
    $this->operator = User::factory()->create();
    $this->actingAs($this->operator);
});

test('guests cannot move an order', function () {
    auth()->logout();

    $order = Order::factory()->pending()->create();

    $this->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertRedirect(route('login'));

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

test('a legal transition is applied and leaves an audit trail', function () {
    $order = Order::factory()->pending()->create();
    $before = $order->activities()->count();

    $this->patch(route('orders.status.update', $order), ['status' => 'confirmed'])
        ->assertRedirect(route('orders.show', $order));

    expect($order->refresh()->status)->toBe(OrderStatus::Confirmed);

    $activity = OrderActivity::query()->latest('id')->first();

    expect($order->activities()->count())->toBe($before + 1)
        ->and($activity->type)->toBe(ActivityType::StatusChanged)
        ->and($activity->from_status)->toBe(OrderStatus::Pending)
        ->and($activity->to_status)->toBe(OrderStatus::Confirmed)
        ->and($activity->user_id)->toBe($this->operator->id);
});

test('an order cannot skip confirmation on its way to processing', function () {
    $order = Order::factory()->pending()->create();

    $this->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertSessionHasErrors('status');

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

test('an illegal transition is rejected and changes nothing', function () {
    $order = Order::factory()->delivered()->create();
    $before = $order->activities()->count();

    $this->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertSessionHasErrors('status');

    expect($order->refresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->activities()->count())->toBe($before);
});

test('a status outside the enum is rejected', function () {
    $order = Order::factory()->pending()->create();

    $this->patch(route('orders.status.update', $order), ['status' => 'teleported'])
        ->assertSessionHasErrors('status');

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});
