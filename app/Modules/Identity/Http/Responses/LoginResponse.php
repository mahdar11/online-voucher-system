<?php

namespace App\Modules\Identity\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Staff diarahkan ke /admin, customer ke /akun.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        $home = $request->user()->isStaff() ? route('admin.dashboard') : route('account.dashboard');

        return redirect()->intended($home);
    }
}
