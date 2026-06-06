<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTruckdriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('truckdrivers', function (Blueprint $table) {
             $table->id();  

            $table->string('driver_name',191);
            $table->string('truck_no',20)->unique();
            $table->string('license', 20)->unique();
            $table->string('driver_address');
            $table->string('mobile_no1',11)->unique();
            $table->string('mobile_no2',11)->unique()->nullable();
            $table->timestamps();
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('truckdrivers');
    }
}
