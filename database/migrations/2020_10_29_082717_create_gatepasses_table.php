<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGatepassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gatepasses', function (Blueprint $table) {
              $table->id();
              $table->integer('gp_no');
            $table->string('m_s');
            $table->string('gp_date',11);
            $table->string('from_dest', 13);
            $table->string('to_dest', 13);
            $table->string('gr_no', 15)->unique();
            $table->integer('weight');
            $table->integer('nugs');
            $table->string('pm');
            $table->integer('frieght_amount');
            $table->integer('labour_amount');
            $table->integer('other');
            $table->integer('dc_amount');
            $table->integer('total_amount');
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
        Schema::dropIfExists('gatepasses');
    }
}