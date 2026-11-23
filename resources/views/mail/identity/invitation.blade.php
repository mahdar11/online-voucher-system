<x-mail::message>
# Anda diundang sebagai {{ $roleLabel }}

Halo {{ $name }},

Anda ditambahkan sebagai **{{ $roleLabel }}** di {{ config('app.name') }}.
Klik tombol di bawah untuk membuat password dan mengaktifkan akun Anda.

<x-mail::button :url="$url">
Buat Password
</x-mail::button>

Link berlaku **{{ $hours }} jam** dan hanya bisa dipakai sekali. Jika kedaluwarsa,
minta super admin mengirim ulang undangan.

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
