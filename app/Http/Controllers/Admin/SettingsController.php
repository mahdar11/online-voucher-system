<?php

namespace App\Http\Controllers\Admin;

use App\Modules\Shared\Services\ActivityLogger;
use App\Modules\Shared\Settings\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController
{
    public function edit(SiteSettings $settings): Response
    {
        $all = $settings->all();

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'reservation_minutes' => $all[SiteSettings::RESERVATION_MINUTES],
                'max_quantity' => $all[SiteSettings::MAX_QUANTITY],
                'support_email' => $all[SiteSettings::SUPPORT_EMAIL] ?? '',
                'support_whatsapp' => $all[SiteSettings::SUPPORT_WHATSAPP] ?? '',
                'announcement' => $all[SiteSettings::ANNOUNCEMENT] ?? '',
            ],
        ]);
    }

    public function update(Request $request, SiteSettings $settings, ActivityLogger $logger): RedirectResponse
    {
        $request->merge([
            // 0812-3456-7890 / +62812... -> 62812... (format link wa.me)
            'support_whatsapp' => $request->filled('support_whatsapp')
                ? preg_replace('/^0/', '62', preg_replace('/\D/', '', (string) $request->input('support_whatsapp')))
                : null,
        ]);

        $data = $request->validate([
            'reservation_minutes' => ['required', 'integer', 'min:5', 'max:60'],
            'max_quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_whatsapp' => ['nullable', 'regex:/^628[0-9]{7,12}$/'],
            'announcement' => ['nullable', 'string', 'max:300'],
        ], [
            'support_whatsapp.regex' => 'Nomor WhatsApp tidak valid, contoh: 081234567890.',
        ], [
            'reservation_minutes' => 'batas waktu bayar',
            'max_quantity' => 'maksimal voucher per pesanan',
            'support_email' => 'email bantuan',
            'support_whatsapp' => 'WhatsApp bantuan',
            'announcement' => 'pengumuman',
        ]);

        $settings->update([
            SiteSettings::RESERVATION_MINUTES => (int) $data['reservation_minutes'],
            SiteSettings::MAX_QUANTITY => (int) $data['max_quantity'],
            SiteSettings::SUPPORT_EMAIL => $data['support_email'] ?? null,
            SiteSettings::SUPPORT_WHATSAPP => $data['support_whatsapp'] ?? null,
            SiteSettings::ANNOUNCEMENT => $data['announcement'] ?? null,
        ]);

        $logger->log('settings.updated', null, ['keys' => array_keys($data)]);

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
