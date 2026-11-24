<?php

namespace App\Modules\Inventory\Http\Controllers\Admin;

use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Inventory\Actions\ImportVouchers;
use App\Modules\Inventory\Http\Requests\ImportVouchersRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportVoucherController
{
    public function __construct(private readonly PackageCatalog $catalog) {}

    public function create(Request $request): Response
    {
        return Inertia::render('Inventory/Admin/Vouchers/Import', [
            'packages' => $this->catalog->options(),
            'selectedPackage' => $request->integer('package') ? (string) $request->integer('package') : '',
            'maxRows' => ImportVouchers::MAX_ROWS,
            'maxFileKb' => ImportVouchersRequest::MAX_FILE_KB,
        ]);
    }

    public function store(ImportVouchersRequest $request, ImportVouchers $importVouchers): RedirectResponse
    {
        $result = $importVouchers(
            $this->catalog->find((int) $request->validated('package_id')),
            $request->file('file'),
            $request->user()->id,
            $request->validated('note'),
        );

        return to_route('admin.batches.index')
            ->with('success', $result->summary())
            ->with('details', $result->problems);
    }

    public function sample(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo "code,password\n";
            echo "VC-7KQ2MZ9P,\n";
            echo "VC-H4XW8N3R,rahasia1\n";
            echo "VC-T6BD2YJE,\n";
        }, 'contoh-import-voucher.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
