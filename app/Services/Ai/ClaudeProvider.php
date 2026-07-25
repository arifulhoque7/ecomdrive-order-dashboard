<?php

namespace App\Services\Ai;

use Illuminate\Support\Arr;

/**
 * Anthropic Messages API, constrained to the caller's JSON schema.
 */
class ClaudeProvider extends Provider
{
    public function __construct(string $key, string $model, string $baseUrl, protected string $version)
    {
        parent::__construct($key, $model, $baseUrl);
    }

    public function name(): string
    {
        return 'claude';
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    protected function ask(string $system, string $prompt, array $schema): ?string
    {
        $response = $this->http()
            ->withHeaders([
                'x-api-key' => $this->key,
                'anthropic-version' => $this->version,
            ])
            ->post("{$this->baseUrl}/messages", [
                'model' => $this->model,
                'max_tokens' => 1500,
                'system' => $system,
                'output_config' => [
                    'effort' => 'low',
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        foreach (Arr::wrap($response->json('content')) as $block) {
            if (\is_array($block) && ($block['type'] ?? null) === 'text' && \is_string($block['text'] ?? null)) {
                return $block['text'];
            }
        }

        return null;
    }
}
