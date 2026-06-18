<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GstSetting extends Model
{
    use HasFactory;

    protected $table = 'gst_settings';

    protected $fillable = ['key', 'value'];

    // ─── Static Helpers ──────────────────────────────────────────

    /**
     * Retrieve a GST setting value by key.
     */
    public static function get(string $key, $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Set a GST setting value (creates or updates).
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
