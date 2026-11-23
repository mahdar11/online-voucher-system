<?php

namespace App\Modules\Inventory\Data;

/**
 * Kode voucher yang sudah terjual, untuk ditampilkan ke pembelinya.
 */
final readonly class VoucherCode
{
    public function __construct(
        public string $code,
        public ?string $password,
    ) {}

    /**
     * @return array{code: string, password: ?string}
     */
    public function toArray(): array
    {
        return ['code' => $this->code, 'password' => $this->password];
    }
}
