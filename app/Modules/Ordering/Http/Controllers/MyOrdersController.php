<?php

namespace App\Modules\Ordering\Http\Controllers;

use App\Modules\Ordering\Http\Resources\OrderResource;
use App\Modules\Ordering\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyOrdersController
{
    private const PER_PAGE = 10;

    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->with('items')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return Inertia::render('Ordering/Orders/Index', [
            'orders' => $orders->through(fn (Order $order) => (new OrderResource($order))->resolve()),
        ]);
    }
}
