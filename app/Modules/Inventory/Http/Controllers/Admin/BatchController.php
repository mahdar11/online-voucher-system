<?php

namespace App\Modules\Inventory\Http\Controllers\Admin;

use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Inventory\Enums\VoucherStatus;
use App\Modules\Inventory\Http\Resources\BatchResource;
use App\Modules\Inventory\Models\VoucherBatch;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class BatchController
{
    private const PER_PAGE = 20;

    public function index(PackageCatalog $catalog, UserDirectory $users): Response
    {
        $batches = VoucherBatch::query()
            ->withCount([
                'vouchers as available_count' => fn (Builder $q) => $q->where('status', VoucherStatus::Available),
            ])
            ->latest('id')
            ->paginate(self::PER_PAGE);

        $packageNames = $catalog->namesFor($batches->pluck('package_id')->all());
        $creatorNames = $users->namesFor($batches->pluck('created_by')->filter()->all());

        return Inertia::render('Inventory/Admin/Batches/Index', [
            'batches' => $batches->through(
                fn (VoucherBatch $batch) => (new BatchResource($batch, $packageNames, $creatorNames))->resolve()
            ),
        ]);
    }
}
