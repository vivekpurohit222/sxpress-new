<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('routes');
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_name', 191);
            $table->string('route_code', 20)->unique();
            $table->unsignedBigInteger('origin_station_id')->nullable();
            $table->unsignedBigInteger('destination_station_id')->nullable();
            $table->decimal('distance_km', 10, 2)->default(0);
            $table->decimal('duration_hours', 10, 2)->default(0);
            $table->decimal('base_freight', 12, 2)->default(0);
            $table->text('via_locations')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->foreign('origin_station_id')->references('id')->on('stations')->onDelete('set null');
            $table->foreign('destination_station_id')->references('id')->on('stations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes');
    }
}