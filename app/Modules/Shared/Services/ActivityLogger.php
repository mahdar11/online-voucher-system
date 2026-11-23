<?php

namespace App\Modules\Shared\Services;

use App\Modules\Shared\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;

/**
 * Mencatat aktivitas penting. Pelaku dan IP diambil dari Laravel Context
 * (diisi oleh middleware ShareActivityContext pada request web), sehingga
 * logger ini juga bisa dipakai dari command atau job tanpa Request.
 */
class ActivityLogger
{
    public const CONTEXT_USER_ID = 'activity.user_id';

    public const CONTEXT_IP = 'activity.ip';

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, array $properties = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'user_id' => Context::getHidden(self::CONTEXT_USER_ID),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => Context::getHidden(self::CONTEXT_IP),
            // Diisi aplikasi (UTC), bukan default database: zona waktu server
            // PostgreSQL bisa berbeda (mis. WIB) sehingga waktunya bergeser.
            'created_at' => now(),
        ]);
    }
}
