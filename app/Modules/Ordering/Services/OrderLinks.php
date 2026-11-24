<?php

namespace App\Modules\Ordering\Services;

use Illuminate\Support\Facades\URL;

/**
 * Link pesanan bertanda tangan untuk email: siapa pun yang membukanya
 * (mis. dari perangkat lain) mendapat akses ke pesanan itu di browsernya.
 */
class OrderLinks
{
    public const VALID_DAYS = 30;

    public function signed(string $invoiceNumber): string
    {
        return URL::temporarySignedRoute(
            'orders.show',
            now()->addDays(self::VALID_DAYS),
            ['order' => $invoiceNumber],
        );
    }
}
