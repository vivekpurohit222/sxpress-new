<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOfficeToGrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Add office column to grs table for branch filtering
     * Per master doc section 13 Golden Rule #1 - Data filtering by office
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('grs', 'office')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->string('office', 100)->nullable()->after('total_amount');
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
        Schema::table('grs', function (Blueprint $table) {
            if (Schema::hasColumn('grs', 'office')) {
                $table->dropColumn('office');
            }
        });
    }
}