<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Modules\Ordering\Http\Resources\OrderResource;
use App\Modules\Ordering\Mail\OrderHistoryLinkMail;
use App\Modules\Ordering\Models\Order;
use App\Modules\Ordering\Services\OrderAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Riwayat Pembelian" untuk guest: magic link ke email, tanpa akun.
 * Email tidak pernah ditaruh di URL; link berisi token acak yang disimpan di cache.
 */
class OrderHistoryController
{
    public const LINK_MINUTES = 30;

    private const MAX_REQUESTS = 3;

    private const DECAY_SECONDS = 600;

    private const SENT = 'Jika email tersebut pernah dipakai untuk memesan, link riwayat sudah kami kirim. Cek kotak masuk (dan folder spam).';

    public function __construct(private readonly OrderAccess $access) {}

    public function create(): Response
    {
        return Inertia::render('Ordering/History/Request');
    }

    public function store(Request $request): RedirectResponse
    {
        $email = strtolower(trim((string) $request->validate(['email' => ['required', 'email', 'max:255']])['email']));

        $key = 'order-history:'.sha1($email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, self::MAX_REQUESTS)) {
            return back()->withErrors(['email' => 'Terlalu banyak permintaan. Coba lagi dalam beberapa menit.']);
        }
        RateLimiter::hit($key, self::DECAY_SECONDS);

        // Respons sama untuk email yang ada maupun tidak, agar tidak bocor siapa yang pernah membeli.
        if (Order::query()->where('customer_email', $email)->exists()) {
            $token = Str::random(48);
            Cache::put("order-history-token:{$token}", $email, now()->addMinutes(self::LINK_MINUTES));

            Mail::to($email)->send(new OrderHistoryLinkMail(route('orders.history.open', $token), self::LINK_MINUTES));
        }

        return back()->with('status', self::SENT);
    }

    /**
     * Magic link dari email: tandai browser ini memiliki email tersebut.
     */
    public function open(Request $request, string $token): RedirectResponse
    {
        $email = Cache::get("order-history-token:{$token}");

        if (! is_string($email)) {
            return to_route('orders.history.create')->with('error', 'Link riwayat sudah kedaluwarsa atau tidak valid. Silakan minta link baru.');
        }

        $this->access->grantEmail($request, $email, self::LINK_MINUTES);

        return to_route('orders.history.index');
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $email = $this->access->verifiedEmail($request);

        if (! $email) {
            return to_route('orders.history.create');
        }

        $orders = Order::query()->with('items')->where('customer_email', $email)->latest('id')->paginate(10);

        return Inertia::render('Ordering/History/Index', [
            'email' => $email,
            'orders' => $orders->through(fn (Order $order) => (new OrderResource($order))->resolve()),
        ]);
    }
}
