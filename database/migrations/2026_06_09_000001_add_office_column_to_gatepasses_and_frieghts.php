<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOfficeColumnToGatepassesAndFrieghts extends Migration
{
    /**
     * Run the migrations.
     *
     * Add office column to gatepasses and frieghts tables for branch filtering
     * Per master doc section 13 Golden Rule #1 - Data filtering by office
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('gatepasses', 'office')) {
            Schema::table('gatepasses', function (Blueprint $table) {
                $table->string('office', 100)->nullable()->after('note');
            });
        }

        if (!Schema::hasColumn('frieghts', 'office')) {
            Schema::table('frieghts', function (Blueprint $table) {
                $table->string('office', 100)->nullable()->after('note');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gatepasses', function (Blueprint $table) {
            if (Schema::hasColumn('gatepasses', 'office')) {
                $table->dropColumn('office');
            }
        });

        Schema::table('frieghts', function (Blueprint $table) {
            if (Schema::hasColumn('frieghts', 'office')) {
                $table->dropColumn('office');
            }
        });
    }
}