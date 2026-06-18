<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class FinancialReportTest extends TestCase
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

    public function test_superadmin_can_access_reports_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.index'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_day_book(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.day-book'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_trial_balance(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.trial-balance'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_profit_and_loss(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.profit-and-loss'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_balance_sheet(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.balance-sheet'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_vehicle_profitability(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.vehicle-profitability'))
            ->assertStatus(200);
    }

    public function test_superadmin_can_access_driver_expenses(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.driver-expenses'))
            ->assertStatus(200);
    }

    // ─── Non-SuperAdmin Access (403) ─────────────────────────────────────────

    public function test_non_superadmin_cannot_access_reports_index(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.reports.index'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_day_book(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.reports.day-book'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_trial_balance(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.reports.trial-balance'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_profit_and_loss(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.reports.profit-and-loss'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_balance_sheet(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.reports.balance-sheet'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_vehicle_profitability(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.reports.vehicle-profitability'))
            ->assertStatus(403);
    }

    public function test_non_superadmin_cannot_access_driver_expenses(): void
    {
        $user = $this->nonSuperAdmin();
        if (!$user) {
            $this->markTestSkipped('No non-SuperAdmin user with Admin/Manager role found.');
        }

        $this->actingAs($user)
            ->get(route('accounting.reports.driver-expenses'))
            ->assertStatus(403);
    }

    // ─── Unauthenticated Access (Redirect to login) ──────────────────────────

    public function test_unauthenticated_user_redirected_from_reports_index(): void
    {
        $this->get(route('accounting.reports.index'))
            ->assertRedirect(route('login'));
    }

    // ─── View Rendering ──────────────────────────────────────────────────────

    public function test_reports_index_lists_all_six_reports(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Day Book');
        $response->assertSee('Trial Balance');
        $response->assertSee('Profit & Loss', false);
        $response->assertSee('Balance Sheet');
        $response->assertSee('Vehicle Profitability');
        $response->assertSee('Driver Expenses');
    }

    public function test_reports_index_renders_correct_view(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.index'))
            ->assertViewIs('accounting.reports.index');
    }

    public function test_day_book_renders_correct_view(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.day-book'))
            ->assertViewIs('accounting.reports.day-book');
    }

    public function test_trial_balance_renders_correct_view(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.trial-balance'))
            ->assertViewIs('accounting.reports.trial-balance');
    }

    public function test_profit_and_loss_renders_correct_view(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.profit-and-loss'))
            ->assertViewIs('accounting.reports.profit-and-loss');
    }

    public function test_balance_sheet_renders_correct_view(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.balance-sheet'))
            ->assertViewIs('accounting.reports.balance-sheet');
    }

    public function test_vehicle_profitability_renders_correct_view(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.vehicle-profitability'))
            ->assertViewIs('accounting.reports.vehicle-profitability');
    }

    public function test_driver_expenses_renders_correct_view(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.driver-expenses'))
            ->assertViewIs('accounting.reports.driver-expenses');
    }

    // ─── Date Validation ─────────────────────────────────────────────────────

    public function test_invalid_date_range_redirects_with_error(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('accounting.reports.trial-balance', [
                'from_date' => '2025-03-31',
                'to_date'   => '2025-01-01',
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
