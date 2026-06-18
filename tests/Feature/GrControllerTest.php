<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Gr;
use App\Models\Branch;

/**
 * GR (Goods Receipt) CRUD tests - tests against real seeded database
 */
class GrControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Login as admin for all tests
        $this->post('/login', [
            'email' => 'admin@sxpress.test',
            'password' => 'password',
        ]);
    }

    public function test_gr_index_loads(): void
    {
        $this->get('/gr')->assertStatus(200);
    }

    public function test_gr_create_form_loads(): void
    {
        $this->get('/gr/create')->assertStatus(200);
    }

    public function test_gr_store_creates_new_gr(): void
    {
        $initialCount = Gr::where('office', 'Rajkot - PN')->count();

        $this->post('/gr/store', [
            'copy_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'consignor' => 'Test Consignor Co',
            'consignor_address' => '123 Test Street, Rajkot',
            'consignor_gst_no' => '',
            'consignee' => 'Test Consignee Pvt Ltd',
            'consignee_address' => '456 Test Road, Surat',
            'consignee_gst_no' => '',
            'nugs' => 10,
            'meth' => 'Bag',
            'weight' => 500.5,
            'description' => 'Test goods for transport',
            'frieght_amount' => 1500,
            'sur_ch' => 100,
            'c_r' => 50,
            'other' => 0,
            'bc_amount' => 25,
            'paid' => 1,
            'to_pay' => 0,
        ])->assertRedirect('/gr');

        $newCount = Gr::where('office', 'Rajkot - PN')->count();
        $this->assertGreaterThan($initialCount, $newCount);
    }

    public function test_gr_validation_requires_fields(): void
    {
        $response = $this->post('/gr/store', []);
        $response->assertSessionHasErrors([
            'copy_date', 'from_dest', 'to_dest', 'consignor',
            'consignor_address', 'consignee', 'consignee_address',
            'nugs', 'meth', 'weight', 'description', 'frieght_amount'
        ]);
    }

    public function test_gr_paid_to_pay_mutual_exclusion(): void
    {
        // Both selected = error
        $this->post('/gr/store', [
            'copy_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'consignor' => 'Test',
            'consignor_address' => 'Test',
            'consignee' => 'Test',
            'consignee_address' => 'Test',
            'nugs' => 1,
            'meth' => 'Bag',
            'weight' => 100,
            'description' => 'Test',
            'frieght_amount' => 100,
            'paid' => 1,
            'to_pay' => 1,
        ])->assertSessionHasErrors('paid');

        // Neither selected = error
        $this->post('/gr/store', [
            'copy_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'consignor' => 'Test',
            'consignor_address' => 'Test',
            'consignee' => 'Test',
            'consignee_address' => 'Test',
            'nugs' => 1,
            'meth' => 'Bag',
            'weight' => 100,
            'description' => 'Test',
            'frieght_amount' => 100,
            'paid' => 0,
            'to_pay' => 0,
        ])->assertSessionHasErrors('paid');
    }

    public function test_gr_autocomplete_consignor_returns_results(): void
    {
        $response = $this->get('/gr/autocomplete/consignor?q=Shree');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertEquals('Shree Balaji Cotton Pvt Ltd', $data[0]['name']);
    }

    public function test_gr_autocomplete_consignee_returns_results(): void
    {
        $response = $this->get('/gr/autocomplete/consignee?q=Sai');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertEquals('Sai Cement Corporation', $data[0]['name']);
    }

    public function test_gr_autocomplete_requires_min_2_chars(): void
    {
        $response = $this->get('/gr/autocomplete/consignor?q=a');
        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    public function test_gr_edit_loads(): void
    {
        // Get first GR for Rajkot office
        $gr = Gr::where('office', 'Rajkot - PN')->first();
        if ($gr) {
            $this->get("/gr/{$gr->id}/edit")->assertStatus(200);
        } else {
            $this->markTestSkipped('No GR found for testing');
        }
    }

    public function test_gr_print_view_loads(): void
    {
        $gr = Gr::where('office', 'Rajkot - PN')->first();
        if ($gr) {
            $this->get("/gr/{$gr->id}/print")->assertStatus(200);
        } else {
            $this->markTestSkipped('No GR found for testing');
        }
    }

    public function test_gr_total_is_calculated_server_side(): void
    {
        $response = $this->post('/gr/store', [
            'copy_date' => now()->format('Y-m-d'),
            'from_dest' => 'Rajkot - PN',
            'to_dest' => 'Navagam',
            'consignor' => 'Test Total Calc',
            'consignor_address' => 'Test Address',
            'consignee' => 'Test Consignee',
            'consignee_address' => 'Test Address 2',
            'nugs' => 5,
            'meth' => 'Box',
            'weight' => 200,
            'description' => 'Test calculation',
            'frieght_amount' => 1000,
            'sur_ch' => 100,
            'c_r' => 50,
            'other' => 25,
            'bc_amount' => 15,
            'paid' => 1,
            'to_pay' => 0,
        ]);

        $response->assertRedirect('/gr');

        // Find the GR we just created
        $gr = Gr::where('consignor', 'Test Total Calc')->first();
        $this->assertNotNull($gr);
        // Total should be: 1000 + 100 + 50 + 25 + 15 = 1190
        $this->assertEquals(1190, $gr->total_amount);
    }
}
