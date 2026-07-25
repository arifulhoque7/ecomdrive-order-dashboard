<?php

namespace App\Providers;

use App\Services\Ai\ClaudeProvider;
use App\Services\Ai\GeminiProvider;
use App\Services\Ai\InsightProvider;
use App\Services\Ai\OpenAiProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InsightProvider::class, fn (): InsightProvider => match (Config::string('services.ai.provider')) {
            'openai' => new OpenAiProvider(
                Config::string('services.openai.key', ''),
                Config::string('services.openai.model'),
                Config::string('services.openai.base_url'),
            ),
            'gemini' => new GeminiProvider(
                Config::string('services.gemini.key', ''),
                Config::string('services.gemini.model'),
                Config::string('services.gemini.base_url'),
            ),
            default => new ClaudeProvider(
                Config::string('services.anthropic.key', ''),
                Config::string('services.anthropic.model'),
                Config::string('services.anthropic.base_url'),
                Config::string('services.anthropic.version'),
            ),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
