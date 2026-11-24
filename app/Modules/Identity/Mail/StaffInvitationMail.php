<?php

namespace App\Modules\Identity\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class StaffInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
        public readonly string $roleLabel,
        public readonly string $url,
        public readonly int $hours,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Undangan menjadi '.$this->roleLabel.' di '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.identity.invitation');
    }
}
