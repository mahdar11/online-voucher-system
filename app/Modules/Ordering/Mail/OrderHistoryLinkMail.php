<?php

namespace App\Modules\Ordering\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderHistoryLinkMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $url, public readonly int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Link riwayat pembelian voucher Anda');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.ordering.history-link');
    }
}
