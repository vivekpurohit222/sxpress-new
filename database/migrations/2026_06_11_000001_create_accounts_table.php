<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 150);
            $table->enum('type', ['asset', 'liability', 'income', 'expense', 'equity']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_group')->default(false);
            $table->string('branch', 100)->nullable(); // null = company-wide
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->boolean('is_system')->default(false); // cannot delete
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();
            $table->index(['type', 'is_active']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
