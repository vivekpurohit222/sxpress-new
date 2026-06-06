<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Gr extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'gr_no',
        'from_dest',
        'to_dest',
        'copy_date',
        'consignor',
        'nor_adress',
        'nor_gst_no',
        'consignee',
        'nee_adress',
        'nee_gst_no',
        'nugs',
        'meth',
        'eway_bill_number',
        'bill_amount',
        'paid',
        'to_pay',
        'other',
        'description',
        'pm',
        'weight',
        'frieght_amount',
        'sur_ch',
        'c_r',
        'bc_amount',
        'total_amount',
    ];

    /**
     * Get the latest GR number
     */
    public static function latestGrNumber()
    {
        $latest = self::latest()->first();
        return $latest ? $latest->gr_no : null;
    }
}
