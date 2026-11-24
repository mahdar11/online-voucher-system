<?php

namespace App\Modules\Ordering\Http\Resources;

use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pesanan untuk halaman admin (tanpa kode voucher; kode dikirim terpisah di detail).
 *
 * @mixin Order
 */
class AdminOrderResource extends JsonResource
{
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
                'is_guest' => $this->isGuest(),
            ],
            'items' => $this->items->map(fn (OrderItem $item) => [
                'package_id' => $item->package_id,
                'package_name' => $item->package_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ])->values()->all(),
            'total' => $this->total,
            'created_at' => $this->created_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'can' => [
                'resend' => $this->status->hasVouchers(),
                'refund' => $this->status === OrderStatus::RefundRequired,
                'cancel' => $this->isPending(),
            ],
        ];
    }
}
