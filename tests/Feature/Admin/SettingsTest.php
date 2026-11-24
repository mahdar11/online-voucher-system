<?php

namespace Tests\Feature\Admin;

use App\Modules\Identity\Enums\Role;
use App\Modules\Ordering\Models\Order;
use App\Modules\Shared\Models\ActivityLog;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class SettingsTest extends ModuleTestCase
{
    private function save(array $overrides = [])
    {
        return $this->actingAs($this->userWithRole(Role::SuperAdmin))->put('/admin/pengaturan', [
            'reservation_minutes' => 30,
            'max_quantity' => 2,
            'support_email' => 'bantuan@example.test',
            'support_whatsapp' => '0812-3456-7890',
            'announcement' => 'Promo akhir pekan!',
            ...$overrides,
        ]);
    }

    public function test_only_settings_managers_can_open_settings_and_log(): void
    {
        $this->actingAs($this->admin())->get('/admin/pengaturan')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/log')->assertForbidden();

        $super = $this->userWithRole(Role::SuperAdmin);
        $this->actingAs($super)->get('/admin/pengaturan')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('settings.reservation_minutes', 15)->where('settings.max_quantity', 5));
        $this->actingAs($super)->get('/admin/log')->assertOk();
    }

    public function test_settings_are_validated(): void
    {
        $this->save(['reservation_minutes' => 4, 'max_quantity' => 21, 'support_email' => 'bukan', 'support_whatsapp' => '123'])
            ->assertSessionHasErrors(['reservation_minutes', 'max_quantity', 'support_email', 'support_whatsapp']);
    }

    public function test_saved_settings_apply_to_checkout_footer_and_home(): void
    {
        $this->save()->assertSessionHas('success');
        $this->assertTrue(ActivityLog::query()->where('action', 'settings.updated')->exists());

        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 10);
        $this->app['auth']->forgetGuards();

        $this->get('/beli/harian')->assertInertia(fn (Assert $page) => $page
            ->where('maxQuantity', 2)
            ->where('reservationMinutes', 30)
            ->where('site.supportEmail', 'bantuan@example.test')
            ->where('site.supportWhatsapp', '6281234567890'));

        $this->post('/beli/harian', ['quantity' => 3, 'name' => 'A', 'email' => 'a@example.test'])
            ->assertSessionHasErrors(['quantity' => 'Maksimal 2 voucher per pesanan.']);

        $this->post('/beli/harian', ['quantity' => 2, 'name' => 'A', 'email' => 'a@example.test'])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(now()->addMinutes(30)->timestamp, Order::query()->sole()->expires_at->timestamp, 5);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('announcement', 'Promo akhir pekan!'));
    }

    public function test_clearing_optional_settings_hides_them(): void
    {
        $this->save();
        $this->save(['support_email' => '', 'support_whatsapp' => '', 'announcement' => '']);

        $this->app['auth']->forgetGuards();
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('announcement', null)
            ->where('site.supportEmail', null));
    }

    public function test_activity_log_lists_and_filters_entries(): void
    {
        $this->save();
        $super = $this->userWithRole(Role::SuperAdmin);

        $this->actingAs($super)->get('/admin/log?action=settings')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ActivityLog')
                ->where('logs.data.0.action', 'settings.updated')
                ->where('logs.data.0.actor', fn ($actor) => $actor !== 'Sistem / pembeli'));

        $this->actingAs($super)->get('/admin/log?action=tidak-ada')
            ->assertInertia(fn (Assert $page) => $page->where('logs.total', 0));
    }
}
