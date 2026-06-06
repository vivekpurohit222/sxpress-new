<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the missing business constraint for challan lines.
 *
 * Source: docs/database-reconstruction-report.md §8.4 recommends a composite
 * UNIQUE on (challan_no, gr_no): a GR can appear on multiple challans over
 * time, but should appear at most once within the same challan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('challan_iteams')) {
            return;
        }

        Schema::table('challan_iteams', function (Blueprint $table) {
            $table->unique(['challan_no', 'gr_no'], 'uk_challan_iteams_challan_no_gr_no');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('challan_iteams')) {
            return;
        }

        Schema::table('challan_iteams', function (Blueprint $table) {
            $table->dropUnique('uk_challan_iteams_challan_no_gr_no');
        });
    }
};
