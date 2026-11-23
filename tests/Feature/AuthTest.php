<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;

    private function userWithRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role->value);

        return $user;
    }

    public function test_auth_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
        $this->get('/register')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
        $this->get('/forgot-password')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_user_can_register_as_customer(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi',
            'email' => 'Budi@Example.test',
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect('/akun');
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'budi@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole(Role::Customer->value));
        $this->assertSame('081234567890', $user->phone);
    }

    public function test_register_rejects_invalid_phone(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'phone' => '12345',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_customer_is_redirected_to_account_after_login(): void
    {
        $user = $this->userWithRole(Role::Customer);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/akun');

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_is_redirected_to_admin_after_login(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->userWithRole(Role::Customer);

        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->userWithRole(Role::Customer, ['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.inactive')]);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = $this->userWithRole(Role::Customer);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        $this->get('/akun')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_customer_cannot_access_admin(): void
    {
        $this->actingAs($this->userWithRole(Role::Customer))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_staff_can_access_admin(): void
    {
        foreach ([Role::Admin, Role::SuperAdmin] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get('/admin')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/Dashboard')
                    ->where('auth.user.roles.0', $role->value));
        }
    }

    public function test_shared_auth_props_are_null_for_guests(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('auth.user', null)
            ->where('appName', config('app.name')));
    }
}
