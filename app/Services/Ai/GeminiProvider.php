<?php

namespace App\Services\Ai;

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
