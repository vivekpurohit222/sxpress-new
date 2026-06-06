<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CretaeGrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grs', function (Blueprint $table) {
            $table->id();
            $table->string('gr_no',8)->unique();
            $table->string('from_dest',13);
            $table->string('to_dest',13);
            $table->string('copy_date');
            $table->string('consignor');            
            $table->string('nor_adress');
            $table->string('nor_gst_no',15);
            $table->string('consignee');
            $table->string('nee_adress');
            $table->string('nee_gst_no',15);            
            $table->integer('nugs');
            $table->string('meth');   
            $table->text('description');
            $table->string('pm');
            $table->string('eway_bill_number');
            $table->decimal('bill_amount');
            $table->decimal('weight');
            $table->boolean('paid')->default('0');
            $table->boolean('to_pay')->default('0');
            $table->decimal('frieght_amount');
            $table->decimal('sur_ch');
            $table->decimal('c_r');
            $table->decimal('other');
            $table->decimal('bc_amount');
            $table->decimal('total_amount');   
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
        //
    }
}
