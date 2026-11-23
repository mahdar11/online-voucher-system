<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

/**
 * Perubahan user ditolak karena melanggar aturan pengaman; pesannya aman ditampilkan ke admin.
 */
class UserManagementException extends RuntimeException
{
    public static function cannotChangeSelf(): self
    {
        return new self('Anda tidak bisa menonaktifkan atau mengubah role akun Anda sendiri.');
    }

    public static function lastSuperAdmin(): self
    {
        return new self('Harus ada minimal satu super admin yang aktif.');
    }
}
