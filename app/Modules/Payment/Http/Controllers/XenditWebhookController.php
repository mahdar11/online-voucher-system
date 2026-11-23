<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Actions\ApplyInvoiceStatus;
use App\Modules\Payment\Gateway\GatewayInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Callback invoice dari Xendit. Diverifikasi dengan header x-callback-token.
 * Selalu menjawab 2xx untuk payload yang sah agar Xendit tidak mengirim ulang
 * tanpa henti; pembayaran tetap diproses idempoten.
 */
class XenditWebhookController
{
    public function invoice(Request $request, ApplyInvoiceStatus $applyInvoiceStatus): JsonResponse
    {
        $expected = (string) config('services.xendit.callback_token');
        $given = (string) $request->header('x-callback-token');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Invalid callback token'], 401);
        }

        if (! $request->filled('external_id') || ! $request->filled('status')) {
            return response()->json(['message' => 'Invalid payload'], 422);
        }

        $result = $applyInvoiceStatus(GatewayInvoice::fromXendit($request->all()));

        return response()->json(['received' => true, 'result' => $result]);
    }
}
