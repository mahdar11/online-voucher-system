<?php

namespace App\Modules\Ordering\Http\Controllers\Admin;

use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Inventory\Contracts\VoucherReservations;
use App\Modules\Inventory\Data\VoucherCode;
use App\Modules\Ordering\Actions\AdminOrderActions;
use App\Modules\Ordering\Actions\CancelOrder;
use App\Modules\Ordering\Contracts\PaymentHistoryReader;
use App\Modules\Ordering\Data\PaymentAttempt;
use App\Modules\Ordering\Data\SalesPeriod;
use App\Modules\Ordering\Enums\OrderStatus;
use App\Modules\Ordering\Exceptions\OrderActionException;
use App\Modules\Ordering\Exceptions\OrderNotCancellableException;
use App\Modules\Ordering\Http\Resources\AdminOrderResource;
use App\Modules\Ordering\Models\Order;
use App\Modules\Shared\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController
{
    private const PER_PAGE = 25;

    public const NEEDS_ACTION = 'perlu-tindakan';

    public function __construct(private readonly AdminOrderActions $actions) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([...array_column(OrderStatus::cases(), 'value'), self::NEEDS_ACTION])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $orders = Order::query()
            ->with('items')
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $status === self::NEEDS_ACTION
                ? $q->where(fn (Builder $w) => $w
                    ->where('status', OrderStatus::RefundRequired)
                    ->orWhere(fn (Builder $p) => $p->where('status', OrderStatus::Paid)->where('paid_at', '<', now()->subMinutes(10))))
                : $q->where('status', $status))
            ->when($filters['from'] ?? null, function (Builder $q) use ($filters) {
                $period = SalesPeriod::days($filters['from'], $filters['until'] ?? $filters['from']);
                $q->whereBetween('created_at', [$period->fromUtc(), $period->untilUtc()]);
            })
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $q->where(fn (Builder $w) => $w
                    ->where('invoice_number', 'ilike', $like)
                    ->orWhere('customer_name', 'ilike', $like)
                    ->orWhere('customer_email', 'ilike', $like));
            })
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Order $order) => (new AdminOrderResource($order))->resolve());

        return Inertia::render('Ordering/Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $filters['status'] ?? '',
                'from' => $filters['from'] ?? '',
                'until' => $filters['until'] ?? '',
                'search' => $filters['search'] ?? '',
            ],
            'statuses' => [
                ['value' => self::NEEDS_ACTION, 'label' => 'Perlu tindakan'],
                ...array_map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()], OrderStatus::cases()),
            ],
        ]);
    }

    public function show(
        Order $order,
        VoucherReservations $reservations,
        PaymentHistoryReader $payments,
        UserDirectory $users,
    ): Response {
        $logs = ActivityLog::query()
            ->where('properties->invoice', $order->invoice_number)
            ->orderBy('id')
            ->get();
        $actors = $users->namesFor($logs->pluck('user_id')->filter()->all());

        return Inertia::render('Ordering/Admin/Orders/Show', [
            'order' => (new AdminOrderResource($order->load('items')))->resolve(),
            'vouchers' => $order->status->hasVouchers()
                ? array_map(fn (VoucherCode $code) => $code->toArray(), $reservations->codesFor($order->invoice_number))
                : [],
            'payments' => array_map(fn (PaymentAttempt $attempt) => $attempt->toArray(), $payments->attemptsFor($order->invoice_number)),
            'activity' => $logs->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->user_id ? ($actors[$log->user_id] ?? 'Pengguna #'.$log->user_id) : 'Sistem / pembeli',
                'note' => $log->properties['note'] ?? null,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function resend(Order $order): RedirectResponse
    {
        try {
            $this->actions->resendVouchers($order);
        } catch (OrderActionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Email voucher dikirim ulang ke {$order->customer_email}.");
    }

    public function refund(Request $request, Order $order): RedirectResponse
    {
        $note = (string) $request->validate([
            'note' => ['required', 'string', 'max:255'],
        ], [], ['note' => 'catatan refund'])['note'];

        try {
            $this->actions->markRefunded($order, $note);
        } catch (OrderActionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Pesanan {$order->invoice_number} ditandai sudah direfund.");
    }

    public function cancel(Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        try {
            $cancelOrder($order);
        } catch (OrderNotCancellableException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Pesanan {$order->invoice_number} dibatalkan; voucher kembali ke stok.");
    }
}
