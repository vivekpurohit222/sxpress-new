<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create a Spatie Media Library compatible `media` table.
 *
 * Source: docs/database-reconstruction-report.md §11 and
 * docs/final-discovery-report.md §5 list `media` as a missing table for POD
 * images, GR scans, and gatepass attachments. This migration only adds the
 * database table; package installation and model traits remain out of scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media')) {
            return;
        }

        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('model');
            $table->uuid('uuid')->nullable()->unique('uk_media_uuid');
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index('idx_media_order_column');
            $table->nullableTimestamps();

            $table->index('collection_name', 'idx_media_collection_name');
            $table->index('created_at', 'idx_media_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
