<?php

namespace App\Modules\Ordering\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VoucherDeliveredMail extends Mailable
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data  dari OrderMailData (dengan kode voucher)
     */
    public function __construct(public readonly array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Kode voucher Anda – pesanan {$this->data['order']->invoice_number}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.ordering.vouchers', with: $this->data);
    }
}
