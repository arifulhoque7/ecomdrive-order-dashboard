<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AiProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAiSettingsRequest;
use App\Models\AiSetting;
use App\Services\Ai\ProviderFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function __construct(protected ProviderFactory $providers) {}

    /**
     * Keys are never sent back to the browser — the page only learns whether one
     * is stored.
     */
    public function edit(): Response
    {
        $saved = AiSetting::query()->get()->keyBy(fn (AiSetting $setting) => $setting->provider->value);

        $active = $saved->first(fn (AiSetting $setting) => $setting->is_active);

        return Inertia::render('settings/ai', [
            'providers' => Collection::make(AiProvider::cases())->map(function (AiProvider $provider) use ($saved) {
                $setting = $saved->get($provider->value);

                return [
                    'value' => $provider,
                    'label' => $provider->label(),
                    'default_model' => $provider->defaultModel(),
                    'key_url' => $provider->keyUrl(),
                    'model' => $setting === null ? $provider->defaultModel() : $setting->model,
                    'has_key' => $setting !== null && $setting->hasKey(),
                    'is_active' => $setting !== null && $setting->is_active,
                ];
            }),
            'active' => $active === null ? AiProvider::Claude : $active->provider,
        ]);
    }

    /**
     * Save one provider's settings and make it the active assistant. An empty
     * key field means "keep the stored key" rather than "clear it".
     */
    public function update(UpdateAiSettingsRequest $request): RedirectResponse
    {
        $provider = $request->provider();
        $setting = AiSetting::query()->firstOrNew(['provider' => $provider]);

        $setting->model = $request->string('model')->value();

        if ($request->filled('api_key')) {
            $setting->api_key = $request->string('api_key')->value();
        }

        $setting->is_active = true;
        $setting->save();

        AiSetting::query()->whereKeyNot($setting->id)->update(['is_active' => false]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':provider is now generating insights.', ['provider' => $provider->label()]),
        ]);

        return to_route('ai.edit');
    }
}
