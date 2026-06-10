<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGatepassGrPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Per master doc section 9.4 - Gatepass ↔ GR Relationship
     * Create pivot table to link gatepasses with GRs
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('gatepass_gr')) {
            Schema::create('gatepass_gr', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gatepass_id');
                $table->unsignedBigInteger('gr_id');
                $table->string('gr_no', 20); // Denormalized for easier queries
                $table->timestamps();

                // Foreign keys
                $table->foreign('gatepass_id')->references('id')->on('gatepasses')->onDelete('cascade');
                $table->foreign('gr_id')->references('id')->on('grs')->onDelete('cascade');

                // Unique constraint to prevent duplicate links
                $table->unique(['gatepass_id', 'gr_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gatepass_gr');
    }
}