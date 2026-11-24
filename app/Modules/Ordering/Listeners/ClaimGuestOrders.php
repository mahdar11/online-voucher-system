<?php

namespace App\Modules\Ordering\Listeners;

use App\Modules\Identity\Events\EmailOwnershipConfirmed;
use App\Modules\Ordering\Models\Order;
use App\Modules\Shared\Services\ActivityLogger;

/**
 * Pesanan guest dengan email yang sama disambungkan ke akun, tetapi hanya setelah
 * kepemilikan email terbukti (verifikasi). Pesanan milik akun lain tidak disentuh.
 */
class ClaimGuestOrders
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function handle(EmailOwnershipConfirmed $event): void
    {
        $claimed = Order::query()
            ->whereNull('user_id')
            ->where('customer_email', strtolower($event->email))
            ->update(['user_id' => $event->userId]);

        if ($claimed > 0) {
            $this->logger->log('orders.claimed', null, ['user_id' => $event->userId, 'count' => $claimed]);
        }
    }
}
