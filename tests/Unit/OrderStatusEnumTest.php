<?php

use App\Enums\OrderStatus;

test('an order can only move along its fulfilment path', function (OrderStatus $from, OrderStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'pending to processing' => [OrderStatus::Pending, OrderStatus::Processing, true],
    'pending to cancelled' => [OrderStatus::Pending, OrderStatus::Cancelled, true],
    'pending skipping to shipped' => [OrderStatus::Pending, OrderStatus::Shipped, false],
    'processing to shipped' => [OrderStatus::Processing, OrderStatus::Shipped, true],
    'shipped to delivered' => [OrderStatus::Shipped, OrderStatus::Delivered, true],
    'delivered back to processing' => [OrderStatus::Delivered, OrderStatus::Processing, false],
    'delivered to refunded' => [OrderStatus::Delivered, OrderStatus::Refunded, true],
]);

test('cancelled and refunded are terminal', function (OrderStatus $status) {
    expect($status->transitions())->toBeEmpty();
})->with([
    [OrderStatus::Cancelled],
    [OrderStatus::Refunded],
]);

test('only unfinished orders count as open', function () {
    expect(OrderStatus::open())->toBe([
        OrderStatus::Pending,
        OrderStatus::Processing,
        OrderStatus::Shipped,
    ]);

    expect(OrderStatus::Delivered->isOpen())->toBeFalse();
    expect(OrderStatus::Processing->isOpen())->toBeTrue();
});
