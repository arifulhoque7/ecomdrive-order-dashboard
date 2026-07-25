<?php

namespace App\Actions\Orders;

use App\Enums\ActivityType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderItem;
use App\Services\Ai\InsightProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read an order the way an operator would and hand back an actionable brief:
 * what happened, what to do next, what is missing, and a reply to send.
 *
 * Backed by the configured assistant, with a deterministic fallback so the
 * feature still works when no API key is present.
 */
class GenerateOrderInsight
{
    public function __construct(protected InsightProvider $provider) {}

    /**
     * The answer shape every provider is held to.
     *
     * @var array<string, mixed>
     */
    protected array $schema = [
        'type' => 'object',
        'properties' => [
            'summary' => ['type' => 'string'],
            'next_actions' => ['type' => 'array', 'items' => ['type' => 'string']],
            'missing_info' => ['type' => 'array', 'items' => ['type' => 'string']],
            'draft_reply' => ['type' => 'string'],
        ],
        'required' => ['summary', 'next_actions', 'missing_info', 'draft_reply'],
        'additionalProperties' => false,
    ];

    /**
     * @return array<string, mixed>
     */
    public function execute(Order $order, bool $refresh = false): array
    {
        if (! $refresh && $order->ai_insight !== null) {
            return $order->ai_insight;
        }

        $order->loadMissing(['customer', 'items', 'activities']);

        $generated = $this->provider->generate(
            'You are an operations analyst for an e-commerce store. Answer only with the requested JSON.',
            $this->prompt($order),
            $this->schema,
        );

        $insight = $generated === null
            ? $this->fromRules($order)
            : [...$generated, 'source' => $this->provider->name()];

        $order->forceFill([
            'ai_insight' => $insight,
            'ai_insight_generated_at' => Carbon::now(),
        ])->save();

        $order->activities()->create([
            'type' => ActivityType::AiInsight,
            'description' => 'AI insight generated.',
            'meta' => ['source' => $insight['source']],
        ]);

        return $insight;
    }

    protected function prompt(Order $order): string
    {
        $items = $order->items
            ->map(fn (OrderItem $item) => "- {$item->quantity} x {$item->name} ({$item->sku})")
            ->implode("\n");

        $timeline = $order->activities
            ->sortBy('created_at')
            ->map(fn (OrderActivity $activity) => "- {$activity->created_at->toDateString()}: {$activity->description}")
            ->implode("\n");

        $address = $order->shipping_address === null
            ? 'MISSING'
            : implode(', ', $order->shipping_address);

        return <<<PROMPT
        Order {$order->order_number} is currently {$order->status->label()} and was placed {$order->placed_at->diffForHumans()}.

        Customer: {$order->customer->name} <{$order->customer->email}>, phone: {$order->customer->phone}
        Shipping address: {$address}
        Order total: {$order->total_cents} cents ({$order->currency})
        Operator notes: {$order->notes}

        Items:
        {$items}

        Activity history:
        {$timeline}

        Summarise what has happened, list the next actions the operator should take, flag anything
        missing that blocks fulfilment, and draft a customer-ready reply to a "where is my order"
        enquiry. Keep every string plain text.
        PROMPT;
    }

    /**
     * Deterministic brief used when no assistant is reachable, so the feature
     * never hard-fails on a missing key.
     *
     * @return array<string, mixed>
     */
    protected function fromRules(Order $order): array
    {
        $days = (int) $order->placed_at->diffInDays(Carbon::now());

        $missing = Collection::make([
            $order->customer->phone === null ? 'Customer phone number' : null,
            $order->shipping_address === null ? 'Shipping address' : null,
            $order->items->isEmpty() ? 'Order line items' : null,
        ])->filter()->values();

        $actions = match ($order->status) {
            OrderStatus::Pending => ['Confirm the payment has cleared.', 'Confirm the order once the customer details check out.'],
            OrderStatus::Confirmed => ['Reserve stock for every line item.', 'Move the order to processing so it can be picked.'],
            OrderStatus::Processing => ['Pick and pack the items.', 'Book the shipment and share tracking with the customer.'],
            OrderStatus::Shipped => ['Monitor the carrier tracking.', 'Mark as delivered once the carrier confirms.'],
            OrderStatus::Delivered => ['Invite the customer to review their purchase.', 'Close the ticket unless a refund is requested.'],
            OrderStatus::Cancelled => ['Release any reserved stock.', 'Confirm the cancellation with the customer.'],
            OrderStatus::Refunded => ['Verify the refund settled with the payment provider.', 'Record the refund reason for reporting.'],
        };

        return [
            'summary' => sprintf(
                'Order %s for %s is %s after %d day(s), covering %d item(s) worth %s %s.',
                $order->order_number,
                $order->customer->name,
                $order->status->label(),
                $days,
                $order->items->count(),
                number_format($order->total_cents / 100, 2),
                $order->currency,
            ),
            'next_actions' => $actions,
            'missing_info' => $missing->all(),
            'draft_reply' => "Hi {$order->customer->name}, thanks for checking in on order {$order->order_number}. It is currently {$order->status->label()} and we will update you as soon as anything changes.",
            'source' => 'fallback',
        ];
    }
}
