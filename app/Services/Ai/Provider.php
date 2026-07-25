<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared plumbing for every assistant: skip when unconfigured, keep the request
 * bounded, and never let a provider outage bubble up to the operator.
 */
abstract class Provider implements InsightProvider
{
    public function __construct(
        protected string $key,
        protected string $model,
        protected string $baseUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>|null
     */
    public function generate(string $system, string $prompt, array $schema): ?array
    {
        if ($this->key === '') {
            return null;
        }

        try {
            $text = $this->ask($system, $prompt, $schema);
        } catch (Throwable $exception) {
            Log::warning('AI insight provider failed.', [
                'provider' => $this->name(),
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        $payload = json_decode((string) $text, true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * Send the request and return the model's raw JSON answer.
     *
     * @param  array<string, mixed>  $schema
     */
    abstract protected function ask(string $system, string $prompt, array $schema): ?string;

    protected function http(): PendingRequest
    {
        return Http::timeout(25)
            ->connectTimeout(5)
            ->retry(2, 250, throw: false)
            ->throw();
    }
}
