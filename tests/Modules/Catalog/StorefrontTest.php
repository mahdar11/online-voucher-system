<?php

namespace Tests\Modules\Catalog;

use App\Modules\Inventory\Enums\VoucherStatus;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class StorefrontTest extends ModuleTestCase
{
    public function test_home_lists_only_active_packages_in_order(): void
    {
        $this->package(['name' => 'Kedua', 'sort_order' => 2]);
        $this->package(['name' => 'Pertama', 'sort_order' => 1, 'validity_days' => 7]);
        $this->package(['name' => 'Nonaktif', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Home')
                ->has('packages', 2)
                ->where('packages.0.name', 'Pertama')
                ->where('packages.0.validity_days', 7)
                ->where('packages.1.name', 'Kedua'));
    }

    public function test_availability_labels_hide_exact_stock_until_low(): void
    {
        $plenty = $this->package(['sort_order' => 1]);
        $low = $this->package(['sort_order' => 2]);
        $this->package(['sort_order' => 3]); // tanpa stok

        $this->stock($plenty, 11);
        $this->stock($low, 3);
        $this->stock($low, 5, VoucherStatus::Withdrawn); // ditarik tidak dihitung
        $this->stock($low, 2, VoucherStatus::Reserved);  // sedang dipesan tidak dihitung

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('packages.0.availability', ['state' => 'available', 'label' => 'Tersedia'])
            ->where('packages.1.availability', ['state' => 'low', 'label' => 'Sisa 3'])
            ->where('packages.2.availability', ['state' => 'sold_out', 'label' => 'Habis'])
            ->missing('packages.0.available_count'));
    }
}
