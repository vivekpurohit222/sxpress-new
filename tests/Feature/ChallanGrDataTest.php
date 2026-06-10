<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gr;
use Illuminate\Support\Facades\DB;

/**
 * Tests that the Challan module correctly loads GR data
 * via the autocomplete endpoint.
 */
class ChallanGrDataTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'SuperAdmin'))->first();
    }

    public function test_autocomplete_returns_full_gr_fields(): void
    {
        // Use existing GR with status=created
        $existingGr = Gr::where('status', 'created')->first();

        if (!$existingGr) {
            $this->markTestSkipped('No GR with status=created exists in DB');
        }

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/gr/autocomplete?q=' . urlencode($existingGr->gr_no));

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data, 'Autocomplete should return results');

        $gr = $data[0];

        // Verify ALL fields required by Challan form are present in the response
        $this->assertArrayHasKey('gr_no', $gr);
        $this->assertArrayHasKey('nugs', $gr);
        $this->assertArrayHasKey('meth', $gr);
        $this->assertArrayHasKey('description', $gr);
        $this->assertArrayHasKey('weight', $gr);
        $this->assertArrayHasKey('frieght_amount', $gr);
        $this->assertArrayHasKey('sur_ch', $gr);
        $this->assertArrayHasKey('c_r', $gr);
        $this->assertArrayHasKey('other', $gr);
        $this->assertArrayHasKey('total_amount', $gr);
        $this->assertArrayHasKey('consignor', $gr);
        $this->assertArrayHasKey('consignee', $gr);
        $this->assertArrayHasKey('from_dest', $gr);
        $this->assertArrayHasKey('to_dest', $gr);
    }

    public function test_autocomplete_excludes_dispatched_grs(): void
    {
        $dispatchedGr = Gr::where('status', 'dispatched')->first();

        if (!$dispatchedGr) {
            $this->markTestSkipped('No dispatched GR exists in DB');
        }

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/gr/autocomplete?q=' . urlencode($dispatchedGr->gr_no));

        $response->assertStatus(200);
        $data = $response->json();

        $found = collect($data)->firstWhere('gr_no', $dispatchedGr->gr_no);
        $this->assertNull($found, 'Dispatched GR should not appear in challan autocomplete');
    }

    public function test_autocomplete_searches_by_consignor(): void
    {
        $gr = Gr::where('status', 'created')->whereNotNull('consignor')->where('consignor', '!=', '')->first();

        if (!$gr) {
            $this->markTestSkipped('No created GR with consignor in DB');
        }

        // Search by first 5 chars of consignor name
        $searchTerm = substr($gr->consignor, 0, 5);

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/gr/autocomplete?q=' . urlencode($searchTerm));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data, 'Should find GR by consignor name fragment');
    }

    public function test_challan_store_populates_items_from_gr(): void
    {
        $gr = Gr::where('status', 'created')->where('office', 'Rajkot')->first();

        if (!$gr) {
            $this->markTestSkipped('No created GR for Rajkot office');
        }

        $vehicleId = DB::table('vehicles')->where('status', 'active')->value('id');
        $driverId = DB::table('truckdrivers')->where('status', 1)->value('id');

        if (!$vehicleId || !$driverId) {
            $this->markTestSkipped('No active vehicle/driver');
        }

        $response = $this->actingAs($this->superAdmin())
            ->post(route('challan.store'), [
                'challan_date' => now()->format('Y-m-d'),
                'from_dest' => 'Rajkot',
                'to_dest' => 'Navagam',
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId,
                'items' => [
                    [
                        'gr_no' => $gr->gr_no,
                        'description' => $gr->description ?: 'Goods',
                        'nugs' => $gr->nugs ?: 1,
                        'weight' => $gr->weight ?: 1.0,
                        'meth' => $gr->meth ?: 'Box',
                    ]
                ]
            ]);

        $response->assertRedirect(route('challan.index'));

        // Verify challan item was created with GR financial data
        $item = DB::table('challan_items')->where('gr_no', $gr->gr_no)->orderByDesc('id')->first();
        $this->assertNotNull($item, 'Challan item should exist');
        $this->assertEquals($gr->sur_ch ?? 0, (float) $item->sur_ch);
        $this->assertEquals($gr->c_r ?? 0, (float) $item->c_r);
        $this->assertEquals($gr->other ?? 0, (float) $item->other);

        // Cleanup
        DB::table('challan_items')->where('id', $item->id)->delete();
        $challan = DB::table('challans')->orderByDesc('id')->first();
        if ($challan) DB::table('challans')->where('id', $challan->id)->delete();
    }
}
