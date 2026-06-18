<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

/**
 * Verify that registration is disabled for the public,
 * role middleware works, and only admins can access protected routes.
 */
class AuthSecurityTest extends TestCase
{
    public function test_register_route_does_not_exist_for_guests(): void
    {
        // Auth::routes(['register' => false]) removes the GET /register route
        $response = $this->get('/register');
        $response->assertStatus(404);
    }

    public function test_register_post_does_not_exist_for_guests(): void
    {
        $response = $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hacker@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'office' => 'Rajkot - PN',
        ]);
        $response->assertStatus(404);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dash')->assertRedirect('/login');
    }

    public function test_superadmin_can_access_branch_routes(): void
    {
        $user = User::where('email', 'superadmin@sxpress.com')->first();
        if (!$user) {
            $user = User::where('email', 'admin@sxpress.test')->first();
        }
        $this->actingAs($user)->get('/branch')->assertStatus(200);
    }

    public function test_staff_cannot_access_branch_routes(): void
    {
        $user = User::where('email', 'staff@sxpress.com')->first();
        if (!$user) {
            $user = User::where('email', 'operator1@sxpress.test')->first();
        }
        $response = $this->actingAs($user)->get('/branch');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_staff_can_access_gr_routes(): void
    {
        $user = User::where('email', 'staff@sxpress.com')->first();
        if (!$user) {
            $user = User::where('email', 'operator1@sxpress.test')->first();
        }
        $this->actingAs($user)->get('/gr')->assertStatus(200);
    }
}
