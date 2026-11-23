<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Database\Factories\UserFactory;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\Role;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(Role::staffValues());
    }

    /**
     * Role utama untuk ditampilkan (user di aplikasi ini hanya punya satu role).
     */
    public function primaryRole(): ?Role
    {
        $name = $this->getRoleNames()->first();

        return $name ? Role::tryFrom($name) : null;
    }

    /**
     * Peta permission untuk frontend, contoh: ['managePackages' => true, ...].
     *
     * @return array<string, bool>
     */
    public function permissionMap(): array
    {
        $granted = $this->getAllPermissions()->pluck('name')->all();

        $map = [];
        foreach (Permission::cases() as $permission) {
            $map[$permission->key()] = in_array($permission->value, $granted, true);
        }

        return $map;
    }
}
