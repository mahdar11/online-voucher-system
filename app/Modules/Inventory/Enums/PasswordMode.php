<?php

namespace App\Modules\Inventory\Enums;

/**
 * Cara mengisi password saat generate voucher.
 */
enum PasswordMode: string
{
    case None = 'none';
    case SameAsCode = 'same';
    case Random = 'random';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa password (kode saja)',
            self::SameAsCode => 'Password sama dengan kode',
            self::Random => 'Password acak 6 karakter',
        };
    }
}
