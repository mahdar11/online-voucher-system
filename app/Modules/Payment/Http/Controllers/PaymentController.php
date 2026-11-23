<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Ordering\Contracts\OrderPayments;
use App\Modules\Payment\Actions\StartPayment;
use App\Modules\Payment\Exceptions\PaymentUnavailableException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PaymentController
{
    /**
     * Membuat/memakai ulang tagihan lalu mengarahkan pembeli ke halaman bayar Xendit.
     */
    public function start(Request $request, string $invoiceNumber, OrderPayments $orders, StartPayment $startPayment): Response
    {
        abort_unless($orders->canAccess($request, $invoiceNumber), 404);

        try {
            $payment = $startPayment($orders->find($invoiceNumber));
        } catch (PaymentUnavailableException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return Inertia::location($payment->invoice_url);
    }
}
