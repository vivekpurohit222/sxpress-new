<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consignor extends Model
{
    use HasFactory;

    protected $fillable = [
        'consignor_name',
        'consignor_code',
        'gst_no',
        'pan_no',
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'rate_per_nug',
        'rate_per_kg',
        'email',
        'contact_person',
        'mobile',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'rate_per_nug' => 'decimal:2',
        'rate_per_kg' => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('consignor_name', 'like', "%{$term}%")
                     ->orWhere('consignor_code', 'like', "%{$term}%")
                     ->orWhere('gst_no', 'like', "%{$term}%")
                     ->orWhere('city', 'like', "%{$term}%");
    }
}