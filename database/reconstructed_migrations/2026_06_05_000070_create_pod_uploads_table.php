<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `pod_uploads` table — proof of delivery images / signatures
 * attached to a GR.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §11 (Missing tables — pod_uploads)
 * - docs/erd.md                             §2  (POD_UPLOAD entity)
 * - docs/business-workflows.md              §8  (POD lifecycle)
 * - docs/master-execution-roadmap.md        §2.7, §5.11
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the schema (declared in the modernized ERD).
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\PodUpload (table: pod_uploads)
 * - App\Models\Gr             (FK: pod_uploads.gr_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\PodUploadController  (upload, list, show)
 * - dash\GrController               (POD view per GR)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * New table — no overlap with any existing table.
 * The gr_id FK is declared ON DELETE RESTRICT — POD is preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pod_uploads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('gr_id');
            $table->string('file_path', 255);
            $table->string('signature_path', 255)->nullable();
            $table->string('received_by_name', 150)->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('gr_id', 'idx_pod_uploads_gr_id');
            $table->index('delivered_at', 'idx_pod_uploads_delivered_at');
            $table->index('created_by_id', 'idx_pod_uploads_created_by_id');

            $table->foreign('gr_id')->references('id')->on('grs')
                  ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')
                  ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pod_uploads');
    }
};
