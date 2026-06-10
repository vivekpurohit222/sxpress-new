<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVehicleDriverFkToGatepassesTable extends Migration
{
    /**
     * Add vehicle_id and driver_id FK columns to gatepasses table.
     * Per SXPRESS_LOGIC_SKILL section 5 - Gatepass Logic.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('gatepasses', 'vehicle_id')) {
            Schema::table('gatepasses', function (Blueprint $table) {
                $table->foreignId('vehicle_id')->nullable()
                    ->constrained('vehicles')->nullOnDelete()
                    ->after('to_branch_id');
            });
        }

        if (!Schema::hasColumn('gatepasses', 'driver_id')) {
            Schema::table('gatepasses', function (Blueprint $table) {
                $table->foreignId('driver_id')->nullable()
                    ->constrained('truckdrivers')->nullOnDelete()
                    ->after('vehicle_id');
            });
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('gatepasses', function (Blueprint $table) {
            if (Schema::hasColumn('gatepasses', 'driver_id')) {
                $table->dropForeign(['driver_id']);
                $table->dropColumn('driver_id');
            }
            if (Schema::hasColumn('gatepasses', 'vehicle_id')) {
                $table->dropForeign(['vehicle_id']);
                $table->dropColumn('vehicle_id');
            }
        });
    }
}