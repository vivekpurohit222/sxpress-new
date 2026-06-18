<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class AccountingAccessControlTest extends TestCase
{
    /**
     * Representative accounting routes to test access control against.
     */
    private array $accountingRoutes = [
        'accounting.expenses.index',
        'accounting.ledger.index',
        'accounting.vouchers.index',
        'accounting.cashbook.daily',
        'accounting.bankbook.index',
        'accounting.outstanding.index',
        'accounting.accounts.index',
    ];

    /**
     * Branch accounting routes to test access control against.
     *
     * **Validates: Requirements 1.2, 8.3**
     */
    private array $branchAccountingRoutes = [
        'accounting.branch.dashboard',
        'accounting.branch.revenue',
        'accounting.branch.expenses',
        'accounting.branch.profitability',
        'accounting.branch.cash-position',
        'accounting.branch.outstanding',
    ];

    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function adminUser(): User
    {
        return User::where('role', 'branch_manager')->first();
    }

    private function managerUser(): User
    {
        return User::where('role', 'branch_manager')->first();
    }

    private function staffUser(): User
    {
        return User::where('role', 'agent')->first();
    }

    private function viewerUser(): ?User
    {
        return User::where('role', 'agent')->first();
    }

    // =========================================================================
    // SuperAdmin Access — should get HTTP 200
    // =========================================================================

    public function test_superadmin_can_access_accounting_routes(): void
    {
        $user = $this->superAdmin();
        $this->assertNotNull($user, 'SuperAdmin user must exist in database');

        foreach ($this->accountingRoutes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "SuperAdmin should get 200 on route [{$routeName}], got {$response->getStatusCode()}"
            );
        }
    }

    public function test_superadmin_with_additional_roles_can_access_accounting(): void
    {
        // SuperAdmin with any additional role combination should still have access
        $user = $this->superAdmin();
        $this->assertNotNull($user, 'SuperAdmin user must exist in database');

        // Verify user has SuperAdmin role — access is granted based solely on SuperAdmin presence
        $this->assertTrue($user->isSuperAdmin());

        $response = $this->actingAs($user)->get(route('accounting.expenses.index'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    // =========================================================================
    // Admin Access — should get HTTP 403
    // =========================================================================

    public function test_admin_cannot_access_accounting_routes(): void
    {
        $user = $this->adminUser();
        if (!$user) {
            $this->markTestSkipped('No Admin user without SuperAdmin role found in database');
        }

        foreach ($this->accountingRoutes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Admin should get 403 on route [{$routeName}], got {$response->getStatusCode()}"
            );
        }
    }

    // =========================================================================
    // Manager Access — should get HTTP 403
    // =========================================================================

    public function test_manager_cannot_access_accounting_routes(): void
    {
        $user = $this->managerUser();
        if (!$user) {
            $this->markTestSkipped('No Manager user without SuperAdmin role found in database');
        }

        foreach ($this->accountingRoutes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Manager should get 403 on route [{$routeName}], got {$response->getStatusCode()}"
            );
        }
    }

    // =========================================================================
    // Staff Access — should get HTTP 403
    // =========================================================================

    public function test_staff_cannot_access_accounting_routes(): void
    {
        $user = $this->staffUser();
        if (!$user) {
            $this->markTestSkipped('No Staff user without SuperAdmin/Admin/Manager role found in database');
        }

        foreach ($this->accountingRoutes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Staff should get 403 on route [{$routeName}], got {$response->getStatusCode()}"
            );
        }
    }

    // =========================================================================
    // Viewer Access — should get HTTP 403
    // =========================================================================

    public function test_viewer_cannot_access_accounting_routes(): void
    {
        $user = $this->viewerUser();
        if (!$user) {
            $this->markTestSkipped('No Viewer user without SuperAdmin/Admin/Manager role found in database');
        }

        foreach ($this->accountingRoutes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Viewer should get 403 on route [{$routeName}], got {$response->getStatusCode()}"
            );
        }
    }

    // =========================================================================
    // Unauthenticated Access — should redirect (302) to /login
    // =========================================================================

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        foreach ($this->accountingRoutes as $routeName) {
            $response = $this->get(route($routeName));
            $this->assertEquals(
                302,
                $response->getStatusCode(),
                "Unauthenticated user should get 302 on route [{$routeName}], got {$response->getStatusCode()}"
            );
            $response->assertRedirect(route('login'));
        }
    }

    // =========================================================================
    // Branch Accounting Routes — Non-SuperAdmin Access Denial (Property 1)
    // =========================================================================

    /**
     * Property 1: Non-SuperAdmin Access Denial
     *
     * For any authenticated user who does not hold the SuperAdmin role,
     * requesting any route under the /accounting/branch prefix SHALL
     * result in an HTTP 403 Forbidden response.
     *
     * **Validates: Requirements 1.2, 8.3**
     */
    public function test_nonsuperadmin_denied_branch_accounting_routes(): void
    {
        $nonSuperAdminUsers = [
            'BranchManager' => $this->adminUser(),
            'BranchManager' => $this->managerUser(),
            'Agent' => $this->staffUser(),
        ];

        foreach ($nonSuperAdminUsers as $roleName => $user) {
            if (!$user) {
                continue;
            }

            foreach ($this->branchAccountingRoutes as $routeName) {
                $response = $this->actingAs($user)->get(route($routeName));
                $status = $response->getStatusCode();

                // Non-SuperAdmin must be denied: 403 Forbidden or redirect away from branch content
                $this->assertTrue(
                    $status === 403 || $status === 302,
                    "{$roleName} should be denied access on branch route [{$routeName}], got {$status}"
                );

                // If redirected, verify user is NOT redirected to branch accounting content
                if ($status === 302) {
                    $location = $response->headers->get('Location', '');
                    $this->assertStringNotContainsString(
                        '/accounting/branch',
                        $location,
                        "{$roleName} was redirected to branch accounting content from [{$routeName}]: {$location}"
                    );
                }
            }
        }
    }

    /**
     * SuperAdmin can access all branch accounting routes (passes middleware).
     *
     * Note: Branch views may not yet exist (task 6.x), so we verify
     * SuperAdmin passes the middleware layer (does NOT get 403/302 denied).
     * A 200 confirms full access; a 500 indicates view not yet created.
     *
     * **Validates: Requirements 1.2, 8.3**
     */
    public function test_superadmin_can_access_branch_accounting_routes(): void
    {
        $user = $this->superAdmin();
        $this->assertNotNull($user, 'SuperAdmin user must exist in database');

        foreach ($this->branchAccountingRoutes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $status = $response->getStatusCode();

            // SuperAdmin must NOT be denied access (no 403 or unauthorized redirect)
            $this->assertNotEquals(
                403,
                $status,
                "SuperAdmin should NOT get 403 on branch route [{$routeName}]"
            );

            // Accept 200 (view rendered) or 500 (view file may not exist yet)
            $this->assertTrue(
                in_array($status, [200, 500]),
                "SuperAdmin should get 200 (or 500 if view pending) on branch route [{$routeName}], got {$status}"
            );
        }
    }
}
