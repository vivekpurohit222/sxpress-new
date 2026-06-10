<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchSerialsTable extends Migration
{
    /**
     * Create branch_serials table.
     * Per SXPRESS_LOGIC_SKILL section 13 - Serial Number Assignment by SuperAdmin.
     *
     * Stores the starting GR number for each branch.
     * Once a branch has GRs, the serial is locked (cannot be changed).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('branch_serials')) {
            Schema::create('branch_serials', function (Blueprint $table) {
                $table->id();
                $table->string('office', 100)->unique(); // matches branches.branch_name or users.office
                $table->string('gr_prefix', 5);
                $table->unsignedInteger('start_from')->default(1);
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('office');
            });
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('branch_serials');
    }
}