<?php

namespace App\Http\Responses;

use App\Enums\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Admin diarahkan ke /admin, customer ke /akun.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        $isStaff = $request->user()->hasAnyRole([Role::SuperAdmin->value, Role::Admin->value]);

        return redirect()->intended($isStaff ? route('admin.dashboard') : route('account.dashboard'));
    }
}
