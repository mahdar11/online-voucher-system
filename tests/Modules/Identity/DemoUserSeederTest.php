<?php

namespace Tests\Modules\Identity;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Database\Seeders\DemoUserSeeder;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'voucher.demo_users.admin.password' => 'admin-demo-123',
            'voucher.demo_users.customer.password' => 'customer-demo-123',
        ]);
    }

    public function test_it_does_nothing_outside_local_environment(): void
    {
        $this->seed(DemoUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_creates_demo_admin_and_customer_in_local(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DemoUserSeeder::class);
        $this->seed(DemoUserSeeder::class); // aman dijalankan ulang

        $this->assertDatabaseCount('users', 2);

        $admin = User::query()->where('email', 'admin@online-voucher.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@online-voucher.test')->firstOrFail();

        $this->assertSame([Role::Admin->value], $admin->getRoleNames()->all());
        $this->assertSame([Role::Customer->value], $customer->getRoleNames()->all());

        // Kembalikan ke "testing" agar verifikasi CSRF dilewati seperti test lain.
        $this->app['env'] = 'testing';

        $this->post('/login', ['email' => $admin->email, 'password' => 'admin-demo-123'])
            ->assertRedirect('/admin');
    }
}
