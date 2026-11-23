<?php

namespace App\Modules\Identity\Listeners;

use App\Modules\Identity\Events\EmailOwnershipConfirmed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Events\Dispatcher;

/**
 * Menerjemahkan event framework (Verified, Login) menjadi event milik Identity,
 * sehingga modul lain tidak perlu tahu detail autentikasi.
 */
class ConfirmEmailOwnership
{
    public function onVerified(Verified $event): void
    {
        $this->confirm($event->user);
    }

    public function onLogin(Login $event): void
    {
        if ($event->user instanceof MustVerifyEmail && $event->user->hasVerifiedEmail()) {
            $this->confirm($event->user);
        }
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Verified::class => 'onVerified',
            Login::class => 'onLogin',
        ];
    }

    private function confirm(mixed $user): void
    {
        if ($user instanceof MustVerifyEmail) {
            event(new EmailOwnershipConfirmed((int) $user->getAuthIdentifier(), $user->getEmailForVerification()));
        }
    }
}
