<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsignorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('consignors');
        Schema::create('consignors', function (Blueprint $table) {
            $table->id();
            $table->string('consignor_name', 191);
            $table->string('consignor_code', 20)->unique();
            $table->string('gst_no', 20)->nullable();
            $table->string('pan_no', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consignors');
    }
}