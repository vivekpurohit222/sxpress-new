<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gr;
use App\Models\gatepass;
use App\Models\Vehicle;
use App\Models\truckdriver;
use Illuminate\Support\Facades\DB;

class GatepassModuleTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    private function createTestGr(string $suffix = ''): int
    {
        return DB::table('grs')->insertGetId([
            'gr_no' => 'T-' . rand(10000, 99999) . $suffix,
            'office' => 'Rajkot - PN',
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'copy_date' => now(),
            'consignor' => 'Test Consignor' . $suffix,
            'consignor_address' => 'Test',
            'consignee' => 'Test Consignee' . $suffix,
            'consignee_address' => 'Test',
            'nugs' => 3, 'meth' => 'Bag', 'weight' => 50,
            'description' => 'Test goods', 'pm' => '', 'eway_bill_number' => '',
            'frieght_amount' => 500, 'total_amount' => 500,
            'paid' => 1, 'to_pay' => 0,
            'status' => 'created',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function getVehicleId(): int
    {
        $v = Vehicle::where('status', 'active')->first();
        if (!$v) {
            return DB::table('vehicles')->insertGetId([
                'vehicle_number' => 'GJ-03-TEST-' . time(),
                'vehicle_type' => 'Truck',
                'status' => 'active',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $v->id;
    }

    private function getDriverId(): int
    {
        // status column is tinyint (1=active)
        $d = DB::table('truckdrivers')->where('status', 1)->first();
        if (!$d) {
            return DB::table('truckdrivers')->insertGetId([
                'driver_name' => 'Test Driver',
                'truck_no' => 'GJ-03-TS-' . rand(1000, 9999),
                'license' => 'GJ03-' . rand(100000, 999999),
                'driver_address' => 'Test Address',
                'mobile_no1' => '98' . rand(10000000, 99999999),
                'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $d->id;
    }

    // ─────────────────────────────────────────────────────────────────
    // LIST
    // ─────────────────────────────────────────────────────────────────

    public function test_gatepass_list_loads(): void
    {
        $this->actingAs($this->staff())
            ->get(route('gatepass.index'))
            ->assertStatus(200)
            ->assertSee('Gate Pass List');
    }

    // ─────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────

    public function test_create_form_loads(): void
    {
        $this->actingAs($this->staff())
            ->get(route('gatepass.create'))
            ->assertStatus(200)
            ->assertSee('Create Gate Pass');
    }

    public function test_can_create_gatepass_with_multiple_grs(): void
    {
        $this->withoutExceptionHandling();

        $grId1 = $this->createTestGr('-A');
        $grId2 = $this->createTestGr('-B');
        $vehicleId = $this->getVehicleId();
        $driverId = $this->getDriverId();

        $response = $this->actingAs($this->staff())->post(route('gatepass.store'), [
            'gp_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'gr_ids' => [$grId1, $grId2],
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
            'remarks' => 'Test multi-GR',
        ]);

        $response->assertRedirect(route('gatepass.index'));

        // Verify gatepass created
        $gp = gatepass::where('note', 'Test multi-GR')->first();
        $this->assertNotNull($gp);
        $this->assertSame(2, $gp->grs()->count());

        // Verify GRs transitioned to dispatched
        $this->assertSame('dispatched', Gr::find($grId1)->status);
        $this->assertSame('dispatched', Gr::find($grId2)->status);

        // Cleanup
        $gp->grs()->detach();
        $gp->forceDelete();
        DB::table('grs')->whereIn('id', [$grId1, $grId2])->delete();
    }

    public function test_create_rejects_already_dispatched_gr(): void
    {
        $grId = $this->createTestGr('-DISP');
        DB::table('grs')->where('id', $grId)->update(['status' => 'dispatched']);

        $this->actingAs($this->staff())->post(route('gatepass.store'), [
            'gp_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'gr_ids' => [$grId],
            'vehicle_id' => $this->getVehicleId(),
            'driver_id' => $this->getDriverId(),
        ])->assertSessionHasErrors('gr_ids');

        DB::table('grs')->where('id', $grId)->delete();
    }

    public function test_create_requires_at_least_one_gr(): void
    {
        $this->actingAs($this->staff())->post(route('gatepass.store'), [
            'gp_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'gr_ids' => [],
            'vehicle_id' => $this->getVehicleId(),
            'driver_id' => $this->getDriverId(),
        ])->assertSessionHasErrors('gr_ids');
    }

    // ─────────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────────

    public function test_edit_form_loads(): void
    {
        $grId = $this->createTestGr('-EDIT');
        $vehicleId = $this->getVehicleId();
        $driverId = $this->getDriverId();

        $this->actingAs($this->superAdmin())->post(route('gatepass.store'), [
            'gp_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'gr_ids' => [$grId],
            'vehicle_id' => $vehicleId, 'driver_id' => $driverId,
        ]);

        $gp = gatepass::latest()->first();

        $this->actingAs($this->superAdmin())
            ->get(route('gatepass.edit', $gp->id))
            ->assertStatus(200)
            ->assertSee($gp->gp_no);

        // Cleanup
        $gp->grs()->detach();
        $gp->forceDelete();
        DB::table('grs')->where('id', $grId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────────

    public function test_delete_reverts_gr_status(): void
    {
        $grId = $this->createTestGr('-DEL');
        $vehicleId = $this->getVehicleId();
        $driverId = $this->getDriverId();

        $this->actingAs($this->superAdmin())->post(route('gatepass.store'), [
            'gp_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'gr_ids' => [$grId],
            'vehicle_id' => $vehicleId, 'driver_id' => $driverId,
        ]);

        $gp = gatepass::latest()->first();
        $this->assertSame('dispatched', Gr::find($grId)->status);

        $this->actingAs($this->superAdmin())
            ->delete(route('gatepass.destroy', $gp->id))
            ->assertRedirect(route('gatepass.index'));

        // GR should be back to created
        $this->assertSame('created', Gr::find($grId)->status);

        DB::table('grs')->where('id', $grId)->delete();
    }

    public function test_staff_cannot_delete_gatepass(): void
    {
        // Staff user attempts to delete a gatepass (should be 403)
        $gp = gatepass::latest()->first();
        if (!$gp) {
            $this->markTestSkipped('No gatepass exists to test');
        }

        $staff = $this->staff();
        $response = $this->actingAs($staff)->delete(route('gatepass.destroy', $gp->id));
        $this->assertSame(403, $response->getStatusCode());
    }

    // ─────────────────────────────────────────────────────────────────
    // PRINT
    // ─────────────────────────────────────────────────────────────────

    public function test_print_shows_linked_grs(): void
    {
        $gp = gatepass::with('grs')->latest()->first();
        if (!$gp) {
            $this->markTestSkipped('No gatepass exists');
        }

        $this->actingAs($this->superAdmin())
            ->get(route('gatepass.print', $gp->id))
            ->assertStatus(200)
            ->assertSee('GATEPASS')
            ->assertSee((string) $gp->gp_no);
    }

    // ─────────────────────────────────────────────────────────────────
    // OFFICE ISOLATION
    // ─────────────────────────────────────────────────────────────────

    public function test_cannot_use_gr_from_other_office(): void
    {
        // Insert a GR for Navagam office
        $grId = DB::table('grs')->insertGetId([
            'gr_no' => 'NV-OTHER-' . time(), 'office' => 'Navagam',
            'from_dest' => 'Navagam', 'to_dest' => 'Rajkot - PN',
            'copy_date' => now(), 'consignor' => 'X', 'consignor_address' => 'X',
            'consignee' => 'X', 'consignee_address' => 'X',
            'nugs' => 1, 'meth' => 'Bag', 'weight' => 10, 'description' => 'X',
            'pm' => '', 'eway_bill_number' => '',
            'frieght_amount' => 100, 'total_amount' => 100,
            'status' => 'created',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Rajkot staff tries to dispatch a Navagam GR
        $this->actingAs($this->staff())->post(route('gatepass.store'), [
            'gp_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'gr_ids' => [$grId],
            'vehicle_id' => $this->getVehicleId(), 'driver_id' => $this->getDriverId(),
        ])->assertSessionHasErrors('gr_ids');

        DB::table('grs')->where('id', $grId)->delete();
    }
}
