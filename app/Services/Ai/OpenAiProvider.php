<?php

namespace App\Services\Ai;

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
