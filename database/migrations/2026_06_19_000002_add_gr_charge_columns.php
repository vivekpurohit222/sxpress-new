<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grs', function (Blueprint $table) {
            if (!Schema::hasColumn('grs', 'labour')) {
                $table->decimal('labour', 12, 2)->default(0)->after('sur_ch');
            }
            if (!Schema::hasColumn('grs', 'dd')) {
                $table->decimal('dd', 12, 2)->default(0)->after('labour');
            }
            if (!Schema::hasColumn('grs', 'local_charge')) {
                $table->decimal('local_charge', 12, 2)->default(0)->after('dd');
            }
            if (!Schema::hasColumn('grs', 'rate_type')) {
                $table->enum('rate_type', ['by_nugs', 'by_weight'])->nullable()->after('local_charge');
            }
            if (!Schema::hasColumn('grs', 'rate')) {
                $table->decimal('rate', 10, 2)->default(0)->after('rate_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grs', function (Blueprint $table) {
            $columns = [];
            foreach (['labour', 'dd', 'local_charge', 'rate_type', 'rate'] as $col) {
                if (Schema::hasColumn('grs', $col)) {
                    $columns[] = $col;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
