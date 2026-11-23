<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoucherPackage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $packages = VoucherPackage::query()
            ->withStockCounts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VoucherPackage $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'price' => $package->price,
                'is_active' => $package->is_active,
                'available_count' => $package->available_count,
                'reserved_count' => $package->reserved_count,
                'sold_count' => $package->sold_count,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'active_packages' => $packages->where('is_active', true)->count(),
                'available' => $packages->sum('available_count'),
                'reserved' => $packages->sum('reserved_count'),
                'sold' => $packages->sum('sold_count'),
            ],
            'packages' => $packages,
        ]);
    }
}
