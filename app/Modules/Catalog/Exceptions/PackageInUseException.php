<?php

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

class PackageInUseException extends RuntimeException
{
    public static function because(string $packageName, string $reason): self
    {
        return new self("Paket \"{$packageName}\" {$reason}, jadi tidak bisa dihapus. Nonaktifkan saja.");
    }
}
