<?php

namespace App\Modules\Catalog\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman publik toko voucher. Katalog paket dibangun di tahap 3.
 */
class StorefrontController
{
    public function home(): Response
    {
        return Inertia::render('Catalog/Home');
    }
}
