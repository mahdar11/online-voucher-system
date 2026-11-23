<?php

namespace App\Modules\Inventory\Http\Controllers\Admin;

use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Inventory\Actions\DeleteVoucher;
use App\Modules\Inventory\Actions\WithdrawVoucher;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Exceptions\VoucherNotDeletableException;
use App\Modules\Inventory\Exceptions\VoucherNotWithdrawableException;
use App\Modules\Inventory\Http\Requests\VoucherIndexRequest;
use App\Modules\Inventory\Http\Resources\VoucherResource;
use App\Modules\Inventory\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController
{
    private const PER_PAGE = 50;

    public function __construct(private readonly PackageCatalog $catalog) {}

    public function index(VoucherIndexRequest $request): Response
    {
        $filters = $request->filters();

        $vouchers = Voucher::query()
            ->when($filters['package'], fn ($q, $id) => $q->where('package_id', (int) $id))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'], fn ($q, $search) => $q->where('code', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $packageNames = $this->catalog->namesFor($vouchers->pluck('package_id')->all());

        return Inertia::render('Inventory/Admin/Vouchers/Index', [
            'vouchers' => $vouchers->through(fn (Voucher $voucher) => (new VoucherResource($voucher, $packageNames))->resolve()),
            'filters' => $filters,
            'packages' => $this->catalog->options(),
            'statuses' => array_map(fn (VoucherStatus $s) => ['value' => $s->value, 'label' => $s->label()], VoucherStatus::cases()),
        ]);
    }

    public function destroy(Voucher $voucher, DeleteVoucher $deleteVoucher): RedirectResponse
    {
        try {
            $deleteVoucher($voucher);
        } catch (VoucherNotDeletableException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Voucher {$voucher->code} dihapus.");
    }

    public function withdraw(Voucher $voucher, WithdrawVoucher $withdrawVoucher): RedirectResponse
    {
        try {
            $withdrawVoucher($voucher);
        } catch (VoucherNotWithdrawableException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Voucher {$voucher->code} ditarik dan tidak akan dijual online.");
    }
}
