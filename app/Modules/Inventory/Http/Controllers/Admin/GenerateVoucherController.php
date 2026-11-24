<?php

namespace App\Modules\Inventory\Http\Controllers\Admin;

use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Inventory\Actions\GenerateVouchers;
use App\Modules\Inventory\Enums\PasswordMode;
use App\Modules\Inventory\Http\Requests\GenerateVouchersRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GenerateVoucherController
{
    public function __construct(private readonly PackageCatalog $catalog) {}

    public function create(Request $request): Response
    {
        return Inertia::render('Inventory/Admin/Vouchers/Generate', [
            'packages' => $this->catalog->options(),
            'selectedPackage' => $request->integer('package') ? (string) $request->integer('package') : '',
            'maxQuantity' => GenerateVouchers::MAX_QUANTITY,
            'passwordModes' => array_map(fn (PasswordMode $m) => ['value' => $m->value, 'label' => $m->label()], PasswordMode::cases()),
        ]);
    }

    public function store(GenerateVouchersRequest $request, GenerateVouchers $generateVouchers): RedirectResponse
    {
        $package = $this->catalog->find((int) $request->validated('package_id'));

        $batch = $generateVouchers(
            package: $package,
            quantity: (int) $request->validated('quantity'),
            length: (int) $request->validated('length'),
            prefix: (string) $request->validated('prefix', ''),
            passwordMode: $request->passwordMode(),
            createdBy: $request->user()->id,
            note: $request->validated('note'),
        );

        return to_route('admin.vouchers.index', ['package' => $package->id])
            ->with('success', number_format($batch->quantity, 0, ',', '.')." voucher untuk paket \"{$package->name}\" berhasil dibuat.");
    }
}
