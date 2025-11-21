<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'json' => json_decode($setting->value, true),
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null): bool
    {
        $setting = static::where('key', $key)->first();

        $valueToStore = match($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            default => (string) $value,
        };

        if ($setting) {
            $setting->value = $valueToStore;
            $setting->type = $type;
            if ($description !== null) {
                $setting->description = $description;
            }
            return $setting->save();
        }

        return static::create([
            'key' => $key,
            'value' => $valueToStore,
            'type' => $type,
            'description' => $description,
        ]) !== null;
    }

    /**
     * Get super admin theme colors
     */
    public static function getSuperAdminTheme(): array
    {
        return [
            'primary_color' => static::get('super_admin_primary_color', '#1E3A5F'),
            'secondary_color' => static::get('super_admin_secondary_color', '#86EFAC'),
            'accent_color' => static::get('super_admin_accent_color', '#FFFFFF'),
        ];
    }
}
