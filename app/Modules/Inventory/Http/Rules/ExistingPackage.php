<?php

namespace App\Modules\Inventory\Http\Rules;

use App\Modules\Catalog\Contracts\PackageCatalog;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Memastikan paket ada, lewat kontrak Catalog (bukan query langsung ke tabel paket).
 */
class ExistingPackage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || app(PackageCatalog::class)->find((int) $value) === null) {
            $fail('validation.exists')->translate();
        }
    }
}
