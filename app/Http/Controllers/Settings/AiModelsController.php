<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AiProvider;
use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Services\Ai\ProviderFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class AiModelsController extends Controller
{
    public function __construct(protected ProviderFactory $providers) {}

    /**
     * Ask the provider which models this account may use, so the operator picks
     * from a real list instead of typing an identifier from memory.
     *
     * A key typed into the form is used as-is; leaving it blank falls back to
     * the stored one, so refreshing never requires re-entering a saved key.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'api_key' => ['nullable', 'string', 'max:512'],
        ]);

        $provider = AiProvider::from($validated['provider']);
        $key = $validated['api_key'] ?? null;

        if (blank($key)) {
            $key = AiSetting::query()->where('provider', $provider)->value('api_key');
        }

        if (blank($key)) {
            return response()->json([
                'message' => 'Add an API key first, then refresh the list.',
                'models' => [],
            ], 422);
        }

        try {
            $models = $this->providers->make($provider, $provider->defaultModel(), (string) $key)->models();
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Could not reach '.$provider->label().': '.$exception->getMessage(),
                'models' => [],
            ], 422);
        }

        sort($models);

        return response()->json([
            'message' => count($models).' models available.',
            'models' => $models,
        ]);
    }
}
