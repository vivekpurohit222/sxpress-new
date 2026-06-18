<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gst_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        DB::table('gst_settings')->insert([
            ['key' => 'company_gst_number', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_gst_rate', 'value' => '5', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_state', 'value' => 'Gujarat', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gst_settings');
    }
};
