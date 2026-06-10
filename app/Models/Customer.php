<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_code',
        'customer_type',
        'gst_no',
        'pan_no',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_pincode',
        'shipping_address',
        'phone',
        'email',
        'contact_person',
        'credit_limit',
        'payment_terms',
        'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('customer_name', 'like', "%{$term}%")
                     ->orWhere('customer_code', 'like', "%{$term}%")
                     ->orWhere('gst_no', 'like', "%{$term}%")
                     ->orWhere('email', 'like', "%{$term}%");
    }
}