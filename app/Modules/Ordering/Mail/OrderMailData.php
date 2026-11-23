<?php

namespace App\Modules\Ordering\Mail;

use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Inventory\Data\VoucherCode;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Models\OrderItem;
use App\Modules\Ordering\Services\OrderLinks;

/**
 * Menyiapkan data yang dipakai template email pesanan.
 */
class OrderMailData
{
    public function __construct(
        private readonly PackageCatalog $catalog,
        private readonly VoucherReservations $reservations,
        private readonly OrderLinks $links,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Order $order, bool $withCodes = false): array
    {
        $order->loadMissing('items');

        return [
            'order' => $order,
            'orderUrl' => $this->links->signed($order->invoice_number),
            'items' => $order->items->map(fn (OrderItem $item) => $this->item($item))->all(),
            'codes' => $withCodes
                ? array_map(fn (VoucherCode $code) => $code->toArray(), $this->reservations->codesFor($order->invoice_number))
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(OrderItem $item): array
    {
        $package = $this->catalog->find($item->package_id);

        return [
            'name' => $item->package_name,
            'quantity' => $item->quantity,
            'total' => $item->total,
            'duration' => $package ? self::duration($package->durationMinutes) : null,
            'validity' => $package ? ($package->validityDays ? "{$package->validityDays} hari sejak login pertama" : 'tanpa batas') : null,
        ];
    }

    private static function duration(int $minutes): string
    {
        $parts = array_filter([
            intdiv($minutes, 1440) ? intdiv($minutes, 1440).' hari' : null,
            intdiv($minutes % 1440, 60) ? intdiv($minutes % 1440, 60).' jam' : null,
            $minutes % 60 ? ($minutes % 60).' menit' : null,
        ]);

        return implode(' ', $parts) ?: '0 menit';
    }
}
