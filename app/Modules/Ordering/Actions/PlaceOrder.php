<?php

namespace App\Modules\Ordering\Actions;

use App\Modules\Catalog\Data\PackageData;
use App\Modules\Inventory\Contracts\InsufficientStockException;
use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Ordering\Data\CustomerData;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Events\OrderPlaced;
use App\Modules\Ordering\Exceptions\TooManyPendingOrdersException;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Services\InvoiceNumberGenerator;
use App\Modules\Shared\Services\ActivityLogger;
use App\Modules\Shared\Settings\SiteSettings;
use Illuminate\Support\Facades\DB;

/**
 * Membuat pesanan baru dan mengunci voucher selama batas waktu pembayaran.
 * Pesanan dan reservasi berada dalam satu transaksi: jika stok tidak cukup,
 * tidak ada pesanan yang tersimpan.
 */
class PlaceOrder
{
    public const MAX_PENDING_PER_EMAIL = 3;

    public function __construct(
        private readonly VoucherReservations $reservations,
        private readonly InvoiceNumberGenerator $invoices,
        private readonly ActivityLogger $logger,
        private readonly SiteSettings $settings,
    ) {}

    /**
     * @throws TooManyPendingOrdersException
     * @throws InsufficientStockException
     */
    public function __invoke(PackageData $package, int $quantity, CustomerData $customer): Order
    {
        $this->guardPendingLimit($customer->email);

        $expiresAt = now()->addMinutes($this->settings->reservationMinutes());
        $total = $package->price * $quantity;

        $order = DB::transaction(function () use ($package, $quantity, $customer, $expiresAt, $total) {
            $order = Order::query()->create([
                'invoice_number' => $this->invoices->next(),
                'user_id' => $customer->userId,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'subtotal' => $total,
                'fee' => 0,
                'total' => $total,
                'status' => OrderStatus::Pending,
                'expires_at' => $expiresAt,
            ]);

            $order->items()->create([
                'package_id' => $package->id,
                'package_name' => $package->name,
                'quantity' => $quantity,
                'unit_price' => $package->price,
                'total' => $total,
            ]);

            $this->reservations->reserve($package->id, $quantity, $order->invoice_number, $expiresAt);

            return $order;
        });

        event(new OrderPlaced($order->invoice_number));

        $this->logger->log('order.placed', $order, [
            'invoice' => $order->invoice_number,
            'package_id' => $package->id,
            'quantity' => $quantity,
            'total' => $total,
        ]);

        return $order;
    }

    private function guardPendingLimit(string $email): void
    {
        $pending = Order::query()
            ->pending()
            ->where('expires_at', '>', now())
            ->where('customer_email', $email)
            ->count();

        if ($pending >= self::MAX_PENDING_PER_EMAIL) {
            throw TooManyPendingOrdersException::forLimit(self::MAX_PENDING_PER_EMAIL);
        }
    }
}
