<?php

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * Runtime configuration for one assistant, so an operator can point the insight
 * feature at a different provider without a deploy.
 *
 * @property int $id
 * @property AiProvider $provider
 * @property string $model
 * @property string|null $api_key
 * @property bool $is_active
 */
class AiSetting extends Model
{
    protected $guarded = [];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => false,
    ];

    public function hasKey(): bool
    {
        return filled($this->api_key);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }
}
