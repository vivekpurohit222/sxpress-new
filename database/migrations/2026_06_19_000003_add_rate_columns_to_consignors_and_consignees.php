<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignors', function (Blueprint $table) {
            if (!Schema::hasColumn('consignors', 'rate_per_nug')) {
                $table->decimal('rate_per_nug', 10, 2)->default(0)->after('phone');
            }
            if (!Schema::hasColumn('consignors', 'rate_per_kg')) {
                $table->decimal('rate_per_kg', 10, 2)->default(0)->after('rate_per_nug');
            }
        });

        Schema::table('consignees', function (Blueprint $table) {
            if (!Schema::hasColumn('consignees', 'rate_per_nug')) {
                $table->decimal('rate_per_nug', 10, 2)->default(0)->after('phone');
            }
            if (!Schema::hasColumn('consignees', 'rate_per_kg')) {
                $table->decimal('rate_per_kg', 10, 2)->default(0)->after('rate_per_nug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consignors', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('consignors', 'rate_per_nug')) $cols[] = 'rate_per_nug';
            if (Schema::hasColumn('consignors', 'rate_per_kg')) $cols[] = 'rate_per_kg';
            if (!empty($cols)) $table->dropColumn($cols);
        });

        Schema::table('consignees', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('consignees', 'rate_per_nug')) $cols[] = 'rate_per_nug';
            if (Schema::hasColumn('consignees', 'rate_per_kg')) $cols[] = 'rate_per_kg';
            if (!empty($cols)) $table->dropColumn($cols);
        });
    }
};
