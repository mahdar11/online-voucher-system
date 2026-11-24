# Online Voucher System

Aplikasi web untuk menjual **voucher internet hotspot** secara online. Pembeli memilih
paket, membayar lewat Xendit (QRIS, transfer bank, e-wallet), lalu kode voucher dikirim
ke emailnya. Admin mengelola paket, stok kode, pesanan, dan laporan penjualan.

Dibangun sebagai **modular monolith**: satu aplikasi Laravel dan satu database, tetapi
kodenya dibagi per domain dengan batas yang dijaga oleh test.

---

## Daftar isi

- [Tech stack](#tech-stack)
- [Arsitektur](#arsitektur)
- [Persyaratan](#persyaratan)
- [Perintah sehari-hari](#perintah-sehari-hari)
- [Alur penting](#alur-penting)
- [Konfigurasi aplikasi](#konfigurasi-aplikasi)
- [Testing](#testing)
- [Struktur folder](#struktur-folder)
- [Deploy ke server](#deploy-ke-server)

---

## Tech stack

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Svelte 5 + Inertia.js 3 (satu aplikasi, tanpa API terpisah) |
| Styling | Tailwind CSS 4 |
| Build | Vite |
| Database | PostgreSQL 16+ |
| Autentikasi | Laravel Fortify |
| Role & permission | spatie/laravel-permission |
| Pembayaran | Xendit Invoice API (dipanggil langsung lewat HTTP client Laravel) |
| Grafik | ApexCharts (dimuat hanya di halaman Laporan) |
| Queue & cache & session | Driver `database` (tidak perlu Redis) |

---

## Arsitektur

Kode dibagi menjadi enam modul di `app/Modules/`:

| Modul | Tanggung jawab |
|---|---|
| **Shared** | Dasar modul, log aktivitas, pengaturan situs |
| **Identity** | Akun, login, role & permission, undangan staf |
| **Catalog** | Paket voucher dan katalog publik |
| **Inventory** | Stok kode voucher: generate, import, reservasi, penjualan |
| **Ordering** | Checkout, pesanan, email, kedaluwarsa, laporan penjualan |
| **Payment** | Tagihan Xendit, webhook, sinkronisasi status |

### Aturan antar modul

```
Shared ← Identity ← Catalog ← Inventory ← Ordering ← Payment
```

1. **Arah ketergantungan satu arah** sesuai urutan di atas (tanpa lingkaran).
2. **Modul lain hanya boleh memakai bagian publik**: `Contracts/`, `Data/`, `Enums/`,
   dan `Events/`. Folder `Models/`, `Actions/`, dan `Http/` adalah internal modul.
3. **Kalau modul "bawah" butuh data dari modul "atas"**, ia mendefinisikan *port*
   (interface) dan modul atas yang mengisinya. Contoh: Catalog butuh jumlah stok, jadi
   Catalog punya `PackageStockReader` dan Inventory yang mengimplementasikannya.
4. **Lapisan aplikasi** (`app/Http/`, `routes/web.php`) hanya untuk halaman yang
   menggabungkan beberapa modul: dashboard, laporan, pengaturan, dan log.

Aturan ini **dijaga otomatis** oleh `tests/Architecture/ModuleBoundaryTest.php`. Kalau
sebuah modul memakai bagian internal modul lain, test gagal.

### Catatan teknis penting

- **Uang** disimpan sebagai bilangan bulat rupiah (`bigint`), bukan desimal.
- **Waktu** disimpan dalam UTC. Batas hari di laporan memakai `BUSINESS_TIMEZONE`
  (default `Asia/Jakarta`).
- **Rebutan stok** dicegah dengan `SELECT … FOR UPDATE SKIP LOCKED`, sehingga dua
  pembeli tidak pernah mendapat kode yang sama.
- **Kolom polymorphic** memakai alias tetap (`user`, `order`, …), bukan nama class,
  supaya data tidak rusak saat class dipindah.

---

## Persyaratan

| Kebutuhan | Versi minimum |
|---|---|
| PHP | 8.3 (dites di 8.4) |
| Composer | 2.x |
| PostgreSQL | 16 |
| Node.js | 20 (dites di 22) |

**Ekstensi PHP:** standar Laravel (`ctype`, `curl`, `dom`, `fileinfo`, `filter`,
`hash`, `mbstring`, `openssl`, `pcre`, `session`, `tokenizer`, `xml`) ditambah
**`pdo_pgsql`**, `bcmath`, `intl`, dan `zip`.

---

## Perintah sehari-hari

| Perintah | Kegunaan |
|---|---|
| `composer dev` | Jalankan semuanya (server, queue, scheduler, log, Vite) |
| `php artisan test` | Jalankan seluruh test |
| `php artisan test --testsuite=Architecture` | Cek batas antar modul saja |
| `npm run build` | Build aset frontend untuk produksi |
| `php artisan migrate` | Jalankan migration baru |
| `php artisan orders:expire` | Kedaluwarsakan pesanan yang lewat batas bayar |
| `php artisan queue:work` | Jalankan queue worker sendiri |
| `php artisan schedule:work` | Jalankan scheduler sendiri |
| `vendor/bin/pint` | Rapikan format kode PHP |
| `php artisan optimize:clear` | Bersihkan semua cache |

---

## Alur penting

### Checkout sampai voucher diterima

1. Pembeli memilih paket lalu mengisi data di `/beli/{slug}`.
2. Sistem **mengunci voucher** (status `reserved`) dan membuat pesanan `pending`
   dengan batas waktu bayar.
3. Email *"Selesaikan pembayaran"* dikirim, berisi link bertanda tangan (berlaku 30 hari).
4. Pembeli menekan **Bayar Sekarang** → tagihan Xendit dibuat → diarahkan ke halaman
   pembayaran Xendit. Batas waktu tagihan = sisa waktu reservasi.
5. Setelah dibayar, Xendit memanggil **webhook** `/webhooks/xendit/invoice`. Kalau
   webhook belum sampai, status dicek ulang saat pembeli kembali ke halaman pesanan.
6. Voucher menjadi `sold`, pesanan menjadi **Lunas**, kode tampil di halaman pesanan.
7. Email berisi kode voucher dikirim lewat queue, lalu pesanan menjadi **Selesai**.

### Kalau tidak dibayar

Command `orders:expire` (tiap menit) menandai pesanan **Kedaluwarsa** dan mengembalikan
vouchernya ke stok. Pembeli juga bisa membatalkan sendiri.

### Kalau pembayaran telat masuk

Sistem mencoba mengunci voucher lagi. Kalau stok habis, pesanan ditandai
**Perlu Refund**, pembeli diberi tahu lewat email, dan admin menindaklanjuti dari
halaman Pesanan.

---

## Konfigurasi aplikasi

Sebagian pengaturan **tidak ada di `.env`**, melainkan diatur dari halaman admin
**Pengaturan** (`/admin/pengaturan`) dan tersimpan di tabel `settings`:

| Pengaturan | Default | Pengaruh |
|---|---|---|
| Batas waktu bayar | 15 menit | Lama voucher dikunci dan masa berlaku tagihan Xendit |
| Maksimal voucher per pesanan | 5 | Batas di halaman checkout |
| Email & WhatsApp bantuan | kosong | Tampil di footer situs |
| Pengumuman beranda | kosong | Banner di halaman katalog |

Variabel `.env` khusus aplikasi ini:

| Variabel | Keterangan |
|---|---|
| `BUSINESS_TIMEZONE` | Zona waktu untuk batas hari di laporan (default `Asia/Jakarta`) |
| `VOUCHER_RESERVATION_MINUTES` | Nilai awal batas waktu bayar sebelum diubah dari halaman admin |
| `SUPER_ADMIN_NAME` / `_EMAIL` / `_PASSWORD` | Akun super admin yang dibuat seeder |
| `DEMO_ADMIN_*` / `DEMO_CUSTOMER_*` | Akun demo, **hanya dibuat saat `APP_ENV=local`** |
| `XENDIT_SECRET_KEY` | Secret key Xendit (rahasia) |
| `XENDIT_CALLBACK_TOKEN` | Token verifikasi webhook (rahasia) |

---

## Testing

```bash
php artisan test
```

Test memakai database terpisah **`Online-voucher-test`** (diatur di `phpunit.xml`), jadi
data pengembangan Anda aman. Buat sekali di awal:

```bash
createdb "Online-voucher-test"
```

Kelompok test:

| Folder | Isi |
|---|---|
| `tests/Architecture/` | Batas antar modul dan alias polymorphic |
| `tests/Modules/<Modul>/` | Test fitur per modul |
| `tests/Feature/Admin/` | Halaman lintas modul (laporan, pengaturan) |
| `tests/Support/` | Base class dan helper |

Test **tidak pernah menghubungi Xendit sungguhan**: semua panggilan HTTP disimulasikan
dan permintaan keluar yang tak terduga membuat test gagal.

---

## Struktur folder

```
app/
├── Modules/
│   ├── Shared/        # dasar modul, log aktivitas, pengaturan
│   ├── Identity/      # akun, role, undangan staf
│   ├── Catalog/       # paket voucher, katalog publik
│   ├── Inventory/     # stok kode, generate, import, reservasi
│   ├── Ordering/      # checkout, pesanan, email, laporan
│   └── Payment/       # Xendit: tagihan, webhook, sinkronisasi
├── Http/              # lapisan aplikasi (dashboard, laporan, pengaturan, log)
└── Providers/
resources/js/
├── Pages/<Modul>/     # halaman Inertia per modul
├── Components/        # komponen bersama
├── Layouts/           # AppLayout (publik), AdminLayout, GuestLayout
└── lib/               # helper format & grafik
tests/
```

Setiap modul berisi folder yang sama polanya: `Actions/`, `Contracts/`, `Data/`,
`Enums/`, `Events/`, `Models/`, `Http/`, `Database/Migrations/`, `routes.php`, dan
service provider-nya.

---

## Deploy ke server

Panduan lengkap konfigurasi server (Nginx, PHP-FPM, PostgreSQL, queue worker,
scheduler, SMTP, webhook Xendit, backup, dan keamanan) ada di:

**[docs/panduan-server.md](docs/panduan-server.md)**
