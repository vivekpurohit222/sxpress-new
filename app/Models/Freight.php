<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Freight extends Model
{
    use HasFactory;

    protected $fillable = [
        'fm_no',
        'fm_date',
        'from_dest',
        'to_dest',
        'truck_no',
        'entry_1',
        'entry_1_amount',
        'entry_2',
        'entry_2_amount',
        'entry_3',
        'entry_3_amount',
        'entry_4',
        'entry_4_amount',
        'total_amount',
        'truck_freight',
        'commission',
        'other_charges',
        'extra',
        'balance_to_sn',
        'note',
    ];
}
