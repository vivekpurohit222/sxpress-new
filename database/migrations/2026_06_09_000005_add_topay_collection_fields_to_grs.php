<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTopayCollectionFieldsToGrs extends Migration
{
    /**
     * Run the migrations.
     *
     * Per master doc section 9.6 - TO-PAY Collection Tracking
     * - Add topay_collected BOOLEAN
     * - Add topay_collected_date DATE
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('grs', 'topay_collected')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->boolean('topay_collected')->default(false)->after('to_pay');
            });
        }

        if (!Schema::hasColumn('grs', 'topay_collected_date')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->date('topay_collected_date')->nullable()->after('topay_collected');
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
            if (Schema::hasColumn('grs', 'topay_collected_date')) {
                $table->dropColumn('topay_collected_date');
            }
            if (Schema::hasColumn('grs', 'topay_collected')) {
                $table->dropColumn('topay_collected');
            }
        });
    }
}