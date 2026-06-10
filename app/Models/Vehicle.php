<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_number',
        'vehicle_type',
        'chassis_no',
        'engine_no',
        'capacity',
        'capacity_unit',
        'insurance_date',
        'tax_date',
        'permit_date',
        'owner_name',
        'owner_phone',
        'status',
        'is_own',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
        'insurance_date' => 'date',
        'tax_date' => 'date',
        'permit_date' => 'date',
        'is_own' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('vehicle_number', 'like', "%{$term}%")
                     ->orWhere('vehicle_type', 'like', "%{$term}%")
                     ->orWhere('owner_name', 'like', "%{$term}%");
    }

    public function isInsuranceValid(): bool
    {
        return $this->insurance_date && $this->insurance_date->gte(now());
    }

    public function isTaxValid(): bool
    {
        return $this->tax_date && $this->tax_date->gte(now());
    }

    public function isPermitValid(): bool
    {
        return $this->permit_date && $this->permit_date->gte(now());
    }
}