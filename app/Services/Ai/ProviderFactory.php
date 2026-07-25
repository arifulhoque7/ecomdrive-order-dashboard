<?php

namespace App\Services\Ai;

use App\Enums\AiProvider;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Config;

/**
 * Builds the assistant the app should talk to.
 *
 * Settings saved in the app win, so an operator can switch provider, model or
 * key without a deploy; the .env values remain the fallback for a fresh install.
 */
class ProviderFactory
{
    public function active(): InsightProvider
    {
        $saved = AiSetting::query()->where('is_active', true)->first();

        if ($saved !== null) {
            return $this->make($saved->provider, $saved->model, (string) $saved->api_key);
        }

        $provider = AiProvider::tryFrom(Config::string('services.ai.provider')) ?? AiProvider::Claude;

        return $this->make(
            $provider,
            Config::string("services.{$this->configKey($provider)}.model"),
            Config::string("services.{$this->configKey($provider)}.key", ''),
        );
    }

    public function make(AiProvider $provider, string $model, string $key): InsightProvider
    {
        $baseUrl = Config::string("services.{$this->configKey($provider)}.base_url");

        return match ($provider) {
            AiProvider::OpenAi => new OpenAiProvider($key, $model, $baseUrl),
            AiProvider::Gemini => new GeminiProvider($key, $model, $baseUrl),
            AiProvider::Claude => new ClaudeProvider(
                $key,
                $model,
                $baseUrl,
                Config::string('services.anthropic.version'),
            ),
        };
    }

    /**
     * The provider's slug differs from its key in config/services.php.
     */
    protected function configKey(AiProvider $provider): string
    {
        return match ($provider) {
            AiProvider::Claude => 'anthropic',
            AiProvider::OpenAi => 'openai',
            AiProvider::Gemini => 'gemini',
        };
    }
}
