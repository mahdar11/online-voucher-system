<?php

namespace App\Modules\Ordering\Http\Resources;

use App\Modules\Inventory\Data\VoucherCode;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pesanan untuk pemiliknya. Kode voucher hanya disertakan setelah lunas.
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /** @var list<VoucherCode> */
    private array $voucherCodes = [];

    /**
     * @param  list<VoucherCode>  $codes
     */
    public function withVoucherCodes(array $codes): self
    {
        $this->voucherCodes = $codes;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],
            'items' => $this->items->map(fn (OrderItem $item) => [
                'package_name' => $item->package_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ])->values()->all(),
            'subtotal' => $this->subtotal,
            'fee' => $this->fee,
            'total' => $this->total,
            'created_at' => $this->created_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'expires_in_seconds' => $this->secondsUntilExpiry(),
            'can_cancel' => $this->isPending(),
            'can_pay' => $this->isPending() && ! $this->isOverdue(),
            'vouchers' => $this->when(
                $this->status->hasVouchers(),
                fn () => array_map(fn (VoucherCode $code) => $code->toArray(), $this->voucherCodes),
            ),
        ];
    }
}
