<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Exceptions\UserManagementException;
use App\Modules\Identity\Mail\StaffInvitationMail;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Pengelolaan user oleh super admin: undang staf, ubah data/role, aktif/nonaktif.
 * Pengaman: tidak bisa mengubah role/menonaktifkan diri sendiri, dan harus
 * selalu ada minimal satu super admin aktif.
 */
class ManageUsers
{
    public const INVITATION_HOURS = 72;

    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Membuat akun staf dengan password acak (tidak pernah dipakai) lalu mengirim
     * undangan untuk membuat password sendiri.
     */
    public function invite(string $name, string $email, ?string $phone, Role $role): User
    {
        $user = DB::transaction(function () use ($name, $email, $phone, $role) {
            $user = User::query()->create([
                'name' => $name,
                'email' => strtolower($email),
                'phone' => $phone,
                'password' => Str::password(40),
                'is_active' => true,
            ]);
            $user->syncRoles([$role->value]);

            return $user;
        });

        $this->sendInvitation($user);
        $this->logger->log('user.invited', $user, ['email' => $user->email, 'role' => $role->value]);

        return $user;
    }

    public function sendInvitation(User $user): void
    {
        $url = URL::temporarySignedRoute('invitation.show', now()->addHours(self::INVITATION_HOURS), ['user' => $user->id]);

        Mail::to($user->email)->send(new StaffInvitationMail(
            $user->name,
            $user->primaryRole()?->label() ?? 'Staf',
            $url,
            self::INVITATION_HOURS,
        ));
    }

    /**
     * @throws UserManagementException
     */
    public function update(User $actor, User $user, string $name, ?string $phone, Role $role): User
    {
        $roleChanges = $user->primaryRole() !== $role;

        if ($roleChanges) {
            $this->guardNotSelf($actor, $user);
            $this->guardKeepsASuperAdmin($user, losesSuperAdmin: $role !== Role::SuperAdmin);
        }

        DB::transaction(function () use ($user, $name, $phone, $role, $roleChanges) {
            $user->update(['name' => $name, 'phone' => $phone]);

            if ($roleChanges) {
                $user->syncRoles([$role->value]);
            }
        });

        $this->logger->log('user.updated', $user, ['role' => $role->value, 'role_changed' => $roleChanges]);

        return $user;
    }

    /**
     * @throws UserManagementException
     */
    public function setActive(User $actor, User $user, bool $active): User
    {
        if (! $active) {
            $this->guardNotSelf($actor, $user);
            $this->guardKeepsASuperAdmin($user, losesSuperAdmin: true);
        }

        $user->update(['is_active' => $active]);
        $this->logger->log($active ? 'user.activated' : 'user.deactivated', $user);

        return $user;
    }

    private function guardNotSelf(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw UserManagementException::cannotChangeSelf();
        }
    }

    private function guardKeepsASuperAdmin(User $user, bool $losesSuperAdmin): void
    {
        if (! $losesSuperAdmin || ! $user->hasRole(Role::SuperAdmin->value) || ! $user->is_active) {
            return;
        }

        $others = User::role(Role::SuperAdmin->value)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->count();

        if ($others === 0) {
            throw UserManagementException::lastSuperAdmin();
        }
    }
}
