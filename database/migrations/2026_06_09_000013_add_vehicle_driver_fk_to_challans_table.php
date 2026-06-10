<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVehicleDriverFkToChallansTable extends Migration
{
    /**
     * Add vehicle_id, driver_id, and total_weight columns to challans table.
     * Per SXPRESS_LOGIC_SKILL section 6 - Challan + Challan Items Logic.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('challans', 'vehicle_id')) {
            Schema::table('challans', function (Blueprint $table) {
                $table->foreignId('vehicle_id')->nullable()
                    ->constrained('vehicles')->nullOnDelete()
                    ->after('to_branch_id');
            });
        }

        if (!Schema::hasColumn('challans', 'driver_id')) {
            Schema::table('challans', function (Blueprint $table) {
                $table->foreignId('driver_id')->nullable()
                    ->constrained('truckdrivers')->nullOnDelete()
                    ->after('vehicle_id');
            });
        }

        if (!Schema::hasColumn('challans', 'total_weight')) {
            Schema::table('challans', function (Blueprint $table) {
                $table->decimal('total_weight', 10, 2)->default(0)->after('status');
            });
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('challans', function (Blueprint $table) {
            if (Schema::hasColumn('challans', 'total_weight')) {
                $table->dropColumn('total_weight');
            }
            if (Schema::hasColumn('challans', 'driver_id')) {
                $table->dropForeign(['driver_id']);
                $table->dropColumn('driver_id');
            }
            if (Schema::hasColumn('challans', 'vehicle_id')) {
                $table->dropForeign(['vehicle_id']);
                $table->dropColumn('vehicle_id');
            }
        });
    }
}