<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class truckdriver extends Model
{
    use HasFactory;

    protected $table = 'truckdrivers';

    protected $fillable = [
        'driver_name',
        'truck_no',
        'license',
        'mobile_no1',
        'mobile_no2',
        'driver_address',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('driver_name', 'like', "%{$term}%")
                     ->orWhere('truck_no', 'like', "%{$term}%")
                     ->orWhere('license', 'like', "%{$term}%")
                     ->orWhere('mobile_no1', 'like', "%{$term}%");
    }
}