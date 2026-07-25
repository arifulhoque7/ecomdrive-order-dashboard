<?php

namespace App\Http\Requests\Settings;

use App\Enums\AiProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'model' => ['required', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:512'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'model.required' => 'Choose a model, or refresh the list to load one.',
        ];
    }

    public function provider(): AiProvider
    {
        return $this->enum('provider', AiProvider::class);
    }
}
