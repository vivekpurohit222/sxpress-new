<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('rate_per_nug', 10, 2)->default(0)->after('phone');
            $table->decimal('rate_per_kg', 10, 2)->default(0)->after('rate_per_nug');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['rate_per_nug', 'rate_per_kg']);
        });
    }
};
