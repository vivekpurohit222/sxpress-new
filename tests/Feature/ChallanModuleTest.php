<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\challan;
use App\Models\ChallanItem;
use App\Models\Gr;
use Illuminate\Support\Facades\DB;

class ChallanModuleTest extends TestCase
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
            'gr_no' => 'CHL-' . rand(10000, 99999) . $suffix,
            'office' => 'Rajkot - PN', 'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'copy_date' => now(), 'consignor' => 'Challan Test' . $suffix,
            'consignor_address' => 'Test Addr', 'consignor_gst_no' => '',
            'consignee' => 'Recipient' . $suffix,
            'consignee_address' => 'Test Addr', 'consignee_gst_no' => '',
            'nugs' => 5, 'meth' => 'Box',
            'weight' => 75.5, 'description' => 'Test items' . $suffix,
            'pm' => '', 'eway_bill_number' => '',
            'frieght_amount' => 800, 'sur_ch' => 0, 'c_r' => 0, 'other' => 0,
            'bc_amount' => 0, 'total_amount' => 800,
            'paid' => 1, 'to_pay' => 0, 'status' => 'created',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function getVehicleId(): int
    {
        return DB::table('vehicles')->where('status', 'active')->value('id') ?? 1;
    }

    private function getDriverId(): int
    {
        return DB::table('truckdrivers')->where('status', 1)->value('id') ?? 1;
    }

    // ─────────────────────────────────────────────────────────────────
    // LIST
    // ─────────────────────────────────────────────────────────────────

    public function test_challan_list_loads(): void
    {
        $this->actingAs($this->staff())
            ->get(route('challan.index'))
            ->assertStatus(200)
            ->assertSee('Challan List');
    }

    // ─────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────

    public function test_create_form_loads(): void
    {
        $this->actingAs($this->staff())
            ->get(route('challan.create'))
            ->assertStatus(200)
            ->assertSee('Challan');
    }

    public function test_can_create_challan_with_items(): void
    {
        $grId = $this->createTestGr('-CHL1');
        $grNo = DB::table('grs')->where('id', $grId)->value('gr_no');

        $this->actingAs($this->staff())->post(route('challan.store'), [
            'challan_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'vehicle_id' => $this->getVehicleId(),
            'driver_id' => $this->getDriverId(),
            'items' => [
                ['gr_no' => $grNo, 'description' => 'Test goods', 'nugs' => 5, 'weight' => 75.5],
            ],
        ])->assertRedirect(route('challan.index'));

        $challan = challan::orderByDesc('id')->first();
        $this->assertNotNull($challan);
        $this->assertStringStartsWith('26/', $challan->challan_no);
        $this->assertEquals(75.5, (float) $challan->total_weight);

        $item = ChallanItem::where('challan_id', $challan->id)->first();
        $this->assertNotNull($item);
        $this->assertSame($grNo, $item->gr_no);
        $this->assertSame(5, (int) $item->nugs);

        // Cleanup
        ChallanItem::where('challan_id', $challan->id)->delete();
        $challan->forceDelete();
        DB::table('grs')->where('id', $grId)->delete();
    }

    public function test_create_requires_at_least_one_item(): void
    {
        $this->actingAs($this->staff())->post(route('challan.store'), [
            'challan_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'vehicle_id' => $this->getVehicleId(),
            'driver_id' => $this->getDriverId(),
            'items' => [],
        ])->assertSessionHasErrors('items');
    }

    // ─────────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────────

    public function test_edit_form_loads_with_items(): void
    {
        $grId = $this->createTestGr('-EDIT');
        $grNo = DB::table('grs')->where('id', $grId)->value('gr_no');

        // Create challan via controller
        $this->actingAs($this->superAdmin())->post(route('challan.store'), [
            'challan_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'vehicle_id' => $this->getVehicleId(), 'driver_id' => $this->getDriverId(),
            'items' => [['gr_no' => $grNo, 'description' => 'Edit test', 'nugs' => 3, 'weight' => 30]],
        ]);

        $challan = challan::latest()->first();

        $this->actingAs($this->superAdmin())
            ->get(route('challan.edit', $challan->id))
            ->assertStatus(200)
            ->assertSee($challan->challan_no)
            ->assertSee($grNo);

        // Cleanup
        ChallanItem::where('challan_id', $challan->id)->delete();
        $challan->forceDelete();
        DB::table('grs')->where('id', $grId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_challan(): void
    {
        $grId = $this->createTestGr('-DEL');
        $grNo = DB::table('grs')->where('id', $grId)->value('gr_no');

        $this->actingAs($this->superAdmin())->post(route('challan.store'), [
            'challan_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'vehicle_id' => $this->getVehicleId(), 'driver_id' => $this->getDriverId(),
            'items' => [['gr_no' => $grNo, 'description' => 'Delete test', 'nugs' => 2, 'weight' => 20]],
        ]);

        $challan = challan::latest()->first();

        $this->actingAs($this->superAdmin())
            ->delete(route('challan.destroy', $challan->id))
            ->assertRedirect(route('challan.index'));

        // Items should be gone too
        $this->assertSame(0, ChallanItem::where('challan_id', $challan->id)->count());

        DB::table('grs')->where('id', $grId)->delete();
    }

    public function test_staff_cannot_delete_challan(): void
    {
        // Insert a challan directly (bypass controller to avoid auth issues)
        $challanId = DB::table('challans')->insertGetId([
            'challan_no' => 'CH-STAFF-DEL',
            'challan_date' => now(),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'truck_no' => 'GJ-03-AB-1234', 'driver_name' => 'Test',
            'license' => 'DL-123', 'owner_name' => 'Test',
            'office' => 'Rajkot - PN',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $staff = $this->staff();
        $response = $this->actingAs($staff)
            ->delete(route('challan.destroy', $challanId));
        $this->assertSame(403, $response->getStatusCode());

        // Cleanup
        DB::table('challans')->where('id', $challanId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // PRINT
    // ─────────────────────────────────────────────────────────────────

    public function test_print_shows_items_and_totals(): void
    {
        $grId = $this->createTestGr('-PRT');
        $grNo = DB::table('grs')->where('id', $grId)->value('gr_no');

        $this->actingAs($this->superAdmin())->post(route('challan.store'), [
            'challan_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'vehicle_id' => $this->getVehicleId(), 'driver_id' => $this->getDriverId(),
            'items' => [['gr_no' => $grNo, 'description' => 'Print test', 'nugs' => 4, 'weight' => 40]],
        ])->assertRedirect(route('challan.index'));

        $challan = challan::orderByDesc('id')->first();
        $this->assertNotNull($challan);

        $this->actingAs($this->superAdmin())
            ->get(route('challan.print', $challan->id))
            ->assertStatus(200)
            ->assertSee('DELIVERY CHALLAN')
            ->assertSee($challan->challan_no);

        // Cleanup
        ChallanItem::where('challan_id', $challan->id)->delete();
        $challan->forceDelete();
        DB::table('grs')->where('id', $grId)->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // ITEM MANAGEMENT
    // ─────────────────────────────────────────────────────────────────

    public function test_cannot_delete_last_item(): void
    {
        $grId = $this->createTestGr('-LAST');
        $grNo = DB::table('grs')->where('id', $grId)->value('gr_no');

        $this->actingAs($this->superAdmin())->post(route('challan.store'), [
            'challan_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'vehicle_id' => $this->getVehicleId(), 'driver_id' => $this->getDriverId(),
            'items' => [['gr_no' => $grNo, 'description' => 'Last item', 'nugs' => 1, 'weight' => 10]],
        ])->assertRedirect(route('challan.index'));

        $challan = challan::orderByDesc('id')->first();
        $this->assertNotNull($challan);
        $item = ChallanItem::where('challan_id', $challan->id)->first();
        $this->assertNotNull($item);

        // Try to delete the only item
        $response = $this->actingAs($this->superAdmin())
            ->delete(route('challan.item.destroy', $item->id));
        $response->assertStatus(422);

        // Item should still exist
        $this->assertNotNull(ChallanItem::find($item->id));

        // Cleanup
        ChallanItem::where('challan_id', $challan->id)->delete();
        $challan->forceDelete();
        DB::table('grs')->where('id', $grId)->delete();
    }
}
