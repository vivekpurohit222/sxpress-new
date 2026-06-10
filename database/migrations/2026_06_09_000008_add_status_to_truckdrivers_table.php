<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToTruckdriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('truckdrivers', function (Blueprint $table) {
            $table->boolean('status')->default(1)->after('driver_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('truckdrivers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}