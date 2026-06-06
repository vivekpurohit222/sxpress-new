<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class truckdriver extends Model
{
    use HasFactory;

     protected $fillable = [
        
        'driver_name',
			'truck_no',
			'license',
		'mobile_no1',
		'mobile_no2',
		'driver_address',
	
    ];
}
