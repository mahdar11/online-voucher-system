<?php

namespace App\Modules\Identity;

use App\Modules\Identity\Actions\Fortify\CreateNewUser;
use App\Modules\Identity\Actions\Fortify\ResetUserPassword;
use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Http\Responses\LoginResponse;
use App\Modules\Identity\Listeners\ConfirmEmailOwnership;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\EloquentUserDirectory;
use App\Modules\Shared\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function register(): void
    {
        // Alias tetap untuk kolom polymorphic (bukan nama class), agar data tidak
        // rusak bila class dipindah. Lihat migration normalize_morph_types.
        Relation::enforceMorphMap([
            'user' => User::class,
        ]);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(UserDirectory::class, EloquentUserDirectory::class);
    }

    protected function bootModule(): void
    {
        $this->registerAdminRouteMacro();
        $this->configureFortify();
        $this->configureEmailVerification();

        Event::subscribe(ConfirmEmailOwnership::class);
    }

    /**
     * Email verifikasi dalam bahasa Indonesia. Login dan checkout tidak mewajibkan
     * verifikasi; verifikasi dipakai untuk membuktikan kepemilikan email.
     */
    private function configureEmailVerification(): void
    {
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject('Verifikasi email akun '.config('app.name'))
            ->greeting('Halo '.($notifiable->name ?? '').',')
            ->line('Klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi Email', $url)
            ->line('Setelah terverifikasi, pesanan yang pernah Anda buat tanpa login dengan email ini akan muncul di akun Anda.')
            ->line('Jika Anda tidak membuat akun, abaikan email ini.')
            ->salutation('Salam, '.config('app.name')));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('Identity/Auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));
    }

    /**
     * Route::admin(fn () => ...) untuk semua modul: wajib login, role staff,
     * prefix /admin, dan nama route "admin.".
     */
    private function registerAdminRouteMacro(): void
    {
        Route::macro('admin', function (Closure $routes): void {
            Route::middleware(['auth', 'role:'.implode('|', Role::staffValues())])
                ->prefix('admin')
                ->name('admin.')
                ->group($routes);
        });
    }

    private function configureFortify(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', Str::lower($request->input(Fortify::username())))->first();

            if (! $user || ! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('auth.inactive'),
                ]);
            }

            return $user;
        });

        Fortify::loginView(fn (Request $request) => Inertia::render('Identity/Auth/Login', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('Identity/Auth/Register'));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('Identity/Auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Identity/Auth/ResetPassword', [
            'token' => $request->route('token'),
            'email' => $request->query('email', ''),
        ]));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
