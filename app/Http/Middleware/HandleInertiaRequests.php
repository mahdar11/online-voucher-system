<?php

namespace App\Http\Middleware;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Settings\SiteSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'site' => fn () => [
                'supportEmail' => app(SiteSettings::class)->supportEmail(),
                'supportWhatsapp' => app(SiteSettings::class)->supportWhatsapp(),
            ],
            'auth' => [
                'user' => fn () => $this->presentUser($request->user()),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'details' => fn () => $request->session()->get('details', []),
            ],
        ];
    }

    /**
     * Frontend menerima keputusan (isStaff, can.*) dari backend, sehingga nama
     * role/permission tidak perlu ditulis ulang di Svelte.
     *
     * @return array<string, mixed>|null
     */
    private function presentUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'emailVerified' => $user->hasVerifiedEmail(),
            'roleLabel' => $user->primaryRole()?->label(),
            'isStaff' => $user->isStaff(),
            'can' => $user->permissionMap(),
        ];
    }
}
