<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\UserDirectory;
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
}
