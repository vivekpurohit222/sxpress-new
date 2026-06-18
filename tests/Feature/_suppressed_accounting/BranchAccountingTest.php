<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class BranchAccountingTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test all 6 routes return 200 for SuperAdmin
    // ─────────────────────────────────────────────────────────────────────────

    public function test_dashboard_loads_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.dashboard'))
            ->assertStatus(200)
            ->assertSee('Branch Dashboard');
    }

    public function test_revenue_loads_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.revenue'))
            ->assertStatus(200)
            ->assertSee('Branch Revenue Report');
    }

    public function test_expenses_loads_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.expenses'))
            ->assertStatus(200)
            ->assertSee('Branch Expense Report');
    }

    public function test_profitability_loads_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.profitability'))
            ->assertStatus(200)
            ->assertSee('Branch Profitability Report');
    }

    public function test_cash_position_loads_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.cash-position'))
            ->assertStatus(200)
            ->assertSee('Cash Position by Branch');
    }

    public function test_outstanding_loads_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.outstanding'))
            ->assertStatus(200)
            ->assertSee('Branch Outstanding Report');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test date filter query parameters are applied correctly
    // ─────────────────────────────────────────────────────────────────────────

    public function test_dashboard_with_date_filters(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.dashboard', [
                'from_date' => '2024-04-01',
                'to_date' => '2024-06-30',
            ]))
            ->assertStatus(200)
            ->assertSee('2024-04-01')
            ->assertSee('2024-06-30');
    }

    public function test_revenue_with_date_filters(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.revenue', [
                'from_date' => '2024-04-01',
                'to_date' => '2024-06-30',
            ]))
            ->assertStatus(200)
            ->assertSee('Branch Revenue Report');
    }

    public function test_expenses_with_date_filters(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.expenses', [
                'from_date' => '2024-04-01',
                'to_date' => '2024-06-30',
            ]))
            ->assertStatus(200)
            ->assertSee('Branch Expense Report');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test correct Blade views are rendered
    // ─────────────────────────────────────────────────────────────────────────

    public function test_dashboard_renders_correct_view(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.dashboard'));

        $response->assertViewIs('accounting.branch.dashboard');
    }

    public function test_revenue_renders_correct_view(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.revenue'));

        $response->assertViewIs('accounting.branch.revenue');
    }

    public function test_expenses_renders_correct_view(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.expenses'));

        $response->assertViewIs('accounting.branch.expenses');
    }

    public function test_profitability_renders_correct_view(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.profitability'));

        $response->assertViewIs('accounting.branch.profitability');
    }

    public function test_cash_position_renders_correct_view(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.cash-position'));

        $response->assertViewIs('accounting.branch.cash-position');
    }

    public function test_outstanding_renders_correct_view(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.outstanding'));

        $response->assertViewIs('accounting.branch.outstanding');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test invalid date range shows error (to_date before from_date)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_dashboard_invalid_date_range_redirects_with_error(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.dashboard', [
                'from_date' => '2024-06-30',
                'to_date' => '2024-04-01',
            ]))
            ->assertStatus(302)
            ->assertSessionHas('error');
    }

    public function test_revenue_invalid_date_range_redirects_with_error(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.revenue', [
                'from_date' => '2024-12-31',
                'to_date' => '2024-01-01',
            ]))
            ->assertStatus(302)
            ->assertSessionHas('error');
    }

    public function test_expenses_invalid_date_range_redirects_with_error(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.expenses', [
                'from_date' => '2024-12-31',
                'to_date' => '2024-01-01',
            ]))
            ->assertStatus(302)
            ->assertSessionHas('error');
    }

    public function test_profitability_invalid_date_range_redirects_with_error(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.profitability', [
                'from_date' => '2024-12-31',
                'to_date' => '2024-01-01',
            ]))
            ->assertStatus(302)
            ->assertSessionHas('error');
    }

    public function test_cash_position_invalid_date_range_redirects_with_error(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.cash-position', [
                'from_date' => '2024-12-31',
                'to_date' => '2024-01-01',
            ]))
            ->assertStatus(302)
            ->assertSessionHas('error');
    }

    public function test_outstanding_invalid_date_range_redirects_with_error(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.outstanding', [
                'from_date' => '2024-12-31',
                'to_date' => '2024-01-01',
            ]))
            ->assertStatus(302)
            ->assertSessionHas('error');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test pagination on outstanding detail (branch drill-down)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_outstanding_branch_detail_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.outstanding', [
                'branch' => 'Rajkot',
            ]))
            ->assertStatus(200)
            ->assertSee('Outstanding Details')
            ->assertSee('Rajkot');
    }

    public function test_outstanding_branch_detail_pagination(): void
    {
        // Request page 1 of outstanding detail for a branch
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.branch.outstanding', [
                'branch' => 'Rajkot',
                'page' => 1,
            ]));

        $response->assertStatus(200);
        // The view renders $branchDetail->links() which is a paginator
        $response->assertSee('Outstanding Details');
    }
}
