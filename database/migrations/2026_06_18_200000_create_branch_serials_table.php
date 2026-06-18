<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Redesign branch_serials table for multi-module serial number system.
     * Supports GR, Challan, Freight Memo, Gate Pass with per-branch FY-based ranges.
     */
    public function up()
    {
        // Drop old branch_serials table if it exists
        Schema::dropIfExists('branch_serials');

        Schema::create('branch_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->enum('module', ['gr', 'challan', 'freight_memo', 'gate_pass']);
            $table->string('fy_year', 5); // e.g. '26/27'
            $table->unsignedInteger('range_start');
            $table->unsignedInteger('range_end');
            $table->unsignedInteger('current_value')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'module', 'fy_year']);
        });

        // ALTER gatepasses.gp_no from INT to VARCHAR(20)
        DB::statement("ALTER TABLE gatepasses MODIFY COLUMN gp_no VARCHAR(20) NOT NULL");

        // Seed initial serial data for all existing branches (full range 1-999999, current FY)
        $this->seedExistingBranches();
    }

    public function down()
    {
        Schema::dropIfExists('branch_serials');

        // Revert gp_no back to INT (note: data loss if strings exist)
        DB::statement("ALTER TABLE gatepasses MODIFY COLUMN gp_no INT NOT NULL");
    }

    /**
     * Seed initial serial ranges for all existing branches.
     */
    private function seedExistingBranches()
    {
        $fyYear = $this->currentFyPrefix();
        $branches = DB::table('branches')->get();
        $modules = ['gr', 'challan', 'freight_memo', 'gate_pass'];

        foreach ($branches as $branch) {
            foreach ($modules as $module) {
                DB::table('branch_serials')->insert([
                    'branch_id'     => $branch->id,
                    'module'        => $module,
                    'fy_year'       => $fyYear,
                    'range_start'   => 1,
                    'range_end'     => 999999,
                    'current_value' => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    private function currentFyPrefix(): string
    {
        $month = (int) date('n');
        $year = (int) date('Y');

        if ($month >= 4) {
            return substr($year, 2) . '/' . substr($year + 1, 2);
        } else {
            return substr($year - 1, 2) . '/' . substr($year, 2);
        }
    }
};
