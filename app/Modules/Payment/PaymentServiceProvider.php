<?php

namespace App\Modules\Payment;

use App\Modules\Shared\ModuleServiceProvider;

/**
 * Integrasi Xendit. Fiturnya dibangun di tahap 4.
 */
class PaymentServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
