<?php

use App\Enums\ActivityType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function insightPayload(): string
{
    return json_encode([
        'summary' => 'Paid and packed, waiting on the carrier.',
        'next_actions' => ['Book the shipment.'],
        'missing_info' => ['Customer phone number'],
        'draft_reply' => 'Your order ships today.',
    ]);
}

test('a claude answer is parsed, cached on the order and logged', function () {
    Config::set('services.ai.provider', 'claude');
    Config::set('services.anthropic.key', 'test-key');

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'thinking', 'thinking' => ''],
                ['type' => 'text', 'text' => insightPayload()],
            ],
        ]),
    ]);

    $order = Order::factory()->pending()->create();

    $this->postJson(route('orders.insight', $order))
        ->assertOk()
        ->assertJsonPath('insight.source', 'claude')
        ->assertJsonPath('insight.summary', 'Paid and packed, waiting on the carrier.')
        ->assertJsonPath('insight.next_actions.0', 'Book the shipment.');

    $order->refresh();

    expect($order->ai_insight['source'])->toBe('claude')
        ->and($order->ai_insight_generated_at)->not->toBeNull()
        ->and($order->activities()->where('type', ActivityType::AiInsight)->exists())->toBeTrue();
});

test('openai and gemini answer through the same contract', function (string $provider, string $host, array $body) {
    Config::set('services.ai.provider', $provider);
    Config::set("services.{$provider}.key", 'test-key');

    Http::fake([$host => Http::response($body)]);

    $order = Order::factory()->pending()->create();

    $this->postJson(route('orders.insight', $order))
        ->assertOk()
        ->assertJsonPath('insight.source', $provider)
        ->assertJsonPath('insight.draft_reply', 'Your order ships today.');
})->with([
    'openai' => [
        'openai',
        'api.openai.com/*',
        ['choices' => [['message' => ['content' => '{"summary":"s","next_actions":[],"missing_info":[],"draft_reply":"Your order ships today."}']]]],
    ],
    'gemini' => [
        'gemini',
        'generativelanguage.googleapis.com/*',
        ['candidates' => [['content' => ['parts' => [['text' => '{"summary":"s","next_actions":[],"missing_info":[],"draft_reply":"Your order ships today."}']]]]]],
    ],
]);

test('a cached insight is reused until a refresh is asked for', function () {
    Config::set('services.anthropic.key', 'test-key');

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => insightPayload()]],
        ]),
    ]);

    $order = Order::factory()->pending()->create();

    $this->postJson(route('orders.insight', $order))->assertOk();
    $this->postJson(route('orders.insight', $order))->assertOk();

    Http::assertSentCount(1);

    $this->postJson(route('orders.insight', $order), ['refresh' => true])->assertOk();

    Http::assertSentCount(2);
});

test('a provider outage falls back to a deterministic brief', function () {
    Config::set('services.anthropic.key', 'test-key');

    Http::fake(['api.anthropic.com/*' => Http::response(status: 500)]);

    $order = Order::factory()->pending()->missingShippingAddress()->create();

    $this->postJson(route('orders.insight', $order))
        ->assertOk()
        ->assertJsonPath('insight.source', 'fallback')
        ->assertJsonPath('insight.missing_info', fn (array $missing) => in_array('Shipping address', $missing, true));
});

test('a missing api key falls back without calling anyone', function () {
    Config::set('services.anthropic.key', '');
    Http::fake();

    $order = Order::factory()->processing()->create();

    $this->postJson(route('orders.insight', $order))
        ->assertOk()
        ->assertJsonPath('insight.source', 'fallback')
        ->assertJsonPath('insight.next_actions.0', 'Pick and pack the items.');

    Http::assertNothingSent();
});

test('guests cannot generate an insight', function () {
    auth()->logout();

    $order = Order::factory()->create();

    $this->postJson(route('orders.insight', $order))->assertUnauthorized();
});
