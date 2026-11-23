<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVoucherPackageRequest;
use App\Http\Requests\Admin\UpdateVoucherPackageRequest;
use App\Models\VoucherPackage;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VoucherPackageController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(): Response
    {
        $packages = VoucherPackage::query()
            ->withStockCounts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VoucherPackage $package) => [
                ...$this->present($package),
                'available_count' => $package->available_count,
                'reserved_count' => $package->reserved_count,
                'sold_count' => $package->sold_count,
            ]);

        return Inertia::render('Admin/Packages/Index', ['packages' => $packages]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Packages/Create');
    }

    public function store(StoreVoucherPackageRequest $request): RedirectResponse
    {
        $package = VoucherPackage::query()->create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug($request->validated('name')),
        ]);

        $this->logger->log('package.created', $package, ['name' => $package->name]);

        return to_route('admin.packages.index')->with('success', "Paket \"{$package->name}\" berhasil ditambahkan.");
    }

    public function edit(VoucherPackage $package): Response
    {
        return Inertia::render('Admin/Packages/Edit', ['package' => $this->present($package)]);
    }

    public function update(UpdateVoucherPackageRequest $request, VoucherPackage $package): RedirectResponse
    {
        $package->fill($request->validated());
        $changes = array_keys($package->getDirty());
        $package->save();

        $this->logger->log('package.updated', $package, ['changes' => $changes]);

        return to_route('admin.packages.index')->with('success', "Paket \"{$package->name}\" berhasil diperbarui.");
    }

    public function toggle(VoucherPackage $package): RedirectResponse
    {
        $package->update(['is_active' => ! $package->is_active]);

        $this->logger->log($package->is_active ? 'package.activated' : 'package.deactivated', $package);

        $state = $package->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Paket \"{$package->name}\" {$state}.");
    }

    public function destroy(VoucherPackage $package): RedirectResponse
    {
        if (! $package->isDeletable()) {
            return back()->with('error', "Paket \"{$package->name}\" sudah punya voucher atau pesanan, jadi tidak bisa dihapus. Nonaktifkan saja.");
        }

        $this->logger->log('package.deleted', null, ['id' => $package->id, 'name' => $package->name]);
        $package->delete();

        return to_route('admin.packages.index')->with('success', "Paket \"{$package->name}\" dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function present(VoucherPackage $package): array
    {
        return $package->only([
            'id', 'name', 'slug', 'description', 'price', 'duration_minutes',
            'quota_mb', 'speed_limit', 'is_active', 'sort_order',
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'paket';
        $slug = $base;

        for ($i = 2; VoucherPackage::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
