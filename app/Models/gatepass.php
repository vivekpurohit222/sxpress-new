<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class gatepass extends Model
{
    use HasFactory;
    protected $fillable = [
        'gp_no',
        'gp_date',
		'm_s',
		'from_dest',
		'to_dest',
		'gr_no',	
		'weight',
		'nugs',
		'pm',
		'frieght_amount',
		'labour_amount',
		'other',
		'dc_amount',	
		'total_amount',
		'note',
    ];
}
