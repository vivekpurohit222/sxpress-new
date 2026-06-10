<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOfficeToChallansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('challans', 'office')) {
            Schema::table('challans', function (Blueprint $table) {
                $table->string('office', 100)->nullable()->after('challan_total');
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
        Schema::table('challans', function (Blueprint $table) {
            if (Schema::hasColumn('challans', 'office')) {
                $table->dropColumn('office');
            }
        });
    }
}