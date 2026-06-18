<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Accounting\GstSetting;

class GstReportTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function nonSuperAdmin(): User
    {
        return User::where('role', 'branch_manager')->first();
    }

    // ─── SuperAdmin Access (200) ─────────────────────────────────────────────

    public function test_superadmin_can_access_gst_summary(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.summary'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_gst_collection(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.collection'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_gst_liability(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.liability'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_gst_input_tax(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.input-tax'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_gst_output_tax(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.output-tax'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_gst_settings(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.settings'))
            ->assertStatus(200);
    }

    // ─── Non-SuperAdmin Access (403) ─────────────────────────────────────────

    public function test_non_superadmin_cannot_access_gst_summary(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.gst.summary'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_gst_collection(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.gst.collection'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_gst_liability(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.gst.liability'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_gst_input_tax(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.gst.input-tax'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_gst_output_tax(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.gst.output-tax'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_gst_settings(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.gst.settings'))
            ->assertStatus(403);
    }

    // ─── Unauthenticated Access (Redirect to login) ──────────────────────────

    public function test_unauthenticated_user_redirected_from_gst_routes(): void
    {
        $this->get(route('accounting.gst.summary'))
            ->assertRedirect(route('login'));
    }

    // ─── View Rendering ──────────────────────────────────────────────────────

    public function test_summary_view_renders_correct_view_name(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.summary'));

        $response->assertStatus(200);
        $response->assertViewIs('accounting.gst.summary');
    }

    // ─── Settings Page & Update ──────────────────────────────────────────────

    public function test_settings_page_renders(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('accounting.gst.settings');
    }

    public function test_settings_update_saves_correctly(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post(route('accounting.gst.settings.update'), [
                'company_gst_number' => '24ABCDE1234F1Z5',
                'default_gst_rate'   => '5',
                'company_state'      => 'Gujarat',
            ]);

        $response->assertRedirect(route('accounting.gst.settings'));
        $response->assertSessionHas('success', 'GST settings updated successfully.');

        // Verify settings were saved
        $this->assertEquals('24ABCDE1234F1Z5', GstSetting::get('company_gst_number'));
        $this->assertEquals('5', GstSetting::get('default_gst_rate'));
        $this->assertEquals('Gujarat', GstSetting::get('company_state'));
    }

    // ─── Invalid Date Range ──────────────────────────────────────────────────

    public function test_invalid_date_range_redirects_with_error(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.gst.summary', [
                'from_date' => '2025-03-31',
                'to_date'   => '2025-01-01',
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
