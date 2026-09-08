<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get a setting value by key with a fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'float' => (float)$setting->value,
            'int' => (int)$setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            default => $setting->value,
        };
    }

    /**
     * Set / update a setting value.
     */
    public static function set(string $key, mixed $value, ?string $type = null, ?string $group = null, ?string $label = null, ?string $description = null): self
    {
        $setting = self::firstOrNew(['key' => $key]);
        $setting->value = (string)$value;
        if ($type) $setting->type = $type;
        if ($group) $setting->group = $group;
        if ($label) $setting->label = $label;
        if ($description) $setting->description = $description;
        $setting->save();

        return $setting;
    }
}
