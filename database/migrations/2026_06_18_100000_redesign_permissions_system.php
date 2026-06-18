<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redesign permissions system:
 * - Add `role` column to users table (super_admin, branch_manager, agent)
 * - Create `branch_permissions` table
 * - Create `user_permissions` table
 * - Stop using Spatie tables (leave them in DB, just stop writing to them)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add role column to users if not exists
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'branch_manager', 'agent'])
                      ->default('agent')
                      ->after('email');
            });
        }

        // Branch permissions — what modules a branch is allowed to use
        Schema::create('branch_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('permission', 50); // gr, challan, freight_memo, import_challan, gate_pass, dds
            $table->timestamps();

            $table->unique(['branch_id', 'permission']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });

        // User permissions — what modules an agent is allowed to use
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('permission', 50); // gr, challan, freight_memo, import_challan, gate_pass, dds
            $table->timestamps();

            $table->unique(['user_id', 'permission']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('branch_permissions');

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
