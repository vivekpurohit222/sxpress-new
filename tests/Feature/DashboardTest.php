<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class DashboardTest extends TestCase
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

    private function viewer(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'Viewer'))->first()
            ?? $this->staff();
    }

    // ─────────────────────────────────────────────────────────────────
    // ACCESS
    // ─────────────────────────────────────────────────────────────────

    public function test_dashboard_requires_auth(): void
    {
        $this->get(route('dash'))->assertRedirect('/login');
    }

    public function test_superadmin_dashboard_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('GRs Today')
            ->assertSee('New GR');
    }

    public function test_admin_dashboard_loads(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('GRs Today');
    }

    public function test_staff_dashboard_loads(): void
    {
        $this->actingAs($this->staff())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('GRs Today')
            ->assertSee('New GR');
    }

    public function test_viewer_dashboard_loads(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('GRs Today');
    }

    // ─────────────────────────────────────────────────────────────────
    // KPI WIDGETS
    // ─────────────────────────────────────────────────────────────────

    public function test_dashboard_shows_todays_gr_count(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('GRs Today');
    }

    public function test_dashboard_shows_monthly_gr_count(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('GRs This Month');
    }

    public function test_dashboard_shows_revenue(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Revenue This Month');
    }

    public function test_dashboard_shows_pending_deliveries(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Pending Deliveries');
    }

    public function test_dashboard_shows_pending_pod(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Pending POD');
    }

    public function test_dashboard_shows_active_vehicles(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Active Vehicles');
    }

    public function test_dashboard_shows_pending_topay(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Pending TO-PAY');
    }

    // ─────────────────────────────────────────────────────────────────
    // BRANCH FILTER (SuperAdmin)
    // ─────────────────────────────────────────────────────────────────

    public function test_superadmin_can_filter_by_branch(): void
    {
        // Impersonation handles branch filtering now — dashboard shows office name
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('Office');
    }

    public function test_non_superadmin_has_no_branch_filter(): void
    {
        $response = $this->actingAs($this->staff())
            ->get(route('dash'));

        $response->assertStatus(200);
        $response->assertDontSee('All Branches');
    }

    // ─────────────────────────────────────────────────────────────────
    // ROLE-BASED WIDGETS
    // ─────────────────────────────────────────────────────────────────

    public function test_manager_sees_charts(): void
    {
        // Manager+ should see chart containers
        $manager = User::whereHas('roles', fn($q) => $q->where('name', 'Manager'))->first()
            ?? $this->superAdmin();

        $this->actingAs($manager)
            ->get(route('dash'))
            ->assertSee('barChart')
            ->assertSee('pieChart')
            ->assertSee('lineChart');
    }

    public function test_staff_sees_quick_actions(): void
    {
        $this->actingAs($this->staff())
            ->get(route('dash'))
            ->assertSee('New GR')
            ->assertSee('Gatepass');
    }

    public function test_staff_does_not_see_freight_memo_action(): void
    {
        $staff = $this->staff();
        $response = $this->actingAs($staff)->get(route('dash'));
        // Staff quick actions don't include Freight Memo
        $response->assertDontSee('Freight Memo');
    }
}
