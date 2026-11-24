<x-mail::message>
# Riwayat pembelian Anda

Seseorang (semoga Anda) meminta link untuk melihat riwayat pembelian voucher dengan email ini.

<x-mail::button :url="$url">
Lihat Riwayat Pembelian
</x-mail::button>

Link berlaku **{{ $minutes }} menit**. Jika Anda tidak memintanya, abaikan email ini;
tidak ada yang bisa melihat pesanan Anda tanpa link tersebut.

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
