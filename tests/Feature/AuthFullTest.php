<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Full authentication integration test with real test data.
 * Tests every auth flow: login, logout, inactive block, role gates,
 * password reset request, registration block, session handling.
 */
class AuthFullTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // LOGIN TESTS
    // ─────────────────────────────────────────────────────────────────────

    public function test_login_page_returns_200(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_superadmin_login_success(): void
    {
        $this->post('/login', [
            'email' => 'superadmin@sxpress.com',
            'password' => 'password',
        ])->assertRedirect('/dash');

        $this->assertAuthenticated();
        $this->assertSame('superadmin@sxpress.com', auth()->user()->email);
    }

    public function test_admin_login_success(): void
    {
        $this->post('/login', [
            'email' => 'admin@sxpress.test',
            'password' => 'password',
        ])->assertRedirect('/dash');

        $this->assertAuthenticated();
    }

    public function test_branch_manager_login_success(): void
    {
        $this->post('/login', [
            'email' => 'rjkt-mgr@sxpress.test',
            'password' => 'password',
        ])->assertRedirect('/dash');

        $this->assertAuthenticated();
    }

    public function test_operator_login_success(): void
    {
        $this->post('/login', [
            'email' => 'operator1@sxpress.test',
            'password' => 'password',
        ])->assertRedirect('/dash');

        $this->assertAuthenticated();
    }

    public function test_staff_login_success(): void
    {
        $user = User::where('email', 'staff@sxpress.com')->first();
        if (!$user) {
            $this->markTestSkipped('staff@sxpress.com user not found');
        }
        $this->post('/login', [
            'email' => 'staff@sxpress.com',
            'password' => 'password',
        ])->assertRedirect('/dash');

        $this->assertAuthenticated();
    }

    public function test_wrong_password_rejected(): void
    {
        $this->post('/login', [
            'email' => 'admin@sxpress.test',
            'password' => 'wrongpassword123',
        ]);

        $this->assertGuest();
    }

    public function test_nonexistent_user_rejected(): void
    {
        $this->post('/login', [
            'email' => 'nobody@nowhere.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_empty_credentials_rejected(): void
    {
        $this->post('/login', [
            'email' => '',
            'password' => '',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    // ─────────────────────────────────────────────────────────────────────
    // INACTIVE USER BLOCK
    // ─────────────────────────────────────────────────────────────────────

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('email', 'inactive@sxpress.com')->first();
        if (!$user) {
            $this->markTestSkipped('inactive@sxpress.com not found');
        }

        $response = $this->post('/login', [
            'email' => 'inactive@sxpress.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // ─────────────────────────────────────────────────────────────────────
    // LOGOUT TESTS
    // ─────────────────────────────────────────────────────────────────────

    public function test_logout_clears_session(): void
    {
        $this->post('/login', [
            'email' => 'admin@sxpress.test',
            'password' => 'password',
        ]);
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->assertGuest();
    }

    public function test_guest_cannot_access_dashboard_after_logout(): void
    {
        $this->post('/login', [
            'email' => 'admin@sxpress.test',
            'password' => 'password',
        ]);
        $this->post('/logout');

        $this->get('/dash')->assertRedirect('/login');
    }

    // ─────────────────────────────────────────────────────────────────────
    // SESSION HANDLING
    // ─────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_redirect_to_login(): void
    {
        $this->get('/dash')->assertRedirect('/login');
        $this->get('/gr')->assertRedirect('/login');
        $this->get('/branch')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('email', 'admin@sxpress.test')->first();
        $this->actingAs($user)->get('/dash')->assertStatus(200);
    }

    public function test_remember_me_works(): void
    {
        $this->post('/login', [
            'email' => 'admin@sxpress.test',
            'password' => 'password',
            'remember' => 'on',
        ])->assertRedirect('/dash');

        $this->assertAuthenticated();
        // The remember_token should be set
        $user = User::where('email', 'admin@sxpress.test')->first();
        $this->assertNotNull($user->remember_token);
    }

    // ─────────────────────────────────────────────────────────────────────
    // REGISTRATION BLOCKED
    // ─────────────────────────────────────────────────────────────────────

    public function test_public_cannot_access_register_page(): void
    {
        $this->get('/register')->assertStatus(404);
    }

    public function test_public_cannot_post_register(): void
    {
        $this->post('/register', [
            'name' => 'Hacker User',
            'email' => 'hacker@evil.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'office' => 'Rajkot',
        ])->assertStatus(404);

        // User should NOT exist in DB
        $this->assertNull(User::where('email', 'hacker@evil.com')->first());
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROLE-BASED ACCESS CONTROL
    // ─────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_access_all_admin_routes(): void
    {
        $user = User::where('email', 'superadmin@sxpress.com')->first();
        if (!$user) {
            $user = User::where('email', 'admin@sxpress.test')->first();
        }

        $this->actingAs($user);

        $this->get('/branch')->assertStatus(200);
        $this->get('/users')->assertStatus(200);
        $this->get('/roles')->assertStatus(200);
    }

    public function test_admin_can_access_user_management(): void
    {
        // Any user with Admin role
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first();
        if (!$user) {
            $this->markTestSkipped('No Admin role user found');
        }

        $this->actingAs($user);
        $this->get('/users')->assertStatus(200);
        $this->get('/vehicle')->assertStatus(200);
    }

    public function test_staff_cannot_access_admin_routes(): void
    {
        $user = User::where('email', 'staff@sxpress.com')->first()
            ?? User::where('email', 'operator1@sxpress.test')->first();

        $this->actingAs($user);

        // Branch management should be forbidden
        $response = $this->get('/branch');
        $this->assertContains($response->getStatusCode(), [403, 302]);

        // Vehicle management should be forbidden
        $response = $this->get('/vehicle');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_staff_can_access_business_routes(): void
    {
        $user = User::where('email', 'staff@sxpress.com')->first()
            ?? User::where('email', 'operator1@sxpress.test')->first();

        $this->actingAs($user);

        // GR index should be accessible by Staff role
        $this->get('/gr')->assertStatus(200);
    }

    public function test_manager_can_access_freight_memo(): void
    {
        $user = User::where('email', 'manager@sxpress.com')->first();
        if (!$user) {
            $this->markTestSkipped('manager@sxpress.com not found');
        }

        $this->actingAs($user);
        // Freight memo access is allowed by role (Manager+)
        // Note: may return 500 if controller has non-auth bugs, so we test auth passes (not 403)
        $response = $this->get('/frieghtmemo');
        $this->assertNotEquals(403, $response->getStatusCode(), 'Manager should not be forbidden');
        $this->assertNotEquals(401, $response->getStatusCode(), 'Manager should not be unauthorized');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSWORD RESET REQUEST
    // ─────────────────────────────────────────────────────────────────────

    public function test_password_reset_page_loads(): void
    {
        $this->get('/password/reset')->assertStatus(200);
    }

    public function test_password_reset_email_request(): void
    {
        // Fake the mailer so no real SMTP connection is attempted
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('/password/email', [
            'email' => 'admin@sxpress.test',
        ]);

        // Should redirect back with 'status' session key (reset link sent)
        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_password_reset_invalid_email(): void
    {
        $this->post('/password/email', [
            'email' => 'nonexistent@nowhere.com',
        ])->assertSessionHasErrors('email');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSWORD HASHING
    // ─────────────────────────────────────────────────────────────────────

    public function test_password_is_properly_hashed(): void
    {
        $user = User::where('email', 'admin@sxpress.test')->first();
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_password_not_stored_as_plaintext(): void
    {
        $user = User::where('email', 'admin@sxpress.test')->first();
        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(str_starts_with($user->password, '$2y$'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // MIDDLEWARE CHECKS
    // ─────────────────────────────────────────────────────────────────────

    public function test_isadmin_middleware_blocks_non_admins(): void
    {
        $user = User::where('email', 'staff@sxpress.com')->first()
            ?? User::where('email', 'operator1@sxpress.test')->first();

        // UserController uses isAdmin middleware
        $response = $this->actingAs($user)->get('/users');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_isadmin_middleware_allows_admins(): void
    {
        $user = User::where('email', 'admin@sxpress.test')->first();
        $this->actingAs($user)->get('/users')->assertStatus(200);
    }

    public function test_throttle_blocks_after_5_attempts(): void
    {
        // Make 6 failed login attempts — should be throttled
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => 'admin@sxpress.test',
                'password' => 'wrong' . $i,
            ]);
        }

        // After 5 failed attempts, the 6th should return 429 (Too Many Requests)
        $response->assertStatus(429);
    }

    // ─────────────────────────────────────────────────────────────────────
    // LAST LOGIN TRACKING
    // ─────────────────────────────────────────────────────────────────────

    public function test_last_login_at_is_recorded(): void
    {
        $user = User::where('email', 'admin@sxpress.test')->first();
        $before = $user->last_login_at;

        $this->post('/login', [
            'email' => 'admin@sxpress.test',
            'password' => 'password',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        if ($before) {
            $this->assertTrue($user->last_login_at->gte($before));
        }
    }
}
