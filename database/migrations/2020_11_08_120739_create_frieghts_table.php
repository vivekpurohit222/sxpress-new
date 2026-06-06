<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFrieghtsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('frieghts', function (Blueprint $table) {
              $table->id();
            $table->string('fm_no',10);
            $table->string('fm_date',11);
            $table->string('from_dest', 13);
            $table->string('to_dest', 13);
            $table->string('truck_no', 10);
            $table->string('entry_1', 13);
            $table->integer('entry_1_amount');
            $table->string('entry_2', 13);
            $table->integer('entry_2_amount');
            $table->string('entry_3', 13);
            $table->integer('entry_3_amount');
            $table->string('entry_4',13);
            $table->integer('entry_4_amount');
            $table->integer('total_amount');
            $table->integer('truck_freight');
            $table->integer('commission');
            $table->integer('other_charges');
            $table->integer('extra');
            $table->integer('balance_to_sn');
            $table->text('note');
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
        Schema::dropIfExists('frieghts');
    }
}
