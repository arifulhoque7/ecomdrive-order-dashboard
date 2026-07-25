<?php

namespace App\Enums;

enum AiProvider: string
{
    case Claude = 'claude';
    case OpenAi = 'openai';
    case Gemini = 'gemini';

    public function label(): string
    {
        return match ($this) {
            self::Claude => 'Anthropic Claude',
            self::OpenAi => 'OpenAI',
            self::Gemini => 'Google Gemini',
        };
    }

    /**
     * A sensible model to start from when the operator picks this provider.
     */
    public function defaultModel(): string
    {
        return match ($this) {
            self::Claude => 'claude-opus-5',
            self::OpenAi => 'gpt-4.1',
            self::Gemini => 'gemini-2.5-flash',
        };
    }

    /**
     * Where the key is issued, shown next to the field so an operator can find it.
     */
    public function keyUrl(): string
    {
        return match ($this) {
            self::Claude => 'https://console.anthropic.com/settings/keys',
            self::OpenAi => 'https://platform.openai.com/api-keys',
            self::Gemini => 'https://aistudio.google.com/apikey',
        };
    }
}
