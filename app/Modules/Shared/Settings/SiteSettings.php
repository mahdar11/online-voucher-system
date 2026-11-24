<?php

namespace App\Modules\Shared\Settings;

use App\Modules\Shared\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan situs yang bisa diubah admin (disimpan di tabel settings, di-cache).
 */
class SiteSettings
{
    public const RESERVATION_MINUTES = 'ordering.reservation_minutes';

    public const MAX_QUANTITY = 'ordering.max_quantity';

    public const SUPPORT_EMAIL = 'support.email';

    public const SUPPORT_WHATSAPP = 'support.whatsapp';

    public const ANNOUNCEMENT = 'storefront.announcement';

    private const CACHE_KEY = 'site-settings';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            self::RESERVATION_MINUTES => (int) config('voucher.reservation_minutes', 15),
            self::MAX_QUANTITY => 5,
            self::SUPPORT_EMAIL => null,
            self::SUPPORT_WHATSAPP => null,
            self::ANNOUNCEMENT => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
            ->whereIn('key', array_keys($this->defaults()))
            ->pluck('value', 'key')
            ->all());

        return array_replace($this->defaults(), array_filter($stored, fn ($value) => $value !== null));
    }

    /**
     * @param  array<string, mixed>  $values  key => nilai (null/'' menghapus ke default)
     */
    public function update(array $values): void
    {
        foreach (array_intersect_key($values, $this->defaults()) as $key => $value) {
            Setting::setValue($key, $value === '' ? null : $value);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function reservationMinutes(): int
    {
        return (int) $this->all()[self::RESERVATION_MINUTES];
    }

    public function maxQuantity(): int
    {
        return (int) $this->all()[self::MAX_QUANTITY];
    }

    public function supportEmail(): ?string
    {
        return $this->all()[self::SUPPORT_EMAIL];
    }

    /**
     * Nomor WhatsApp dalam format internasional tanpa "+", mis. 6281234567890.
     */
    public function supportWhatsapp(): ?string
    {
        return $this->all()[self::SUPPORT_WHATSAPP];
    }

    public function announcement(): ?string
    {
        return $this->all()[self::ANNOUNCEMENT];
    }
}
