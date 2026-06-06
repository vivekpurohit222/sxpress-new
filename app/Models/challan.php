<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class challan extends Model
{
    use HasFactory;

    protected $fillable = [
	'challan_no',
'from_dest',
'challan_date',
'to_dest',
'truck_no',
'driver_name',
'license',
'owner_name',
'note',
'challan_total',
    ];
}

