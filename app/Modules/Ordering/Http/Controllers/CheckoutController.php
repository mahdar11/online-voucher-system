<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Modules\Catalog\Contracts\PackageCatalog;
use App\Modules\Catalog\Contracts\PackageStockReader;
use App\Modules\Catalog\Data\PackageData;
use App\Modules\Catalog\Data\PackageStock;
use App\Modules\Catalog\Data\StockAvailability;
use App\Modules\Catalog\Data\StorefrontPackage;
use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Inventory\Contracts\InsufficientStockException;
use App\Modules\Ordering\Actions\PlaceOrder;
use App\Modules\Ordering\Exceptions\TooManyPendingOrdersException;
use App\Modules\Ordering\Http\Requests\CheckoutRequest;
use App\Modules\Ordering\Services\OrderAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController
{
    public function __construct(
        private readonly PackageCatalog $catalog,
        private readonly PackageStockReader $stockReader,
    ) {}

    public function create(Request $request, string $slug, UserDirectory $users): Response
    {
        $package = $this->findPackage($slug);
        $availability = $this->availability($package);
        $profile = $request->user() ? $users->profile($request->user()->getAuthIdentifier()) : null;

        return Inertia::render('Ordering/Checkout/Create', [
            'package' => StorefrontPackage::make($package, $availability),
            'maxQuantity' => min(PlaceOrder::MAX_QUANTITY, $availability->purchasable),
            'reservationMinutes' => (int) config('voucher.reservation_minutes', 15),
            'customer' => [
                'name' => $profile?->name ?? '',
                'email' => $profile?->email ?? '',
                'phone' => $profile?->phone ?? '',
            ],
        ]);
    }

    public function store(CheckoutRequest $request, string $slug, PlaceOrder $placeOrder, OrderAccess $access): RedirectResponse
    {
        $package = $this->findPackage($slug);

        try {
            $order = $placeOrder($package, (int) $request->validated('quantity'), $request->customer());
        } catch (InsufficientStockException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()]);
        } catch (TooManyPendingOrdersException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()]);
        }

        $access->grant($request, $order);

        return to_route('orders.show', $order->invoice_number)
            ->with('success', 'Pesanan dibuat. Selesaikan pembayaran sebelum batas waktu berakhir.');
    }

    private function findPackage(string $slug): PackageData
    {
        return $this->catalog->findActiveBySlug($slug) ?? abort(404);
    }

    private function availability(PackageData $package): StockAvailability
    {
        $stock = $this->stockReader->stockFor([$package->id]);

        return StockAvailability::from($stock[$package->id] ?? new PackageStock);
    }
}
