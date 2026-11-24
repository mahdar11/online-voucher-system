<?php

namespace App\Modules\Ordering\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Rentang tanggal laporan dalam zona waktu bisnis (mis. WIB), inklusif.
 * Dipakai untuk mengubah "1–30 November WIB" menjadi batas waktu UTC di query.
 */
final readonly class SalesPeriod
{
    public function __construct(
        public CarbonImmutable $from,   // awal hari pertama, zona bisnis
        public CarbonImmutable $until,  // akhir hari terakhir, zona bisnis
        public string $timezone,
    ) {}

    public static function days(string $fromDate, string $untilDate, ?string $timezone = null): self
    {
        $timezone ??= (string) config('voucher.business_timezone', 'Asia/Jakarta');

        return new self(
            CarbonImmutable::parse($fromDate, $timezone)->startOfDay(),
            CarbonImmutable::parse($untilDate, $timezone)->endOfDay(),
            $timezone,
        );
    }

    public function fromUtc(): CarbonImmutable
    {
        return $this->from->utc();
    }

    public function untilUtc(): CarbonImmutable
    {
        return $this->until->utc();
    }

    /**
     * @return list<string> tanggal Y-m-d di zona bisnis
     */
    public function dates(): array
    {
        $dates = [];
        foreach (CarbonPeriod::create($this->from->toDateString(), $this->until->toDateString()) as $day) {
            $dates[] = $day->toDateString();
        }

        return $dates;
    }
}
