<?php

namespace Tests\Modules\Catalog;

use Tests\Support\AdminTestCase;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Shared\Models\ActivityLog;
use App\Modules\Inventory\Models\Voucher;
use App\Modules\Catalog\Models\VoucherPackage;
use Inertia\Testing\AssertableInertia as Assert;

class PackageAdminTest extends AdminTestCase
{
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Voucher 1 Hari',
            'description' => 'Internet seharian',
            'price' => 10000,
            'duration_minutes' => 1440,
            'quota_mb' => '',
            'speed_limit' => '5m/5m',
            'is_active' => true,
            'sort_order' => 1,
            ...$overrides,
        ];
    }

    public function test_admin_can_create_package_with_generated_slug(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/paket', $this->validPayload())
            ->assertRedirect('/admin/paket')
            ->assertSessionHas('success');

        $package = VoucherPackage::query()->sole();
        $this->assertSame('voucher-1-hari', $package->slug);
        $this->assertSame('5M/5M', $package->speed_limit);
        $this->assertNull($package->quota_mb);
        $this->assertSame(10000, $package->price);

        $this->assertTrue(ActivityLog::query()->where('action', 'package.created')->exists());
    }

    public function test_duplicate_names_get_unique_slugs(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/paket', $this->validPayload());
        $this->actingAs($admin)->post('/admin/paket', $this->validPayload());

        $this->assertSame(['voucher-1-hari', 'voucher-1-hari-2'], VoucherPackage::query()->orderBy('id')->pluck('slug')->all());
    }

    public function test_package_validation(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/paket', $this->validPayload([
                'name' => '',
                'price' => -1,
                'duration_minutes' => 0,
                'speed_limit' => 'cepat',
            ]))
            ->assertSessionHasErrors(['name', 'price', 'duration_minutes', 'speed_limit']);

        $this->assertDatabaseCount('voucher_packages', 0);
    }

    public function test_admin_can_update_package_without_changing_slug(): void
    {
        $package = $this->package(['slug' => 'lama']);

        $this->actingAs($this->admin())
            ->put("/admin/paket/{$package->id}", $this->validPayload(['name' => 'Nama Baru', 'price' => 7500]))
            ->assertRedirect('/admin/paket');

        $package->refresh();
        $this->assertSame('Nama Baru', $package->name);
        $this->assertSame(7500, $package->price);
        $this->assertSame('lama', $package->slug);
    }

    public function test_admin_can_toggle_package_status(): void
    {
        $package = $this->package(['is_active' => true]);

        $this->actingAs($this->admin())->patch("/admin/paket/{$package->id}/status");

        $this->assertFalse($package->refresh()->is_active);
    }

    public function test_empty_package_can_be_deleted(): void
    {
        $package = $this->package();

        $this->actingAs($this->admin())
            ->delete("/admin/paket/{$package->id}")
            ->assertRedirect('/admin/paket');

        $this->assertModelMissing($package);
    }

    public function test_package_with_vouchers_cannot_be_deleted(): void
    {
        $package = $this->package();
        Voucher::query()->create(['package_id' => $package->id, 'code' => 'ABCD1234']);

        $this->actingAs($this->admin())
            ->delete("/admin/paket/{$package->id}")
            ->assertSessionHas('error');

        $this->assertModelExists($package);
    }

    public function test_index_shows_stock_counts(): void
    {
        $package = $this->package();
        foreach (['A1A1', 'B2B2', 'C3C3'] as $code) {
            Voucher::query()->create(['package_id' => $package->id, 'code' => $code]);
        }
        Voucher::query()->create(['package_id' => $package->id, 'code' => 'D4D4', 'status' => VoucherStatus::Sold]);

        $this->actingAs($this->admin())
            ->get('/admin/paket')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Admin/Packages/Index')
                ->where('packages.0.available_count', 3)
                ->where('packages.0.sold_count', 1));
    }
}
