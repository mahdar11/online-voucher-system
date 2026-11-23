<?php

namespace Tests\Modules\Identity;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Mail\StaffInvitationMail;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class UserManagementTest extends ModuleTestCase
{
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->superAdmin = $this->userWithRole(Role::SuperAdmin);
    }

    private function invite(array $overrides = []): User
    {
        $this->actingAs($this->superAdmin)->post('/admin/pengguna', [
            'name' => 'Staf Baru', 'email' => 'Staf@Example.test', 'phone' => '', 'role' => Role::Admin->value, ...$overrides,
        ])->assertSessionHasNoErrors();

        return User::query()->where('email', 'staf@example.test')->sole();
    }

    private function invitationUrl(): string
    {
        return Mail::queued(StaffInvitationMail::class)->last()->url;
    }

    public function test_only_super_admin_manages_users(): void
    {
        $this->actingAs($this->admin())->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($this->customer())->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($this->superAdmin)->get('/admin/pengguna')->assertOk();
    }

    public function test_inviting_staff_creates_unverified_account_and_queues_invitation(): void
    {
        $user = $this->invite();

        $this->assertTrue($user->hasRole(Role::Admin->value));
        $this->assertFalse($user->hasVerifiedEmail());
        Mail::assertQueued(StaffInvitationMail::class, fn ($mail) => $mail->hasTo('staf@example.test') && $mail->roleLabel === 'Admin');
    }

    public function test_invitation_cannot_create_customers_or_duplicate_emails(): void
    {
        $this->actingAs($this->superAdmin)->post('/admin/pengguna', ['name' => 'X', 'email' => 'x@example.test', 'role' => Role::Customer->value])
            ->assertSessionHasErrors('role');
        $this->actingAs($this->superAdmin)->post('/admin/pengguna', ['name' => 'X', 'email' => $this->superAdmin->email, 'role' => Role::Admin->value])
            ->assertSessionHasErrors('email');
    }

    public function test_accepting_invitation_sets_password_verifies_and_logs_in(): void
    {
        $user = $this->invite();
        $url = $this->invitationUrl();
        $this->app['auth']->forgetGuards();

        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Identity/Auth/AcceptInvitation'));
        $this->post($url, ['password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])->assertRedirect('/admin');

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertAuthenticatedAs($user);

        // Sekali pakai.
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect('/login');
    }

    public function test_tampered_or_expired_invitation_is_rejected(): void
    {
        $this->invite();
        $url = $this->invitationUrl();
        $this->app['auth']->forgetGuards();

        $this->get($url.'x')->assertForbidden();
        $this->travel(73)->hours();
        $this->get($url)->assertForbidden();
    }

    public function test_cannot_deactivate_or_demote_yourself(): void
    {
        $this->actingAs($this->superAdmin)->patch("/admin/pengguna/{$this->superAdmin->id}/status")->assertSessionHas('error');
        $this->actingAs($this->superAdmin)->put("/admin/pengguna/{$this->superAdmin->id}", ['name' => 'X', 'role' => Role::Admin->value])
            ->assertSessionHas('error');

        $this->assertTrue($this->superAdmin->refresh()->is_active);
        $this->assertTrue($this->superAdmin->hasRole(Role::SuperAdmin->value));
    }

    public function test_at_least_one_active_super_admin_must_remain(): void
    {
        $other = $this->userWithRole(Role::SuperAdmin);

        // Ada dua super admin: salah satu boleh diturunkan.
        $this->actingAs($this->superAdmin)->put("/admin/pengguna/{$other->id}", ['name' => $other->name, 'role' => Role::Admin->value])
            ->assertSessionHas('success');

        // Tinggal satu (diri sendiri) aktif: tidak ada super admin lain yang bisa dinonaktifkan,
        // dan menonaktifkan super admin lain yang sudah nonaktif tetap aman.
        $third = $this->userWithRole(Role::SuperAdmin);
        $this->superAdmin->update(['is_active' => false]);
        $this->actingAs($third)->patch("/admin/pengguna/{$third->id}/status")->assertSessionHas('error');
        $this->actingAs($this->userWithRole(Role::SuperAdmin))->patch("/admin/pengguna/{$third->id}/status")->assertSessionHas('success');
    }

    public function test_last_active_super_admin_cannot_be_deactivated_by_anyone(): void
    {
        $lonely = $this->userWithRole(Role::SuperAdmin);
        $this->superAdmin->update(['is_active' => false]);
        $this->superAdmin->syncRoles([Role::Admin->value]);
        $helper = $this->userWithRole(Role::SuperAdmin);
        $this->actingAs($helper)->patch("/admin/pengguna/{$helper->id}/status")->assertSessionHas('error');

        $this->actingAs($helper)->patch("/admin/pengguna/{$lonely->id}/status")->assertSessionHas('success');
        $this->actingAs($helper)->put("/admin/pengguna/{$helper->id}", ['name' => 'H', 'role' => Role::Admin->value])->assertSessionHas('error');
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $staff = $this->admin();
        $this->actingAs($this->superAdmin)->patch("/admin/pengguna/{$staff->id}/status")->assertSessionHas('success');
        $this->assertFalse($staff->refresh()->is_active);

        $this->actingAs($staff)->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_resend_invitation_only_while_pending(): void
    {
        $user = $this->invite();

        $this->actingAs($this->superAdmin)->post("/admin/pengguna/{$user->id}/undangan")->assertSessionHas('success');
        Mail::assertQueued(StaffInvitationMail::class, 2);

        $user->markEmailAsVerified();
        $this->actingAs($this->superAdmin)->post("/admin/pengguna/{$user->id}/undangan")->assertSessionHas('error');
    }
}
