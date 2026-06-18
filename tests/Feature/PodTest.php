<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gr;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PodTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    private function createDispatchedGr(): int
    {
        $office = $this->staff()->office;
        return DB::table('grs')->insertGetId([
            'gr_no' => 'POD-' . rand(10000, 99999),
            'office' => $office, 'from_dest' => $office, 'to_dest' => 'Navagam',
            'copy_date' => now(), 'consignor' => 'POD Test Consignor',
            'consignor_address' => 'Test', 'consignee' => 'POD Test Consignee',
            'consignee_address' => 'Test', 'nugs' => 2, 'meth' => 'Box',
            'weight' => 30, 'description' => 'POD test', 'pm' => '', 'eway_bill_number' => '',
            'frieght_amount' => 500, 'total_amount' => 500,
            'paid' => 1, 'to_pay' => 0, 'status' => 'dispatched',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // UPLOAD FORM
    // ─────────────────────────────────────────────────────────────────

    public function test_upload_form_loads(): void
    {
        $grId = $this->createDispatchedGr();

        $this->actingAs($this->staff())
            ->get("/gr/{$grId}/upload-pod")
            ->assertStatus(200)
            ->assertSee('Upload Proof of Delivery');

        DB::table('grs')->where('id', $grId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // IMAGE UPLOAD
    // ─────────────────────────────────────────────────────────────────

    public function test_can_upload_image_pod(): void
    {
        Storage::fake('public');
        $grId = $this->createDispatchedGr();

        $file = UploadedFile::fake()->image('pod_receipt.jpg', 800, 600)->size(1024);

        $this->actingAs($this->staff())->post("/gr/{$grId}/upload-pod", [
            'pod_file' => $file,
            'pod_date' => now()->format('Y-m-d'),
            'pod_note' => 'Delivered to warehouse guard',
        ])->assertRedirect();

        // GR should be marked as delivered
        $gr = Gr::find($grId);
        $this->assertSame('delivered', $gr->status);
        $this->assertNotNull($gr->pod_file);
        $this->assertNotNull($gr->pod_date);
        $this->assertSame('Delivered to warehouse guard', $gr->pod_note);

        // File should exist in storage
        Storage::disk('public')->assertExists($gr->pod_file);

        DB::table('grs')->where('id', $grId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // PDF UPLOAD
    // ─────────────────────────────────────────────────────────────────

    public function test_can_upload_pdf_pod(): void
    {
        Storage::fake('public');
        $grId = $this->createDispatchedGr();

        $file = UploadedFile::fake()->create('pod_document.pdf', 2048, 'application/pdf');

        $this->actingAs($this->staff())->post("/gr/{$grId}/upload-pod", [
            'pod_file' => $file,
            'pod_date' => now()->format('Y-m-d'),
        ])->assertRedirect();

        $gr = Gr::find($grId);
        $this->assertSame('delivered', $gr->status);
        $this->assertStringEndsWith('.pdf', $gr->pod_file);
        Storage::disk('public')->assertExists($gr->pod_file);

        DB::table('grs')->where('id', $grId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // VALIDATION
    // ─────────────────────────────────────────────────────────────────

    public function test_rejects_upload_without_pod_date(): void
    {
        $grId = $this->createDispatchedGr();

        $file = UploadedFile::fake()->image('test.jpg');

        $this->actingAs($this->staff())->post("/gr/{$grId}/upload-pod", [
            'pod_file' => $file,
            // pod_date missing
        ])->assertSessionHasErrors('pod_date');

        DB::table('grs')->where('id', $grId)->delete();
    }

    public function test_rejects_upload_for_created_gr(): void
    {
        $grId = DB::table('grs')->insertGetId([
            'gr_no' => 'POD-CRE-' . rand(10000, 99999),
            'office' => 'Rajkot - PN', 'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'copy_date' => now(), 'consignor' => 'X', 'consignor_address' => 'X',
            'consignee' => 'X', 'consignee_address' => 'X',
            'nugs' => 1, 'meth' => 'Bag', 'weight' => 10, 'description' => 'X',
            'pm' => '', 'eway_bill_number' => '', 'frieght_amount' => 100, 'total_amount' => 100,
            'status' => 'created',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        $this->actingAs($this->staff())->post("/gr/{$grId}/upload-pod", [
            'pod_file' => $file,
            'pod_date' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('pod_file');

        DB::table('grs')->where('id', $grId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // DOWNLOAD / PREVIEW
    // ─────────────────────────────────────────────────────────────────

    public function test_can_view_pod_file(): void
    {
        Storage::fake('public');
        $grId = $this->createDispatchedGr();

        // Upload a POD first
        $file = UploadedFile::fake()->image('pod_view_test.jpg', 400, 300);
        $this->actingAs($this->staff())->post("/gr/{$grId}/upload-pod", [
            'pod_file' => $file,
            'pod_date' => now()->format('Y-m-d'),
        ]);

        $gr = Gr::find($grId);

        // View via dedicated route
        $this->actingAs($this->staff())
            ->get(route('gr.pod', $grId))
            ->assertStatus(200);

        DB::table('grs')->where('id', $grId)->delete();
    }

    public function test_pod_view_returns_404_if_no_file(): void
    {
        $grId = $this->createDispatchedGr();

        $this->actingAs($this->staff())
            ->get(route('gr.pod', $grId))
            ->assertStatus(404);

        DB::table('grs')->where('id', $grId)->delete();
    }

    public function test_other_office_cannot_view_pod(): void
    {
        Storage::fake('public');
        // Create a GR in Navagam
        $grId = DB::table('grs')->insertGetId([
            'gr_no' => 'POD-NV-' . rand(10000, 99999),
            'office' => 'Navagam', 'from_dest' => 'Navagam', 'to_dest' => 'Rajkot - PN',
            'copy_date' => now(), 'consignor' => 'X', 'consignor_address' => 'X',
            'consignee' => 'X', 'consignee_address' => 'X',
            'nugs' => 1, 'meth' => 'Bag', 'weight' => 10, 'description' => 'X',
            'pm' => '', 'eway_bill_number' => '', 'frieght_amount' => 100, 'total_amount' => 100,
            'status' => 'dispatched', 'pod_file' => 'pods/test.jpg',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Rajkot staff cannot access Navagam POD
        $response = $this->actingAs($this->staff())
            ->get(route('gr.pod', $grId));
        $this->assertSame(403, $response->getStatusCode());

        DB::table('grs')->where('id', $grId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // STATUS UPDATE
    // ─────────────────────────────────────────────────────────────────

    public function test_upload_auto_transitions_to_delivered(): void
    {
        Storage::fake('public');
        $grId = $this->createDispatchedGr();
        $this->assertSame('dispatched', DB::table('grs')->where('id', $grId)->value('status'));

        $file = UploadedFile::fake()->image('pod_status.jpg');

        $this->actingAs($this->staff())->post("/gr/{$grId}/upload-pod", [
            'pod_file' => $file,
            'pod_date' => now()->format('Y-m-d'),
        ]);

        $this->assertSame('delivered', DB::table('grs')->where('id', $grId)->value('status'));

        DB::table('grs')->where('id', $grId)->delete();
    }

    public function test_mark_delivered_without_file(): void
    {
        $grId = $this->createDispatchedGr();

        $this->actingAs($this->staff())
            ->postJson("/gr/{$grId}/mark-delivered")
            ->assertJson(['success' => true]);

        $this->assertSame('delivered', DB::table('grs')->where('id', $grId)->value('status'));

        DB::table('grs')->where('id', $grId)->delete();
    }
}
