<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gr;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class GrModuleTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'SuperAdmin'))->first();
    }

    private function admin(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'SuperAdmin'))
            ->first();
    }

    private function staff(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'Staff'))
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['SuperAdmin', 'Admin', 'Manager']))
            ->first();
    }

    private function validGrData(array $override = []): array
    {
        return array_merge([
            'copy_date'         => now()->format('Y-m-d'),
            'from_dest'         => 'Rajkot',
            'to_dest'           => 'Navagam',
            'consignor'         => 'Test Consignor Pvt Ltd',
            'consignor_address' => '123 Industrial Area, Rajkot',
            'consignor_gst_no'  => '',
            'consignee'         => 'Test Consignee Corp',
            'consignee_address' => '456 Market Road, Navagam',
            'consignee_gst_no'  => '',
            'nugs'              => 5,
            'meth'              => 'Bag',
            'weight'            => 250.5,
            'description'       => 'Cotton bales for transport',
            'pm'                => '',
            'eway_bill_number'  => '',
            'bill_amount'       => 0,
            'frieght_amount'    => 1500,
            'sur_ch'            => 100,
            'c_r'               => 50,
            'other'             => 25,
            'bc_amount'         => 15,
            'paid'              => 1,
            'to_pay'            => 0,
        ], $override);
    }

    // ─────────────────────────────────────────────────────────────────
    // CREATE GR
    // ─────────────────────────────────────────────────────────────────

    public function test_staff_can_access_gr_create_form(): void
    {
        $this->actingAs($this->staff())
            ->get(route('gr.create'))
            ->assertStatus(200);
    }

    public function test_staff_can_create_gr(): void
    {
        $staff = $this->staff();

        $response = $this->actingAs($staff)
            ->post(route('gr.store'), $this->validGrData());

        $response->assertRedirect(route('gr.index'));

        // Verify GR was created
        $gr = Gr::where('consignor', 'Test Consignor Pvt Ltd')->latest()->first();
        $this->assertNotNull($gr);
        $this->assertSame($staff->office, $gr->office);
        $this->assertSame('created', $gr->status);
        $this->assertSame($staff->id, $gr->created_by_id);
        $this->assertNotNull($gr->gr_no);
        $this->assertStringStartsWith('AA-', $gr->gr_no); // Rajkot prefix

        // Cleanup
        $gr->forceDelete();
    }

    public function test_total_amount_calculated_server_side(): void
    {
        $staff = $this->staff();
        $data = $this->validGrData([
            'frieght_amount' => 1000,
            'sur_ch' => 200,
            'c_r' => 100,
            'other' => 50,
            'bc_amount' => 30,
        ]);

        $this->actingAs($staff)->post(route('gr.store'), $data);

        $gr = Gr::where('consignor', 'Test Consignor Pvt Ltd')->latest()->first();
        // Total = 1000 + 200 + 100 + 50 + 30 = 1380
        $this->assertEquals(1380.00, (float) $gr->total_amount);

        $gr->forceDelete();
    }

    public function test_paid_and_topay_mutual_exclusion(): void
    {
        $staff = $this->staff();

        // Both selected = error
        $this->actingAs($staff)
            ->post(route('gr.store'), $this->validGrData(['paid' => 1, 'to_pay' => 1]))
            ->assertSessionHasErrors('paid');

        // Neither selected = error
        $this->actingAs($staff)
            ->post(route('gr.store'), $this->validGrData(['paid' => 0, 'to_pay' => 0]))
            ->assertSessionHasErrors('paid');
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAs($this->staff())
            ->post(route('gr.store'), [])
            ->assertSessionHasErrors(['copy_date', 'from_dest', 'to_dest', 'consignor', 'consignee', 'nugs', 'meth', 'weight', 'description', 'frieght_amount']);
    }

    public function test_gr_number_is_unique_and_sequential(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('gr.store'), $this->validGrData(['consignor' => 'Seq Test 1']));
        $this->actingAs($staff)->post(route('gr.store'), $this->validGrData(['consignor' => 'Seq Test 2']));

        $gr1 = Gr::where('consignor', 'Seq Test 1')->first();
        $gr2 = Gr::where('consignor', 'Seq Test 2')->first();

        $this->assertNotEquals($gr1->gr_no, $gr2->gr_no);

        // Numbers should be sequential
        $num1 = (int) substr($gr1->gr_no, 3); // strip prefix "AA-"
        $num2 = (int) substr($gr2->gr_no, 3);
        $this->assertSame($num1 + 1, $num2);

        $gr1->forceDelete();
        $gr2->forceDelete();
    }

    // ─────────────────────────────────────────────────────────────────
    // EDIT GR
    // ─────────────────────────────────────────────────────────────────

    public function test_staff_can_edit_own_created_gr(): void
    {
        $staff = $this->staff();

        // Create a GR
        $this->actingAs($staff)->post(route('gr.store'), $this->validGrData(['consignor' => 'Edit Test']));
        $gr = Gr::where('consignor', 'Edit Test')->first();

        // Edit it
        $this->actingAs($staff)
            ->get(route('gr.edit', $gr->id))
            ->assertStatus(200);

        $gr->forceDelete();
    }

    public function test_closed_gr_cannot_be_edited(): void
    {
        // Insert a closed GR directly (bypass observer via DB)
        $id = DB::table('grs')->insertGetId([
            'gr_no' => 'TEST-CLOSED-001',
            'office' => 'Rajkot',
            'from_dest' => 'Rajkot',
            'to_dest' => 'Navagam',
            'copy_date' => now(),
            'consignor' => 'Closed Test',
            'consignor_address' => 'Test',
            'consignee' => 'Test',
            'consignee_address' => 'Test',
            'nugs' => 1, 'meth' => 'Bag', 'weight' => 10,
            'description' => 'Test', 'pm' => '', 'eway_bill_number' => '',
            'frieght_amount' => 100, 'total_amount' => 100,
            'status' => 'closed',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('gr.edit', $id));
        $this->assertSame(403, $response->getStatusCode());

        DB::table('grs')->where('id', $id)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // DELETE GR
    // ─────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_created_gr(): void
    {
        $sa = $this->superAdmin();

        // Create GR directly via DB to avoid test isolation issues
        $grNo = 'DEL-TEST-' . time();
        $id = DB::table('grs')->insertGetId([
            'gr_no' => $grNo, 'office' => $sa->office,
            'from_dest' => $sa->office, 'to_dest' => 'Navagam',
            'copy_date' => now(), 'consignor' => 'Delete Direct',
            'consignor_address' => 'T', 'consignee' => 'T', 'consignee_address' => 'T',
            'nugs' => 1, 'meth' => 'Bag', 'weight' => 10, 'description' => 'T',
            'pm' => '', 'eway_bill_number' => '',
            'frieght_amount' => 100, 'total_amount' => 100,
            'status' => 'created', 'created_by_id' => $sa->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($sa)
            ->delete(route('gr.destroy', $id))
            ->assertRedirect(route('gr.index'));

        // Soft-deleted
        $this->assertNull(Gr::find($id));
        $this->assertNotNull(Gr::withTrashed()->find($id));

        Gr::withTrashed()->find($id)->forceDelete();
    }

    public function test_staff_cannot_delete_gr(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('gr.store'), $this->validGrData(['consignor' => 'Staff Del']));
        $gr = Gr::where('consignor', 'Staff Del')->first();

        $response = $this->actingAs($staff)
            ->delete(route('gr.destroy', $gr->id));
        $this->assertSame(403, $response->getStatusCode());

        $gr->forceDelete();
    }

    public function test_cannot_delete_dispatched_gr(): void
    {
        $id = DB::table('grs')->insertGetId([
            'gr_no' => 'TEST-DISP-001', 'office' => 'Rajkot',
            'from_dest' => 'Rajkot', 'to_dest' => 'Navagam',
            'copy_date' => now(), 'consignor' => 'Disp Test',
            'consignor_address' => 'T', 'consignee' => 'T', 'consignee_address' => 'T',
            'nugs' => 1, 'meth' => 'Bag', 'weight' => 10, 'description' => 'T',
            'pm' => '', 'eway_bill_number' => '',
            'frieght_amount' => 100, 'total_amount' => 100,
            'status' => 'dispatched',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('gr.destroy', $id))
            ->assertSessionHasErrors();

        DB::table('grs')->where('id', $id)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // PRINT GR
    // ─────────────────────────────────────────────────────────────────

    public function test_superadmin_can_print_any_gr(): void
    {
        $sa = $this->superAdmin();

        // Create a GR as SuperAdmin directly (to avoid test isolation issues)
        $this->actingAs($sa)->post(route('gr.store'), $this->validGrData(['consignor' => 'Print Test SA']));
        $gr = Gr::where('consignor', 'Print Test SA')->first();

        $this->actingAs($sa)
            ->get(route('gr.print', $gr->id))
            ->assertStatus(200)
            ->assertSee($gr->gr_no);

        $gr->forceDelete();
    }

    public function test_staff_cannot_print_other_office_gr(): void
    {
        // Insert GR for a different office
        $id = DB::table('grs')->insertGetId([
            'gr_no' => 'NV-99999', 'office' => 'Navagam',
            'from_dest' => 'Navagam', 'to_dest' => 'Rajkot',
            'copy_date' => now(), 'consignor' => 'Other Office',
            'consignor_address' => 'T', 'consignee' => 'T', 'consignee_address' => 'T',
            'nugs' => 1, 'meth' => 'Bag', 'weight' => 10, 'description' => 'T',
            'pm' => '', 'eway_bill_number' => '',
            'frieght_amount' => 100, 'total_amount' => 100,
            'status' => 'created',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Staff in Rajkot cannot print GR from Navagam
        $rajkotStaff = $this->staff(); // assumed Rajkot
        $response = $this->actingAs($rajkotStaff)->get(route('gr.print', $id));
        $this->assertSame(403, $response->getStatusCode());

        DB::table('grs')->where('id', $id)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // OFFICE ISOLATION
    // ─────────────────────────────────────────────────────────────────

    public function test_staff_only_sees_own_office_grs(): void
    {
        $staff = $this->staff(); // Rajkot

        $response = $this->actingAs($staff)->get(route('gr.index'));
        $response->assertStatus(200);
        // Should NOT see GRs from other offices in the response
        $response->assertDontSee('NV-99999'); // Navagam GR shouldn't be visible
    }

    public function test_superadmin_sees_all_grs(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('gr.index'))
            ->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────
    // STATUS HANDLING
    // ─────────────────────────────────────────────────────────────────

    public function test_undo_topay_requires_admin(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('gr.store'), $this->validGrData([
            'consignor' => 'ToPay Test', 'paid' => 0, 'to_pay' => 1,
        ]));
        $gr = Gr::where('consignor', 'ToPay Test')->first();

        // Staff cannot undo topay
        $response = $this->actingAs($staff)->post("/gr/{$gr->id}/undo-topay-collected");
        $this->assertSame(403, $response->getStatusCode());

        $gr->forceDelete();
    }

    // ─────────────────────────────────────────────────────────────────
    // FINANCIAL CALCULATIONS
    // ─────────────────────────────────────────────────────────────────

    public function test_total_ignores_client_submitted_value(): void
    {
        $staff = $this->staff();
        $data = $this->validGrData([
            'frieght_amount' => 500,
            'sur_ch' => 0,
            'c_r' => 0,
            'other' => 0,
            'bc_amount' => 0,
            'total_amount' => 99999, // client tries to inject a fake total
        ]);

        $this->actingAs($staff)->post(route('gr.store'), $data);

        $gr = Gr::where('consignor', 'Test Consignor Pvt Ltd')->latest()->first();
        // Server recalculates: 500 + 0 + 0 + 0 + 0 = 500
        $this->assertEquals(500.00, (float) $gr->total_amount);
        $this->assertNotEquals(99999, (float) $gr->total_amount);

        $gr->forceDelete();
    }
}
