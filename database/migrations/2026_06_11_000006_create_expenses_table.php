<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no', 30)->unique();
            $table->date('expense_date');
            $table->enum('expense_type', ['diesel', 'driver_salary', 'repair', 'tyre', 'office', 'branch', 'misc']);
            $table->string('description', 300);
            $table->decimal('amount', 14, 2);
            $table->string('paid_to', 200)->default('');
            $table->unsignedBigInteger('account_id'); // expense ledger account
            $table->unsignedBigInteger('paid_from_account_id')->nullable(); // cash or bank
            $table->unsignedBigInteger('voucher_id')->nullable(); // linked voucher (auto-created)
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('branch', 100);
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->index(['branch', 'expense_date']);
            $table->index(['expense_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
