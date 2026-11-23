<?php

namespace App\Http\Controllers\Admin;

use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Ordering\Data\SalesPeriod;
use App\Modules\Shared\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController
{
    private const PER_PAGE = 50;

    public function index(Request $request, UserDirectory $users): Response
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $logs = ActivityLog::query()
            ->when($filters['action'] ?? null, fn (Builder $q, string $action) => $q->where('action', 'like', addcslashes($action, '%_\\').'%'))
            ->when($filters['user'] ?? null, fn (Builder $q, int $user) => $q->where('user_id', $user))
            ->when($filters['from'] ?? null, function (Builder $q) use ($filters) {
                $period = SalesPeriod::days($filters['from'], $filters['until'] ?? $filters['from']);
                $q->whereBetween('created_at', [$period->fromUtc(), $period->untilUtc()]);
            })
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $actorIds = ActivityLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id')->all();
        $actors = $users->namesFor($actorIds);

        return Inertia::render('Admin/ActivityLog', [
            'logs' => $logs->through(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->user_id ? ($actors[$log->user_id] ?? 'Pengguna #'.$log->user_id) : 'Sistem / pembeli',
                'invoice' => $log->properties['invoice'] ?? null,
                'properties' => collect($log->properties ?? [])->except('invoice')->map(fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v))->all(),
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]),
            'filters' => [
                'action' => $filters['action'] ?? '',
                'user' => isset($filters['user']) ? (string) $filters['user'] : '',
                'from' => $filters['from'] ?? '',
                'until' => $filters['until'] ?? '',
            ],
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action')->all(),
            'actors' => collect($actors)->map(fn ($name, $id) => ['value' => (string) $id, 'label' => $name])->values()->all(),
        ]);
    }
}
