<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAuditAndTopayFieldsToGrsTable extends Migration
{
    /**
     * Add status audit fields and topay_collected_by to grs table.
     * Per SXPRESS_LOGIC_SKILL sections 3, 8, and 15.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('grs', 'status_updated_at')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->timestamp('status_updated_at')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('grs', 'status_updated_by')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->unsignedBigInteger('status_updated_by')->nullable()->after('status_updated_at');
                $table->foreign('status_updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('grs', 'topay_collected_by')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->unsignedBigInteger('topay_collected_by')->nullable()->after('topay_collected_date');
                $table->foreign('topay_collected_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('grs', function (Blueprint $table) {
            if (Schema::hasColumn('grs', 'topay_collected_by')) {
                $table->dropForeign(['topay_collected_by']);
                $table->dropColumn('topay_collected_by');
            }
            if (Schema::hasColumn('grs', 'status_updated_by')) {
                $table->dropForeign(['status_updated_by']);
                $table->dropColumn('status_updated_by');
            }
            if (Schema::hasColumn('grs', 'status_updated_at')) {
                $table->dropColumn('status_updated_at');
            }
        });
    }
}