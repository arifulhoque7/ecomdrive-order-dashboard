<?php

namespace App\Services\Ai;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * OpenAI chat completions with a strict structured-output schema.
 */
class OpenAiProvider extends Provider
{
    public function name(): string
    {
        return 'openai';
    }

    /**
     * The account exposes embeddings, audio and image models too; only the chat
     * families can answer an insight prompt.
     *
     * @return array<int, string>
     */
    public function models(): array
    {
        $response = $this->http()
            ->withToken($this->key)
            ->get("{$this->baseUrl}/models");

        $ids = Arr::map(
            Arr::where(
                Arr::wrap($response->json('data')),
                fn (mixed $model) => \is_array($model) && \is_string($model['id'] ?? null),
            ),
            fn (array $model) => (string) $model['id'],
        );

        return array_values(array_filter(
            $ids,
            fn (string $id) => Str::startsWith($id, ['gpt-', 'o1', 'o3', 'o4', 'chatgpt-']),
        ));
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    protected function ask(string $system, string $prompt, array $schema): ?string
    {
        $response = $this->http()
            ->withToken($this->key)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'order_insight',
                        'strict' => true,
                        'schema' => $schema,
                    ],
                ],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        return $response->json('choices.0.message.content');
    }
}
