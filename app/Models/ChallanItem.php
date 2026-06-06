<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'challan_no',
        'gr_no',
        'nugs',
        'meth',
        'description',
        'weight',
        'paid',
        'to_pay',
        'sur_ch',
        'c_r',
        'other',
    ];
}
