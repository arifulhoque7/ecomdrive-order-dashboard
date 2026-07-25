<?php

namespace App\Services\Ai;

/**
 * One assistant the order insight feature can talk to.
 */
interface InsightProvider
{
    /**
     * Ask the model to answer the prompt as JSON matching the given schema.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>|null Null when the provider is unusable, so the caller can fall back.
     */
    public function generate(string $system, string $prompt, array $schema): ?array;

    /**
     * The name recorded on the generated insight.
     */
    public function name(): string;
}
