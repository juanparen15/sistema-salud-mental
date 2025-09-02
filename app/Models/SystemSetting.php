<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsJson;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'value' => AsJson::class,
        'is_public' => 'boolean',
    ];

    public function getValueAttribute($value)
    {
        $decoded = json_decode($value, true);
        
        return match ($this->type) {
            'boolean' => (bool) $decoded,
            'integer' => (int) $decoded,
            'float' => (float) $decoded,
            'array' => (array) $decoded,
            default => $decoded,
        };
    }

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => json_encode($value),
                'type' => $type,
            ]
        );
    }

    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }
}