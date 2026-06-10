<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consignee extends Model
{
    use HasFactory;

    protected $fillable = [
        'consignee_name',
        'consignee_code',
        'gst_no',
        'pan_no',
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'email',
        'contact_person',
        'mobile',
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
        return $query->where('consignee_name', 'like', "%{$term}%")
                     ->orWhere('consignee_code', 'like', "%{$term}%")
                     ->orWhere('gst_no', 'like', "%{$term}%")
                     ->orWhere('city', 'like', "%{$term}%");
    }
}