<?php

namespace App\Services;

use App\Enums\BatchSource;
use App\Enums\VoucherStatus;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Models\VoucherPackage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SplFileObject;

class VoucherImporter
{
    public const MAX_ROWS = 5000;

    private const MAX_PROBLEMS = 10;

    private const CODE_PATTERN = '/^[A-Za-z0-9_-]{4,64}$/';

    private const PASSWORD_PATTERN = '/^\S{1,64}$/';

    private const HEADER_NAMES = ['code', 'kode', 'username', 'user'];

    /**
     * @throws ValidationException
     */
    public function import(VoucherPackage $package, UploadedFile $file, ?User $user, ?string $note = null): VoucherImportResult
    {
        $rows = $this->readRows($file);

        $valid = [];
        $duplicates = 0;
        $invalid = 0;
        $problems = [];

        foreach ($rows as $line => $row) {
            $code = $row[0];
            $password = $row[1];

            if (! preg_match(self::CODE_PATTERN, $code)) {
                $invalid++;
                $this->addProblem($problems, "Baris {$line}: format kode \"{$this->preview($code)}\" tidak valid.");

                continue;
            }

            if ($password !== null && ! preg_match(self::PASSWORD_PATTERN, $password)) {
                $invalid++;
                $this->addProblem($problems, "Baris {$line}: password untuk kode {$code} tidak valid.");

                continue;
            }

            if (isset($valid[$code])) {
                $duplicates++;
                $this->addProblem($problems, "Baris {$line}: kode {$code} muncul lebih dari sekali di file.");

                continue;
            }

            $valid[$code] = $password;
        }

        foreach (array_chunk(VoucherCodeGenerator::keys($valid), 1000) as $chunk) {
            $taken = Voucher::query()
                ->where('package_id', $package->id)
                ->whereIn('code', $chunk)
                ->pluck('code');

            foreach ($taken as $code) {
                unset($valid[$code]);
                $duplicates++;
                $this->addProblem($problems, "Kode {$code} sudah ada di paket ini.");
            }
        }

        if ($valid === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada kode voucher baru yang bisa diimport dari file ini.',
            ]);
        }

        $batch = DB::transaction(function () use ($package, $file, $user, $note, $valid) {
            $batch = VoucherBatch::query()->create([
                'package_id' => $package->id,
                'created_by' => $user?->id,
                'source' => BatchSource::Import,
                'file_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'quantity' => count($valid),
                'note' => $note,
            ]);

            $now = now();
            $records = [];
            foreach ($valid as $code => $password) {
                $records[] = [
                    'package_id' => $package->id,
                    'batch_id' => $batch->id,
                    'code' => (string) $code,
                    'password' => $password,
                    'status' => VoucherStatus::Available->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($records, 500) as $chunk) {
                Voucher::query()->insert($chunk);
            }

            return $batch;
        });

        return new VoucherImportResult($batch, count($valid), $duplicates, $invalid, $problems);
    }

    /**
     * Membaca CSV menjadi [nomor baris => [kode, password|null]].
     * Mendukung pemisah koma atau titik koma (Excel versi Indonesia) dan header opsional.
     *
     * @return array<int, array{0: string, 1: ?string}>
     *
     * @throws ValidationException
     */
    private function readRows(UploadedFile $file): array
    {
        $csv = new SplFileObject($file->getRealPath());
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);
        $csv->setCsvControl($this->detectDelimiter($file), '"', '\\');

        $rows = [];
        $first = true;

        foreach ($csv as $index => $columns) {
            if (! is_array($columns) || $columns === [null]) {
                continue;
            }

            $code = trim((string) ($columns[0] ?? ''));
            $password = trim((string) ($columns[1] ?? ''));

            if ($first) {
                $code = preg_replace('/^\xEF\xBB\xBF/', '', $code); // hapus BOM dari Excel
                $first = false;

                if (in_array(strtolower($code), self::HEADER_NAMES, true)) {
                    continue;
                }
            }

            if ($code === '' && $password === '') {
                continue;
            }

            $rows[$index + 1] = [$code, $password === '' ? null : $password];

            if (count($rows) > self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'file' => 'File berisi lebih dari '.number_format(self::MAX_ROWS, 0, ',', '.').' baris. Pecah menjadi beberapa file.',
                ]);
            }
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'File CSV kosong.']);
        }

        return $rows;
    }

    private function detectDelimiter(UploadedFile $file): string
    {
        $handle = fopen($file->getRealPath(), 'r');
        $firstLine = (string) fgets($handle);
        fclose($handle);

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    /**
     * @param  list<string>  $problems
     */
    private function addProblem(array &$problems, string $message): void
    {
        if (count($problems) < self::MAX_PROBLEMS) {
            $problems[] = $message;
        }
    }

    private function preview(string $value): string
    {
        return mb_strimwidth($value, 0, 30, '…');
    }
}
