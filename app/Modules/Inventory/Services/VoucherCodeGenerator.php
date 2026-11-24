<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\Voucher;
use RuntimeException;

class VoucherCodeGenerator
{
    /**
     * Huruf besar dan angka tanpa karakter yang mudah tertukar (0/O, 1/I/L).
     */
    public const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const PASSWORD_LENGTH = 6;

    private const MAX_ATTEMPTS = 20;

    public function code(int $length, string $prefix = ''): string
    {
        return $prefix.$this->random($length);
    }

    public function password(): string
    {
        return $this->random(self::PASSWORD_LENGTH);
    }

    /**
     * Membuat sejumlah kode unik yang belum dipakai di paket tersebut.
     *
     * @return list<string>
     */
    public function uniqueCodes(int $packageId, int $quantity, int $length, string $prefix = ''): array
    {
        $codes = [];

        for ($attempt = 0; count($codes) < $quantity; $attempt++) {
            if ($attempt >= self::MAX_ATTEMPTS) {
                throw new RuntimeException('Gagal membuat kode unik. Coba perbesar panjang kode.');
            }

            $candidates = [];
            while (count($candidates) < $quantity - count($codes)) {
                $code = $this->code($length, $prefix);

                if (! isset($codes[$code])) {
                    $candidates[$code] = true;
                }
            }

            $taken = Voucher::query()
                ->where('package_id', $packageId)
                ->whereIn('code', self::keys($candidates))
                ->pluck('code')
                ->all();

            $codes += array_diff_key($candidates, array_flip($taken));
        }

        return self::keys($codes);
    }

    /**
     * PHP mengubah key array berupa angka (mis. "234567") menjadi integer,
     * sedangkan kolom kode bertipe teks di PostgreSQL. Selalu kembalikan string.
     *
     * @param  array<array-key, mixed>  $array
     * @return list<string>
     */
    public static function keys(array $array): array
    {
        return array_map('strval', array_keys($array));
    }

    private function random(int $length): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= self::ALPHABET[random_int(0, $max)];
        }

        return $result;
    }
}
