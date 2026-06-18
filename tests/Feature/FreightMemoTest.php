<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Freight;
use Illuminate\Support\Facades\DB;

class FreightMemoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::table('frieghts')->where('fm_no', 'like', '0%')->delete();
        DB::table('frieghts')->where('fm_no', 'like', 'TEST%')->delete();
    }

    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    private function getVehicleId(): int
    {
        return DB::table('vehicles')->where('status', 'active')->value('id') ?? 1;
    }

    private function getDriverId(): int
    {
        return DB::table('truckdrivers')->where('status', 1)->value('id') ?? 1;
    }

    public function test_list_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('frieghtmemo.index'))
            ->assertStatus(200)
            ->assertSee('Freight Memo');
    }

    public function test_create_form_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('frieghtmemo.create'))
            ->assertStatus(200)
            ->assertSee('Total Lorry Hire')
            ->assertSee('Advance Paid')
            ->assertSee('Balance Payable');
    }

    public function test_staff_cannot_create(): void
    {
        $response = $this->actingAs($this->staff())->get(route('frieghtmemo.create'));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_can_create_freight_memo(): void
    {
        $this->actingAs($this->superAdmin())->post(route('frieghtmemo.store'), [
            'fm_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'truck_no' => 'GJ-03-AB-1234',
            'truck_freight' => 15000,
            'advance' => 5000,
            'commission' => 1500,
            'hamali' => 500,
            'detention' => 400,
            'tds' => 0,
            'other_charges' => 200,
            'note' => 'Trip completed on time',
        ])->assertRedirect(route('frieghtmemo.index'));

        $fm = Freight::orderByDesc('id')->first();
        $this->assertNotNull($fm);
        $this->assertEquals(15000, (float) $fm->truck_freight);
        $this->assertEquals(1500, (float) $fm->commission);
        // Balance = 15000 - 5000 - 1500 - 500 - 400 - 0 - 200 = 7400
        $this->assertEquals(7400, (float) $fm->balance_due);

        $fm->forceDelete();
    }

    public function test_balance_calculation(): void
    {
        $this->actingAs($this->superAdmin())->post(route('frieghtmemo.store'), [
            'fm_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'truck_no' => 'GJ-03-AB-5678',
            'truck_freight' => 20000,
            'advance' => 0,
            'commission' => 2000,
            'hamali' => 1000,
            'detention' => 0,
            'tds' => 0,
            'other_charges' => 500,
        ]);

        $fm = Freight::orderByDesc('id')->first();
        // Balance = 20000 - 0 - 2000 - 1000 - 0 - 0 - 500 = 16500
        $this->assertEquals(16500, (float) $fm->balance_due);

        $fm->forceDelete();
    }

    public function test_edit_loads(): void
    {
        $id = DB::table('frieghts')->insertGetId([
            'fm_no' => 'TEST-EDIT', 'fm_date' => now(),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'truck_no' => 'GJ-03-AB-1234', 'truck_freight' => 10000,
            'commission' => 1000, 'balance_due' => 9000,
            'office' => 'Rajkot - PN', 'memo_no' => '', 'consignor' => '', 'consignee' => '',
            'note' => '', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('frieghtmemo.edit', $id))
            ->assertStatus(200)
            ->assertSee('TEST-EDIT');

        DB::table('frieghts')->where('id', $id)->delete();
    }

    public function test_delete(): void
    {
        $id = DB::table('frieghts')->insertGetId([
            'fm_no' => 'TEST-DEL', 'fm_date' => now(),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'truck_no' => 'GJ-03-AB-1234', 'truck_freight' => 5000,
            'balance_due' => 5000, 'office' => 'Rajkot - PN',
            'memo_no' => '', 'consignor' => '', 'consignee' => '', 'note' => '',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('frieghtmemo.destroy', $id))
            ->assertRedirect(route('frieghtmemo.index'));

        DB::table('frieghts')->where('id', $id)->delete();
    }

    public function test_print(): void
    {
        $id = DB::table('frieghts')->insertGetId([
            'fm_no' => 'TEST-PRT', 'fm_date' => now(),
            'from_dest' => 'Rajkot - PN', 'to_dest' => 'Navagam',
            'truck_no' => 'GJ-03-AB-1234', 'truck_freight' => 12000,
            'commission' => 1200, 'balance_due' => 10800,
            'entry_1' => 'Loading', 'entry_1_amount' => 0,
            'office' => 'Rajkot - PN', 'memo_no' => '', 'consignor' => '', 'consignee' => '', 'note' => '',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('frieghtmemo.print', $id))
            ->assertStatus(200)
            ->assertSee('FREIGHT MEMO')
            ->assertSee('TEST-PRT')
            ->assertSee('10,800.00');

        DB::table('frieghts')->where('id', $id)->delete();
    }
}
