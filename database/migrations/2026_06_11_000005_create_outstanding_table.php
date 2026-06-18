<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outstanding', function (Blueprint $table) {
            $table->id();
            $table->enum('party_type', ['customer', 'consignor', 'consignee', 'truck_owner']);
            $table->string('party_name', 200);
            $table->enum('type', ['receivable', 'payable']);
            $table->string('invoice_ref', 50); // GR No or FM No
            $table->date('invoice_date');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('pending_amount', 14, 2); // computed: total - paid
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->string('branch', 100);
            $table->string('reference_type', 50)->nullable(); // gr, freight_memo
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['party_type', 'status']);
            $table->index(['branch', 'status']);
            $table->index(['type', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outstanding');
    }
};
