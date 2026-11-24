<?php

namespace App\Modules\Ordering\Data;

/**
 * Data pembeli yang disimpan di pesanan (guest maupun user yang login).
 */
final readonly class CustomerData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?int $userId = null,
    ) {}
}
