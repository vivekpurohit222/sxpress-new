<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no', 30)->unique();
            $table->enum('voucher_type', ['receipt', 'payment', 'contra', 'journal']);
            $table->date('voucher_date');
            $table->string('narration', 500)->default('');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('branch', 100);
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('reference_type', 50)->nullable(); // gr, freight_memo, expense, manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['voucher_type', 'voucher_date']);
            $table->index(['branch', 'voucher_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
