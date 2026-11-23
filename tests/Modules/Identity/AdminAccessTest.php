<?php

namespace Tests\Modules\Identity;

use Tests\Support\AdminTestCase;
use App\Modules\Identity\Enums\Role;

class AdminAccessTest extends AdminTestCase
{
    /**
     * @return list<string>
     */
    private function adminPages(): array
    {
        return [
            '/admin',
            '/admin/paket',
            '/admin/paket/tambah',
            '/admin/voucher',
            '/admin/voucher/generate',
            '/admin/voucher/import',
            '/admin/batch',
        ];
    }

    public function test_customer_is_forbidden_on_every_admin_page(): void
    {
        $customer = $this->userWithRole(Role::Customer);

        foreach ($this->adminPages() as $url) {
            $this->actingAs($customer)->get($url)->assertForbidden();
        }

        $this->actingAs($customer)->post('/admin/paket', [])->assertForbidden();
        $this->actingAs($customer)->post('/admin/voucher/generate', [])->assertForbidden();
    }

    public function test_admin_and_super_admin_can_open_every_admin_page(): void
    {
        foreach ([Role::Admin, Role::SuperAdmin] as $role) {
            $user = $this->userWithRole($role);

            foreach ($this->adminPages() as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }
    }

    public function test_admin_receives_permissions_in_shared_props(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('auth.user.isStaff', true)
                ->where('auth.user.roleLabel', 'Admin')
                ->where('auth.user.can.manageVouchers', true)
                ->where('auth.user.can.managePackages', true)
                ->where('auth.user.can.manageUsers', false));
    }
}
