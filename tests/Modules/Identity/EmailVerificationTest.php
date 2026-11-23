<?php

namespace Tests\Modules\Identity;

use App\Modules\Identity\Models\User;
use App\Modules\Ordering\Models\Order;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ModuleTestCase;

class EmailVerificationTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function guestOrder(string $email): Order
    {
        $package = $this->package(['slug' => 'paket-'.uniqid()]);
        $this->stock($package, 2);

        $this->post("/beli/{$package->slug}", ['quantity' => 1, 'name' => 'Tamu', 'email' => $email])
            ->assertSessionHasNoErrors();
        $this->flushSession();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }

    public function test_registration_sends_indonesian_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Budi', 'email' => 'budi@example.test',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ]);

        $user = User::query()->where('email', 'budi@example.test')->sole();
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
            return $notification->toMail($user)->subject === 'Verifikasi email akun '.config('app.name');
        });
    }

    public function test_unverified_users_can_still_use_account_and_checkout(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('customer');

        $this->actingAs($user)->get('/akun')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.emailVerified', false));

        $package = $this->package(['slug' => 'harian']);
        $this->stock($package, 1);
        $this->actingAs($user)->post('/beli/harian', ['quantity' => 1, 'name' => 'Budi', 'email' => $user->email])
            ->assertSessionHasNoErrors();
    }

    public function test_verifying_email_claims_matching_guest_orders_only(): void
    {
        $mine = $this->guestOrder('budi@example.test');
        $otherEmail = $this->guestOrder('orang-lain@example.test');

        $owner = $this->customer();
        $ownedByOther = $this->guestOrder('budi@example.test');
        $ownedByOther->update(['user_id' => $owner->id]);

        $user = User::factory()->unverified()->create(['email' => 'budi@example.test']);
        $user->assignRole('customer');

        // Belum terverifikasi: pesanan guest belum tersambung.
        $this->actingAs($user)->get('/akun/pesanan')->assertInertia(fn (Assert $page) => $page->where('orders.total', 0));

        $this->actingAs($user)->get($this->verificationUrl($user))->assertRedirect();

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
        $this->assertSame($user->id, $mine->refresh()->user_id);
        $this->assertNull($otherEmail->refresh()->user_id);
        $this->assertSame($owner->id, $ownedByOther->refresh()->user_id);

        $this->actingAs($user)->get('/akun/pesanan')->assertInertia(fn (Assert $page) => $page
            ->where('orders.total', 1)
            ->where('orders.data.0.invoice_number', $mine->invoice_number));
    }

    public function test_verified_user_logging_in_claims_new_guest_orders(): void
    {
        $user = User::factory()->create(['email' => 'sari@example.test']);
        $user->assignRole('customer');

        $order = $this->guestOrder('sari@example.test');

        $this->post('/login', ['email' => 'sari@example.test', 'password' => 'password'])->assertRedirect('/akun');

        $this->assertSame($user->id, $order->refresh()->user_id);
    }

    public function test_unverified_login_does_not_claim_orders(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'sari@example.test']);
        $user->assignRole('customer');
        $order = $this->guestOrder('sari@example.test');

        $this->post('/login', ['email' => 'sari@example.test', 'password' => 'password']);

        $this->assertNull($order->refresh()->user_id);
    }
}
