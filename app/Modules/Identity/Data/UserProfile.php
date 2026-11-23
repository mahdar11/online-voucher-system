<?php

namespace App\Modules\Identity\Data;

/**
 * Data kontak user yang boleh dipakai modul lain (mis. untuk mengisi form checkout).
 */
final readonly class UserProfile
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
    ) {}
}
