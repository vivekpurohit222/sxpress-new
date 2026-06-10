<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPodFieldsToGrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Per master doc section 9.3 - POD (Proof of Delivery) Upload
     * - Add delivery_status ENUM('pending', 'in_transit', 'delivered')
     * - Add pod_file (path to uploaded POD document)
     * - Add pod_date (date POD was uploaded/received)
     * - Add delivered_at (actual delivery timestamp)
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('grs', 'delivery_status')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->enum('delivery_status', ['pending', 'in_transit', 'delivered'])->default('pending')->after('status');
            });
        }

        if (!Schema::hasColumn('grs', 'pod_file')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->string('pod_file', 255)->nullable()->after('delivery_status');
            });
        }

        if (!Schema::hasColumn('grs', 'pod_date')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->date('pod_date')->nullable()->after('pod_file');
            });
        }

        if (!Schema::hasColumn('grs', 'delivered_at')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->timestamp('delivered_at')->nullable()->after('pod_date');
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
            if (Schema::hasColumn('grs', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
            if (Schema::hasColumn('grs', 'pod_date')) {
                $table->dropColumn('pod_date');
            }
            if (Schema::hasColumn('grs', 'pod_file')) {
                $table->dropColumn('pod_file');
            }
            if (Schema::hasColumn('grs', 'delivery_status')) {
                $table->dropColumn('delivery_status');
            }
        });
    }
}