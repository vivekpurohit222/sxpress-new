<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `settings` table — application-level key/value config.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §11 (Missing tables — settings)
 * - docs/master-execution-roadmap.md        §2.12, §5.20
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the schema. 80% on the seed keys (depends on
 *             which configs the user wants to externalize).
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\Setting (table: settings)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\SettingController
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * New table — no overlap with any existing table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string / int / float / bool / json
            $table->string('group', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('group', 'idx_settings_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
