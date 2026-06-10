<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('vehicles');
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number', 20)->unique();
            $table->string('vehicle_type', 50)->nullable();
            $table->string('chassis_no', 50)->nullable();
            $table->string('engine_no', 50)->nullable();
            $table->decimal('capacity', 10, 2)->default(0);
            $table->string('capacity_unit', 20)->default('MT');
            $table->date('insurance_date')->nullable();
            $table->date('tax_date')->nullable();
            $table->date('permit_date')->nullable();
            $table->string('owner_name', 191)->nullable();
            $table->string('owner_phone', 20)->nullable();
            $table->enum('status', ['active', 'inactive', 'under_maintenance'])->default('active');
            $table->boolean('is_own')->default(0);
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
        Schema::dropIfExists('vehicles');
    }
}