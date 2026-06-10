<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_name',
        'route_code',
        'origin_station_id',
        'destination_station_id',
        'distance_km',
        'duration_hours',
        'base_freight',
        'via_locations',
        'status',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'duration_hours' => 'decimal:2',
        'base_freight' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function originStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'origin_station_id');
    }

    public function destinationStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'destination_station_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('route_name', 'like', "%{$term}%")
                     ->orWhere('route_code', 'like', "%{$term}%");
    }
}