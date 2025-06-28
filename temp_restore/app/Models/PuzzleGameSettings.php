<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuzzleGameSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'is_public',
        'category',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getSetting($key, $default = null)
    {
        $setting = self::where('setting_key', $key)
            ->where('is_active', true)
            ->first();

        if (!$setting) {
            return $default;
        }

        switch ($setting->setting_type) {
            case 'integer':
                return (int)$setting->setting_value;
            case 'boolean':
                return (bool)$setting->setting_value;
            case 'json':
                return json_decode($setting->setting_value, true);
            default:
                return $setting->setting_value;
        }
    }

    public static function setSetting($key, $value, $type = 'string', $description = null)
    {
        $setting = self::where('setting_key', $key)->first();

        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        if ($setting) {
            $setting->update([
                'setting_value' => $value,
                'setting_type' => $type,
                'description' => $description
            ]);
        } else {
            self::create([
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_type' => $type,
                'description' => $description,
                'is_public' => false,
                'category' => 'general',
                'display_order' => 0,
                'is_active' => true
            ]);
        }
    }

    public static function getPublicSettings()
    {
        return self::where('is_public', true)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('category')
            ->get();
    }
}
