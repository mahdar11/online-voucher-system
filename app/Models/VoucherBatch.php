<?php

namespace App\Models;

use App\Enums\BatchSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /** @return BelongsTo<VoucherPackage, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(VoucherPackage::class, 'package_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Voucher, $this> */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'batch_id');
    }
}
