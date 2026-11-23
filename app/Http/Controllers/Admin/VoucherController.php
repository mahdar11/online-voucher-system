<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BatchSource;
use App\Enums\VoucherStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateVouchersRequest;
use App\Http\Requests\Admin\ImportVouchersRequest;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Models\VoucherPackage;
use App\Services\ActivityLogger;
use App\Services\VoucherCodeGenerator;
use App\Services\VoucherImporter;
use App\Services\VoucherImportResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'package' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(VoucherStatus::class)],
            'search' => ['nullable', 'string', 'max:64'],
        ]);

        $vouchers = Voucher::query()
            ->with('package:id,name')
            ->when($filters['package'] ?? null, fn ($q, $id) => $q->where('package_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('code', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
            ->latest('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Voucher $voucher) => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'password' => $voucher->password,
                'status' => $voucher->status->value,
                'status_label' => $voucher->status->label(),
                'package' => $voucher->package?->name,
                'batch_id' => $voucher->batch_id,
                'created_at' => $voucher->created_at?->toIso8601String(),
                'sold_at' => $voucher->sold_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Vouchers/Index', [
            'vouchers' => $vouchers,
            'filters' => [
                'package' => isset($filters['package']) ? (string) $filters['package'] : '',
                'status' => $filters['status'] ?? '',
                'search' => $filters['search'] ?? '',
            ],
            'packages' => $this->packageOptions(),
            'statuses' => collect(VoucherStatus::cases())->map(fn (VoucherStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        if ($voucher->status !== VoucherStatus::Available) {
            return back()->with('error', "Voucher {$voucher->code} berstatus {$voucher->status->label()}, jadi tidak bisa dihapus.");
        }

        $this->logger->log('voucher.deleted', null, [
            'id' => $voucher->id,
            'code' => $voucher->code,
            'package_id' => $voucher->package_id,
        ]);
        $voucher->delete();

        return back()->with('success', "Voucher {$voucher->code} dihapus.");
    }

    public function generateForm(Request $request): Response
    {
        return Inertia::render('Admin/Vouchers/Generate', [
            'packages' => $this->packageOptions(),
            'selectedPackage' => (string) $request->integer('package') ?: '',
            'maxQuantity' => GenerateVouchersRequest::MAX_QUANTITY,
        ]);
    }

    public function generate(GenerateVouchersRequest $request, VoucherCodeGenerator $generator): RedirectResponse
    {
        $data = $request->validated();
        $package = VoucherPackage::query()->findOrFail($data['package_id']);

        $codes = $generator->uniqueCodes($package, $data['quantity'], $data['length'], $data['prefix'] ?? '');

        $batch = DB::transaction(function () use ($request, $data, $package, $codes, $generator) {
            $batch = VoucherBatch::query()->create([
                'package_id' => $package->id,
                'created_by' => $request->user()->id,
                'source' => BatchSource::Generate,
                'quantity' => count($codes),
                'note' => $data['note'] ?? null,
            ]);

            $now = now();
            $records = array_map(fn (string $code) => [
                'package_id' => $package->id,
                'batch_id' => $batch->id,
                'code' => $code,
                'password' => match ($data['password_mode']) {
                    'same' => $code,
                    'random' => $generator->password(),
                    default => null,
                },
                'status' => VoucherStatus::Available->value,
                'created_at' => $now,
                'updated_at' => $now,
            ], $codes);

            foreach (array_chunk($records, 500) as $chunk) {
                Voucher::query()->insert($chunk);
            }

            return $batch;
        });

        $this->logger->log('vouchers.generated', $batch, [
            'package_id' => $package->id,
            'quantity' => $batch->quantity,
        ]);

        return to_route('admin.vouchers.index', ['package' => $package->id])
            ->with('success', number_format($batch->quantity, 0, ',', '.')." voucher untuk paket \"{$package->name}\" berhasil dibuat.");
    }

    public function importForm(Request $request): Response
    {
        return Inertia::render('Admin/Vouchers/Import', [
            'packages' => $this->packageOptions(),
            'selectedPackage' => (string) $request->integer('package') ?: '',
            'maxRows' => VoucherImporter::MAX_ROWS,
            'maxFileKb' => ImportVouchersRequest::MAX_FILE_KB,
        ]);
    }

    public function import(ImportVouchersRequest $request, VoucherImporter $importer): RedirectResponse
    {
        $package = VoucherPackage::query()->findOrFail($request->validated('package_id'));

        /** @var VoucherImportResult $result */
        $result = $importer->import($package, $request->file('file'), $request->user(), $request->validated('note'));

        $this->logger->log('vouchers.imported', $result->batch, [
            'package_id' => $package->id,
            'file' => $result->batch->file_name,
            'inserted' => $result->inserted,
            'duplicates' => $result->duplicates,
            'invalid' => $result->invalid,
        ]);

        return to_route('admin.batches.index')
            ->with('success', $result->summary())
            ->with('details', $result->problems);
    }

    public function importSample(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo "code,password\n";
            echo "VC-7KQ2MZ9P,\n";
            echo "VC-H4XW8N3R,rahasia1\n";
            echo "VC-T6BD2YJE,\n";
        }, 'contoh-import-voucher.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{value: string, label: string}>
     */
    private function packageOptions()
    {
        return VoucherPackage::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (VoucherPackage $p) => [
                'value' => (string) $p->id,
                'label' => $p->name.($p->is_active ? '' : ' (nonaktif)'),
            ]);
    }
}
