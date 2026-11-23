<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VoucherStatus;
use App\Http\Controllers\Controller;
use App\Models\VoucherBatch;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class VoucherBatchController extends Controller
{
    public function index(): Response
    {
        $batches = VoucherBatch::query()
            ->with(['package:id,name', 'creator:id,name'])
            ->withCount([
                'vouchers as available_count' => fn (Builder $q) => $q->where('status', VoucherStatus::Available),
            ])
            ->latest('id')
            ->paginate(20)
            ->through(fn (VoucherBatch $batch) => [
                'id' => $batch->id,
                'package' => $batch->package?->name,
                'package_id' => $batch->package_id,
                'source' => $batch->source->value,
                'source_label' => $batch->source->label(),
                'file_name' => $batch->file_name,
                'quantity' => $batch->quantity,
                'available_count' => $batch->available_count,
                'note' => $batch->note,
                'creator' => $batch->creator?->name,
                'created_at' => $batch->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Batches/Index', ['batches' => $batches]);
    }
}
