<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\BatchSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['package_id', 'created_by', 'source', 'file_name', 'quantity', 'note'])]
class VoucherBatch extends Model
{
    protected function casts(): array
    {
        return [
            'source' => BatchSource::class,
            'quantity' => 'integer',
        ];
    }

    /** @return HasMany<Voucher, $this> */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'batch_id');
    }
}
