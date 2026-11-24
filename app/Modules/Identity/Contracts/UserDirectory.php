<?php

namespace App\Modules\Identity\Contracts;

use App\Modules\Identity\Data\UserProfile;

/**
 * Akses baca data user untuk modul lain, tanpa membuka model User.
 */
interface UserDirectory
{
    /**
     * @param  list<int>  $ids
     * @return array<int, string> [user_id => nama]
     */
    public function namesFor(array $ids): array;

    public function profile(int $id): ?UserProfile;
}
