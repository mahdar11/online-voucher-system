<?php

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Inventory\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Voucher untuk tabel stok admin (termasuk kode dan password).
 *
 * @mixin Voucher
 */
class VoucherResource extends JsonResource
{
    /**
     * @param  array<int, string>  $packageNames
     */
    public function __construct(Voucher $voucher, private readonly array $packageNames = [])
    {
        parent::__construct($voucher);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'password' => $this->password,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'package' => $this->packageNames[$this->package_id] ?? null,
            'batch_id' => $this->batch_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'sold_at' => $this->sold_at?->toIso8601String(),
            'can_delete' => $this->isDeletable(),
            'can_withdraw' => $this->isWithdrawable(),
        ];
    }
}
