<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 150);
            $table->string('account_number', 30);
            $table->string('ifsc_code', 15)->nullable();
            $table->string('branch_name', 150)->nullable();
            $table->string('account_type', 30)->default('current'); // current, savings
            $table->unsignedBigInteger('account_id'); // FK → accounts table (linked ledger account)
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('office', 100)->nullable(); // null = company-wide
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts');
        });

        // Add cheque & reconciliation fields to ledger_entries
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->string('cheque_no', 20)->nullable()->after('narration');
            $table->date('cheque_date')->nullable()->after('cheque_no');
            $table->enum('reconciliation_status', ['uncleared', 'cleared', 'bounced'])->default('uncleared')->after('cheque_date');
            $table->date('cleared_date')->nullable()->after('reconciliation_status');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropColumn(['cheque_no', 'cheque_date', 'reconciliation_status', 'cleared_date']);
        });
        Schema::dropIfExists('bank_accounts');
    }
};
