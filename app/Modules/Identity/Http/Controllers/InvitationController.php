<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\Fortify\PasswordValidationRules;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staf menerima undangan: membuat password lewat link bertanda tangan (sekali pakai).
 * Membuka link dari email sekaligus membuktikan kepemilikan email.
 */
class InvitationController
{
    use PasswordValidationRules;

    public function show(User $user): Response|RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return $this->alreadyAccepted();
        }

        return Inertia::render('Identity/Auth/AcceptInvitation', [
            'name' => $user->name,
            'email' => $user->email,
            'roleLabel' => $user->primaryRole()?->label(),
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return $this->alreadyAccepted();
        }

        $request->validate(['password' => $this->passwordRules()]);

        $user->forceFill(['password' => $request->input('password')])->save();
        $user->markEmailAsVerified();
        event(new Verified($user));

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route($user->isStaff() ? 'admin.dashboard' : 'account.dashboard')
            ->with('success', 'Akun Anda sudah aktif. Selamat datang!');
    }

    private function alreadyAccepted(): RedirectResponse
    {
        return to_route('login')->with('status', 'Undangan ini sudah dipakai. Silakan masuk dengan password Anda.');
    }
}
