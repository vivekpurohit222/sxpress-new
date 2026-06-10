<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Updates gr status enum from: booked,loaded,in_transit,delivered,cancelled
     *                  to: created,dispatched,in_transit,delivered,closed,cancelled
     */
    public function up(): void
    {
        // Since DB is empty (0 GRs), we can safely modify the enum directly
        // If there were data, we'd need to first update values then change enum

        // Check current row count (safety check)
        $count = DB::table('grs')->count();
        if ($count > 0) {
            throw new \Exception("Cannot modify status enum — {$count} GR records exist. Migrate data first.");
        }

        // Drop the column and re-add with correct enum values
        // Using raw SQL because Doctrine DBAL would be needed for clean enum modification
        DB::statement("ALTER TABLE grs MODIFY COLUMN status ENUM('created','dispatched','in_transit','delivered','closed','cancelled') DEFAULT 'created' AFTER office");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE grs MODIFY COLUMN status ENUM('booked','loaded','in_transit','delivered','cancelled') DEFAULT 'booked' AFTER office");
    }
};