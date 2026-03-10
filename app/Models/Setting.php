<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting?->value ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, ?string $value, array $meta = []): void
    {
        static::updateOrCreate(
            ['key' => $key],
            array_merge([
                'value' => $value,
                'type' => 'string',
                'group' => 'general',
            ], $meta)
        );
    }
}
