<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Modules\Catalog\Actions\DeletePackage;
use App\Modules\Catalog\Actions\SavePackage;
use App\Modules\Catalog\Contracts\PackageStockReader;
use App\Modules\Catalog\Exceptions\PackageInUseException;
use App\Modules\Catalog\Http\Requests\PackageRequest;
use App\Modules\Catalog\Http\Resources\PackageResource;
use App\Modules\Catalog\Models\VoucherPackage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PackageController
{
    public function __construct(private readonly SavePackage $savePackage) {}

    public function index(PackageStockReader $stockReader): Response
    {
        $packages = VoucherPackage::query()->ordered()->get();
        $stock = $stockReader->stockFor($packages->modelKeys());

        return Inertia::render('Catalog/Admin/Packages/Index', [
            'packages' => $packages
                ->map(fn (VoucherPackage $package) => (new PackageResource($package))->withStock($stock[$package->id] ?? null)->resolve())
                ->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/Admin/Packages/Create');
    }

    public function store(PackageRequest $request): RedirectResponse
    {
        $package = $this->savePackage->create($request->validated());

        return to_route('admin.packages.index')->with('success', "Paket \"{$package->name}\" berhasil ditambahkan.");
    }

    public function edit(VoucherPackage $package): Response
    {
        return Inertia::render('Catalog/Admin/Packages/Edit', [
            'package' => (new PackageResource($package))->resolve(),
        ]);
    }

    public function update(PackageRequest $request, VoucherPackage $package): RedirectResponse
    {
        $this->savePackage->update($package, $request->validated());

        return to_route('admin.packages.index')->with('success', "Paket \"{$package->name}\" berhasil diperbarui.");
    }

    public function toggle(VoucherPackage $package): RedirectResponse
    {
        $this->savePackage->toggleActive($package);

        $state = $package->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Paket \"{$package->name}\" {$state}.");
    }

    public function destroy(VoucherPackage $package, DeletePackage $deletePackage): RedirectResponse
    {
        try {
            $deletePackage($package);
        } catch (PackageInUseException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.packages.index')->with('success', "Paket \"{$package->name}\" dihapus.");
    }
}
