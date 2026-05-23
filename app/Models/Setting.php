<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get setting value with type casting
     */
    public function getValue()
    {
        return match ($this->setting_type) {
            'number' => (float) $this->setting_value,
            'boolean' => $this->setting_value === '1' || $this->setting_value === true,
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }

    /**
     * Get or create setting
     */
    public static function getSetting($key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        return $setting ? $setting->getValue() : $default;
    }

    /**
     * Set or update setting
     */
    public static function setSetting($key, $value, $type = 'text')
    {
        return static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value, 'setting_type' => $type]
        );
    }
}
