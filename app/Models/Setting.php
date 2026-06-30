<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = [
        'key_name',
        'value',
    ];

    protected $casts = [
        'value' => 'float',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::query()->where('key_name', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, mixed $value): self
    {
        return self::query()->updateOrCreate(
            ['key_name' => $key],
            ['value' => (string) $value]
        );
    }
}
