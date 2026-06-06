<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChallanIteamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('challan_iteams', function (Blueprint $table) {
            $table->id();
            $table->string('gr_no',8)->unique();
            $table->string('challan_no');
            $table->integer('nugs');
            $table->string('meth');  
            $table->text('description');  
            $table->decimal('weight');
            $table->decimal('paid')->nullable();
            $table->decimal('to_pay')->nullable();
            $table->decimal('sur_ch');
            $table->decimal('c_r');
            $table->decimal('other');
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
        Schema::dropIfExists('challan_iteams');
    }
}
