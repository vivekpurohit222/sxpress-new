<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add pod_note and pod_uploaded_by fields to grs table.
     * These were referenced in GrController but missing from the database.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasColumn('grs', 'pod_note')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->string('pod_note', 500)->nullable()->after('pod_date');
            });
        }

        if (!Schema::hasColumn('grs', 'pod_uploaded_by')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->unsignedBigInteger('pod_uploaded_by')->nullable()->after('pod_note');
                $table->foreign('pod_uploaded_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('grs', function (Blueprint $table) {
            if (Schema::hasColumn('grs', 'pod_uploaded_by')) {
                $table->dropForeign(['pod_uploaded_by']);
                $table->dropColumn('pod_uploaded_by');
            }
            if (Schema::hasColumn('grs', 'pod_note')) {
                $table->dropColumn('pod_note');
            }
        });
    }
};