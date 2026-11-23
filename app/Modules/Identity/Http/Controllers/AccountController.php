<?php

namespace App\Modules\Identity\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AccountController
{
    public function dashboard(): Response
    {
        return Inertia::render('Identity/Account/Dashboard');
    }
}
