<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_name',
        'station_code',
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'email',
        'contact_person',
        'mobile',
        'branch_id',
        'is_warehouse',
        'status',
    ];

    protected $casts = [
        'is_warehouse' => 'boolean',
        'status' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function originRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'origin_station_id');
    }

    public function destinationRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'destination_station_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('station_name', 'like', "%{$term}%")
                     ->orWhere('station_code', 'like', "%{$term}%")
                     ->orWhere('city', 'like', "%{$term}%");
    }
}