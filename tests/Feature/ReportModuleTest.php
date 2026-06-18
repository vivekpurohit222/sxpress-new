<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class ReportModuleTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    private function manager(): User
    {
        return User::where('role', 'branch_manager')->first();
    }

    // ─── Access Control ─────────────────────────────────────────────

    public function test_staff_cannot_access_reports(): void
    {
        $this->actingAs($this->staff())
            ->get(route('reports.index'))
            ->assertStatus(403);
    }

    public function test_manager_can_access_reports(): void
    {
        $user = $this->manager() ?? $this->superAdmin();
        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertStatus(200)
            ->assertSee('Reports');
    }

    public function test_superadmin_can_access_reports(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.index'))
            ->assertStatus(200)
            ->assertSee('GR Reports');
    }

    // ─── Report Pages Load ──────────────────────────────────────────

    public function test_gr_register_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.gr_register'))
            ->assertStatus(200)
            ->assertSee('GR Register');
    }

    public function test_daily_booking_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.daily_booking'))
            ->assertStatus(200)
            ->assertSee('Daily Booking');
    }

    public function test_revenue_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.revenue'))
            ->assertStatus(200)
            ->assertSee('Revenue');
    }

    public function test_branch_performance_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.branch_performance'))
            ->assertStatus(200)
            ->assertSee('Branch Performance');
    }

    public function test_pending_pod_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.pending_pod'))
            ->assertStatus(200)
            ->assertSee('Pending POD');
    }

    public function test_pending_delivery_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.pending_delivery'))
            ->assertStatus(200)
            ->assertSee('Pending Delivery');
    }

    public function test_pending_topay_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.pending_topay'))
            ->assertStatus(200)
            ->assertSee('Pending TO-PAY');
    }

    public function test_freight_report_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.freight'))
            ->assertStatus(200)
            ->assertSee('Freight Memo');
    }

    public function test_vehicle_report_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.vehicle'))
            ->assertStatus(200)
            ->assertSee('Vehicle');
    }

    public function test_driver_report_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.driver'))
            ->assertStatus(200)
            ->assertSee('Driver');
    }

    // ─── CSV Exports ────────────────────────────────────────────────

    public function test_gr_register_csv_export(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('reports.gr_register', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContains('GR No', $response->streamedContent());
    }

    public function test_daily_booking_csv_export(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('reports.daily_booking', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_revenue_csv_export(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('reports.revenue', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_pending_topay_csv_export(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('reports.pending_topay', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_branch_performance_csv_export(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('reports.branch_performance', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    // ─── Filters ────────────────────────────────────────────────────

    public function test_gr_register_date_filter(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.gr_register', ['from_date' => '2026-01-01', 'to_date' => '2026-01-31']))
            ->assertStatus(200);
    }

    public function test_branch_filter_for_superadmin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('reports.gr_register', ['branch' => 'Rajkot']))
            ->assertStatus(200);
    }

    // ─── Helper ─────────────────────────────────────────────────────

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that string contains '{$needle}'"
        );
    }
}
