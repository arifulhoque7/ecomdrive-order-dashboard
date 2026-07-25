<?php

namespace App\Services\Ai;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Google Gemini generateContent, pinned to a JSON response schema.
 */
class GeminiProvider extends Provider
{
    public function name(): string
    {
        return 'gemini';
    }

    /**
     * @return array<int, string>
     */
    public function models(): array
    {
        $response = $this->http()
            ->withHeaders(['x-goog-api-key' => $this->key])
            ->get("{$this->baseUrl}/models", ['pageSize' => 200]);

        $usable = Arr::where(
            Arr::wrap($response->json('models')),
            fn (mixed $model) => \is_array($model)
                && \is_string($model['name'] ?? null)
                && \in_array('generateContent', Arr::wrap($model['supportedGenerationMethods'] ?? []), true),
        );

        return array_values(Arr::map(
            $usable,
            fn (array $model) => Str::after((string) $model['name'], 'models/'),
        ));
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    protected function ask(string $system, string $prompt, array $schema): ?string
    {
        $response = $this->http()
            ->withHeaders(['x-goog-api-key' => $this->key])
            ->post("{$this->baseUrl}/models/{$this->model}:generateContent", [
                'systemInstruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $this->withoutUnsupportedKeywords($schema),
                ],
            ]);

        return $response->json('candidates.0.content.parts.0.text');
    }

    /**
     * Gemini rejects JSON Schema keywords it does not implement.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    protected function withoutUnsupportedKeywords(array $schema): array
    {
        unset($schema['additionalProperties']);

        return $schema;
    }
}
