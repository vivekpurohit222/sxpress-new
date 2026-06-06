<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Laravel's database notifications table.
 *
 * Source: docs/database-reconstruction-report.md §11 and
 * docs/final-discovery-report.md §5 list `notifications` as a missing table
 * for app-level notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable()->index('idx_notifications_read_at');
            $table->timestamps();

            $table->index('created_at', 'idx_notifications_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
