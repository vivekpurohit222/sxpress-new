<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToUsersTable extends Migration
{
    /**
     * Add is_active column to users table.
     * Per SXPRESS_LOGIC_SKILL section 14 - Login Guard to block inactive users.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('office');
            });
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
}