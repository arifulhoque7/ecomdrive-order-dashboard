<?php

use App\Enums\AiProvider;
use App\Models\AiSetting;
use App\Models\Order;
use App\Models\User;
use App\Services\Ai\InsightProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the settings page reports what is stored without leaking the key', function () {
    AiSetting::query()->create([
        'provider' => AiProvider::OpenAi,
        'model' => 'gpt-4.1',
        'api_key' => 'sk-secret-value',
        'is_active' => true,
    ]);

    $response = $this->get(route('ai.edit'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/ai')
        ->where('active', 'openai')
        ->where('providers.1.has_key', true)
        ->where('providers.1.model', 'gpt-4.1')
        ->missing('providers.1.api_key')
    );

    expect($response->content())->not->toContain('sk-secret-value');
});

test('saving a provider stores the key encrypted and makes it the only active one', function () {
    AiSetting::query()->create([
        'provider' => AiProvider::Claude,
        'model' => 'claude-opus-5',
        'api_key' => 'old-claude-key',
        'is_active' => true,
    ]);

    $this->put(route('ai.update'), [
        'provider' => 'openai',
        'model' => 'gpt-4.1',
        'api_key' => 'sk-new-key',
    ])->assertRedirect(route('ai.edit'));

    $openai = AiSetting::query()->where('provider', AiProvider::OpenAi)->firstOrFail();

    expect($openai->is_active)->toBeTrue()
        ->and($openai->api_key)->toBe('sk-new-key')
        ->and($openai->getRawOriginal('api_key'))->not->toContain('sk-new-key')
        ->and(AiSetting::query()->where('provider', AiProvider::Claude)->firstOrFail()->is_active)->toBeFalse();
});

test('an empty key field keeps the stored key', function () {
    AiSetting::query()->create([
        'provider' => AiProvider::OpenAi,
        'model' => 'gpt-4.1',
        'api_key' => 'sk-keep-me',
        'is_active' => true,
    ]);

    $this->put(route('ai.update'), [
        'provider' => 'openai',
        'model' => 'gpt-4.1-mini',
        'api_key' => '',
    ])->assertRedirect(route('ai.edit'));

    $setting = AiSetting::query()->where('provider', AiProvider::OpenAi)->firstOrFail();

    expect($setting->api_key)->toBe('sk-keep-me')
        ->and($setting->model)->toBe('gpt-4.1-mini');
});

test('the saved provider is the one that generates insights', function () {
    Config::set('services.ai.provider', 'claude');

    AiSetting::query()->create([
        'provider' => AiProvider::Gemini,
        'model' => 'gemini-2.5-flash',
        'api_key' => 'gem-key',
        'is_active' => true,
    ]);

    expect(app(InsightProvider::class)->name())->toBe('gemini');
});

test('refreshing models asks the provider and returns the list', function () {
    Http::fake([
        'api.openai.com/v1/models' => Http::response([
            'data' => [
                ['id' => 'gpt-4.1'],
                ['id' => 'text-embedding-3-small'],
                ['id' => 'gpt-4o-mini'],
            ],
        ]),
    ]);

    $this->postJson(route('ai.models'), [
        'provider' => 'openai',
        'api_key' => 'sk-test',
    ])
        ->assertOk()
        ->assertJsonPath('models', ['gpt-4.1', 'gpt-4o-mini']);
});

test('refreshing without any key explains what to do', function () {
    Http::fake();

    $this->postJson(route('ai.models'), ['provider' => 'openai'])
        ->assertStatus(422)
        ->assertJsonPath('models', []);

    Http::assertNothingSent();
});

test('a provider outage while refreshing is reported, not thrown', function () {
    Http::fake(['api.openai.com/*' => Http::response(status: 401)]);

    $this->postJson(route('ai.models'), [
        'provider' => 'openai',
        'api_key' => 'sk-wrong',
    ])->assertStatus(422)->assertJsonPath('models', []);
});

test('the stored key is used to generate a real insight', function () {
    AiSetting::query()->create([
        'provider' => AiProvider::OpenAi,
        'model' => 'gpt-4.1',
        'api_key' => 'sk-stored',
        'is_active' => true,
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '{"summary":"s","next_actions":[],"missing_info":[],"draft_reply":"d"}',
                ],
            ]],
        ]),
    ]);

    $order = Order::factory()->pending()->create();

    $this->postJson(route('orders.insight', $order))
        ->assertOk()
        ->assertJsonPath('insight.source', 'openai');
});

test('guests cannot read or change the assistant settings', function () {
    auth()->logout();

    $this->get(route('ai.edit'))->assertRedirect(route('login'));
    $this->put(route('ai.update'), ['provider' => 'openai', 'model' => 'gpt-4.1'])
        ->assertRedirect(route('login'));
});
