<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Identity\Data\UserProfile;
use App\Modules\Identity\Models\User;

class EloquentUserDirectory implements UserDirectory
{
    public function namesFor(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        return User::query()->whereKey($ids)->pluck('name', 'id')->all();
    }

    public function profile(int $id): ?UserProfile
    {
        $user = User::query()->find($id);

        return $user ? new UserProfile($user->id, $user->name, $user->email, $user->phone) : null;
    }
}
