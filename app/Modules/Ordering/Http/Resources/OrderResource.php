<?php

namespace App\Modules\Ordering\Http\Resources;

use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pesanan untuk pemiliknya. Kode voucher sengaja tidak disertakan:
 * baru ditampilkan setelah pesanan lunas (tahap 4–5).
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
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
            'can_pay' => false, // aktif setelah integrasi Xendit (tahap 4)
        ];
    }
}
