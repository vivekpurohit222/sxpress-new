<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChallansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('challans', function (Blueprint $table) {
             $table->string('challan_no')->unique();
            $table->string('from_dest', 13);
            $table->string('challan_date');
            $table->string('to_dest', 13);
            $table->string('truck_no', 20);
            $table->string('driver_name');
            $table->string('license',20);
            $table->string('owner_name');
            $table->text('note')->nullable();
            $table->decimal('challan_total')->nullable();
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
        Schema::dropIfExists('challans');
    }
}
