<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * End-to-end login flow tests, run against the live seeded `sxpress`
 * database (no RefreshDatabase — we assert against the real seed data).
 */
class LoginTest extends TestCase
{
    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_admin_can_log_in_with_seeded_password(): void
    {
        $response = $this->post('/login', [
            'email'    => 'admin@sxpress.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dash');   // RouteServiceProvider::HOME
        $this->assertAuthenticated();
        $this->assertSame('admin@sxpress.test', auth()->user()->email);
    }

    public function test_branch_manager_can_log_in(): void
    {
        $this->post('/login', [
            'email'    => 'rjkt-mgr@sxpress.test',
            'password' => 'password',
        ])->assertRedirect('/dash');

        $this->assertAuthenticated();
    }

    public function test_operator_can_log_in(): void
    {
        $this->post('/login', [
            'email'    => 'operator1@sxpress.test',
            'password' => 'password',
        ])->assertRedirect('/dash');

        $this->assertAuthenticated();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/login', [
            'email'    => 'admin@sxpress.test',
            'password' => 'not-the-password',
        ]);

        $this->assertGuest();
    }

    public function test_logout_ends_session(): void
    {
        $this->post('/login', [
            'email'    => 'admin@sxpress.test',
            'password' => 'password',
        ]);
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->assertGuest();
    }
}
