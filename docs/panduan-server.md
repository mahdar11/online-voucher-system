# Panduan Konfigurasi Server

Panduan menyiapkan **Online Voucher System** di server produksi. Contoh perintah memakai
**Ubuntu 24.04 LTS**, tapi konsepnya sama untuk distribusi lain.

> **Catatan keamanan.** Semua kredensial (password database, secret key Xendit, password
> SMTP) diisi **langsung di server** pada file `.env`. Jangan pernah memasukkannya ke git
> atau membagikannya lewat chat.

---

## Daftar isi

1. [Yang harus hidup di server](#1-yang-harus-hidup-di-server)
2. [Spesifikasi server](#2-spesifikasi-server)
3. [Paket yang dipasang](#3-paket-yang-dipasang)
4. [Database PostgreSQL](#4-database-postgresql)
5. [Menaruh kode aplikasi](#5-menaruh-kode-aplikasi)
6. [File `.env` produksi](#6-file-env-produksi)
7. [Inisialisasi aplikasi](#7-inisialisasi-aplikasi)
8. [Nginx dan HTTPS](#8-nginx-dan-https)
9. [Queue worker (wajib)](#9-queue-worker-wajib)
10. [Scheduler (wajib)](#10-scheduler-wajib)
11. [SMTP dan DNS email](#11-smtp-dan-dns-email)
12. [Xendit produksi](#12-xendit-produksi)
13. [Backup database](#13-backup-database)
14. [Checklist keamanan](#14-checklist-keamanan)
15. [Uji setelah deploy](#15-uji-setelah-deploy)
16. [Deploy versi berikutnya](#16-deploy-versi-berikutnya)
17. [Masalah umum](#17-masalah-umum)

---

## 1. Yang harus hidup di server

Aplikasi ini **bukan** aplikasi web biasa yang cukup dijalankan Nginx. Ada lima bagian
yang semuanya harus berjalan:

| Bagian | Fungsi | Kalau mati |
|---|---|---|
| **Web (Nginx + PHP-FPM)** | Melayani halaman dan webhook | Situs tidak bisa dibuka |
| **PostgreSQL** | Semua data | Aplikasi error total |
| **Queue worker** | Mengirim email (voucher, undangan, riwayat) | Pembeli **tidak menerima kode voucher** walaupun sudah bayar |
| **Scheduler (cron)** | `orders:expire` tiap menit | Voucher tidak pernah kembali ke stok setelah pesanan kedaluwarsa |
| **SMTP** | Jalur keluar email | Semua email gagal terkirim |

> **Karena itu shared hosting / cPanel biasa tidak cocok**: umumnya tidak mengizinkan
> proses latar (queue worker) dan cron per menit.

---

## 2. Spesifikasi server

| Skala | CPU | RAM | Disk |
|---|---|---|---|
| Awal (< 500 pesanan/hari) | 1–2 vCPU | 2 GB | 25 GB SSD |
| Menengah | 2–4 vCPU | 4 GB | 50 GB SSD |

Yang dibutuhkan: akses root/sudo, IP publik, dan domain yang diarahkan ke IP itu.

---

## 3. Paket yang dipasang

```bash
sudo apt update && sudo apt install -y nginx postgresql postgresql-contrib supervisor git unzip curl certbot python3-certbot-nginx
```

```bash
sudo apt install -y php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl
```

Sesuaikan batas upload PHP agar import CSV voucher (maksimal 2 MB) tidak tertolak.
Di `/etc/php/8.3/fpm/php.ini`:

```ini
upload_max_filesize = 8M
post_max_size = 8M
memory_limit = 256M
```

```bash
sudo systemctl restart php8.3-fpm
```

> Nilai bawaan Ubuntu (`upload_max_filesize = 2M`) terlalu mepet: file CSV berukuran
> tepat 2 MB akan ditolak sebelum sampai ke aplikasi.

Composer:

```bash
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

Node.js 22 (dipakai untuk membangun aset frontend):

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```

> **Kenapa Node perlu di server?** Folder hasil build (`public/build`) sengaja tidak
> ikut masuk git. Jadi aset harus dibangun di server dengan `npm run build`, atau
> dibangun di CI lalu disalin ke server. Kalau Anda memilih cara CI, Node tidak perlu
> dipasang di server.

Cek versi:

```bash
php -v && composer -V && node -v && psql --version
```

---

## 4. Database PostgreSQL

Buat user dan database khusus aplikasi (ganti `PASSWORD_KUAT_DI_SINI`):

```bash
sudo -u postgres psql -c "CREATE USER voucher WITH PASSWORD 'PASSWORD_KUAT_DI_SINI';"
```

```bash
sudo -u postgres psql -c "CREATE DATABASE voucher_production OWNER voucher;"
```

Pastikan PostgreSQL **hanya mendengarkan localhost** (default Ubuntu sudah begitu).
Periksa di `/etc/postgresql/16/main/postgresql.conf`:

```
listen_addresses = 'localhost'
```

> **Zona waktu database tidak perlu diubah.** Aplikasi menyimpan waktu dalam UTC dan
> mengonversinya sendiri memakai `BUSINESS_TIMEZONE`.

---

## 5. Menaruh kode aplikasi

Buat user khusus (jangan jalankan aplikasi sebagai root):

```bash
sudo adduser --disabled-password --gecos "" deploy && sudo usermod -aG www-data deploy
```

Ambil kode:

```bash
sudo mkdir -p /var/www/voucher && sudo chown deploy:www-data /var/www/voucher
```

```bash
sudo -u deploy git clone https://github.com/mahdar11/online-voucher-system.git /var/www/voucher
```

Pasang dependensi (sebagai user `deploy`, di `/var/www/voucher`):

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

```bash
npm ci && npm run build
```

Atur kepemilikan dan izin folder:

```bash
sudo chown -R deploy:www-data /var/www/voucher && sudo chmod -R 775 /var/www/voucher/storage /var/www/voucher/bootstrap/cache
```

---

## 6. File `.env` produksi

```bash
sudo -u deploy cp /var/www/voucher/.env.example /var/www/voucher/.env
```

Lindungi filenya agar tidak terbaca user lain:

```bash
sudo chmod 640 /var/www/voucher/.env && sudo chown deploy:www-data /var/www/voucher/.env
```

### Isi yang harus disesuaikan

| Variabel | Nilai produksi | Catatan |
|---|---|---|
| `APP_NAME` | `"Nama Toko Anda"` | Tampil di situs dan email |
| `APP_ENV` | `production` | **Wajib.** Menonaktifkan akun demo |
| `APP_DEBUG` | `false` | **Wajib.** Kalau `true`, pesan error membocorkan konfigurasi |
| `APP_KEY` | *(dibuat di langkah 7)* | Jangan pernah diganti setelah ada data |
| `APP_URL` | `https://voucher.domainanda.id` | Dipakai untuk link di email |
| `APP_LOCALE` | `id` | |
| `BUSINESS_TIMEZONE` | `Asia/Jakarta` | Batas hari di laporan |
| `DB_CONNECTION` | `pgsql` | |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `5432` | |
| `DB_DATABASE` | `voucher_production` | |
| `DB_USERNAME` / `DB_PASSWORD` | `voucher` / *(rahasia)* | Dari langkah 4 |
| `SESSION_DRIVER` | `database` | |
| `SESSION_SECURE_COOKIE` | `true` | **Wajib.** Cookie hanya lewat HTTPS |
| `SESSION_DOMAIN` | `voucher.domainanda.id` | |
| `QUEUE_CONNECTION` | `database` | |
| `CACHE_STORE` | `database` | |
| `LOG_CHANNEL` | `daily` | Log dipecah per hari |
| `LOG_LEVEL` | `warning` | Hindari `debug` di produksi |
| `MAIL_*` | *(lihat bagian 11)* | |
| `XENDIT_SECRET_KEY` | `xnd_production_…` | **Rahasia** |
| `XENDIT_CALLBACK_TOKEN` | *(dari dashboard)* | **Rahasia** |
| `SUPER_ADMIN_NAME` / `_EMAIL` / `_PASSWORD` | akun admin pertama | Password kuat; boleh dikosongkan lagi setelah seeding |
| `DEMO_ADMIN_*` / `DEMO_CUSTOMER_*` | **kosongkan** | Akun demo tidak pernah dibuat saat `APP_ENV=production`, tapi lebih aman dikosongkan |

---

## 7. Inisialisasi aplikasi

Jalankan sebagai user `deploy` di `/var/www/voucher`:

```bash
php artisan key:generate
```

```bash
php artisan migrate --force
```

```bash
php artisan db:seed --force
```

Seeder membuat role, permission, dan **satu akun super admin** dari `SUPER_ADMIN_*`.
Akun demo tidak dibuat karena `APP_ENV=production`.

Cache konfigurasi (mempercepat aplikasi):

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

> Setiap kali `.env` diubah, jalankan ulang `php artisan config:cache`, kalau tidak
> perubahan tidak terbaca.

---

## 8. Nginx dan HTTPS

Buat `/etc/nginx/sites-available/voucher`:

```nginx
server {
    listen 80;
    server_name voucher.domainanda.id;
    root /var/www/voucher/public;

    index index.php;
    charset utf-8;

    # Keamanan dasar
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    client_max_body_size 8M;          # cukup untuk import CSV voucher (maks 2 MB)

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    # Aset hasil build boleh di-cache lama (namanya mengandung hash)
    location /build/ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.(?!well-known).* { deny all; }   # blokir .env, .git, dll

    error_page 404 /index.php;
}
```

Aktifkan dan pasang sertifikat HTTPS:

```bash
sudo ln -s /etc/nginx/sites-available/voucher /etc/nginx/sites-enabled/ && sudo nginx -t && sudo systemctl reload nginx
```

```bash
sudo certbot --nginx -d voucher.domainanda.id
```

Certbot otomatis menambahkan blok HTTPS dan pengalihan dari HTTP. Perpanjangan
sertifikat berjalan otomatis.

> **Kalau memakai Cloudflare atau load balancer**, aplikasi perlu tahu IP asli pengunjung.
> Sampaikan ke saya kalau ini dipakai, karena perlu pengaturan *trusted proxy* di kode.

---

## 9. Queue worker (wajib)

Tanpa ini, **email kode voucher tidak akan pernah terkirim**.

Buat `/etc/supervisor/conf.d/voucher-worker.conf`:

```ini
[program:voucher-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/voucher/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
directory=/var/www/voucher
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/voucher/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start voucher-worker:*
```

Cek statusnya:

```bash
sudo supervisorctl status voucher-worker:*
```

> Setiap selesai deploy versi baru, jalankan `php artisan queue:restart` supaya worker
> memuat kode terbaru.

---

## 10. Scheduler (wajib)

Tanpa ini, voucher pada pesanan yang kedaluwarsa **tidak pernah kembali ke stok**.

```bash
sudo crontab -u deploy -e
```

Tambahkan satu baris:

```cron
* * * * * cd /var/www/voucher && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menjalankan `orders:expire` setiap menit. Verifikasi jadwalnya:

```bash
php artisan schedule:list
```

---

## 11. SMTP dan DNS email

### Pengaturan di `.env`

Contoh untuk **Brevo** (gratis 300 email/hari); provider lain mirip:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=xxx
MAIL_PASSWORD=xxx
MAIL_SCHEME=null
MAIL_FROM_ADDRESS="noreply@domainanda.id"
MAIL_FROM_NAME="${APP_NAME}"
```

> **Tentang `MAIL_SCHEME`.** Nilainya hanya boleh `smtp`, `smtps`, atau dibiarkan
> `null`. Kalau dibiarkan `null`, Laravel memilih sendiri: port `465` memakai
> `smtps` (TLS langsung), port lain memakai `smtp` dengan STARTTLS. Jadi untuk port
> `587` biarkan saja `null`. Mengisinya dengan `tls` akan membuat pengiriman gagal.

> `MAIL_FROM_ADDRESS` harus memakai domain yang Anda kuasai dan sudah diverifikasi di
> provider. Memakai alamat Gmail/Yahoo di sini membuat email ditolak atau masuk spam.

### Catatan DNS (penting agar email tidak masuk spam)

Tambahkan di DNS domain Anda, sesuai instruksi provider SMTP:

| Record | Fungsi |
|---|---|
| **SPF** (TXT) | Menyatakan server mana yang boleh mengirim atas nama domain Anda |
| **DKIM** (TXT/CNAME) | Tanda tangan digital pada setiap email |
| **DMARC** (TXT) | Kebijakan bila SPF/DKIM gagal, contoh: `v=DMARC1; p=quarantine; rua=mailto:dmarc@domainanda.id` |

Setelah dipasang, uji kualitas pengiriman di https://www.mail-tester.com (kirim satu
email dari aplikasi ke alamat yang diberikan situs itu).

---

## 12. Xendit produksi

1. Di dashboard Xendit, **matikan Test Mode**.
2. **Settings → API Keys →** buat secret key baru dengan izin **Write** untuk
   *Money-in*. Salin ke `XENDIT_SECRET_KEY`.
3. **Settings → Webhooks**, isi URL callback untuk *Invoices paid*:

   ```
   https://voucher.domainanda.id/webhooks/xendit/invoice
   ```

4. Salin **verification token** ke `XENDIT_CALLBACK_TOKEN`.
5. Jalankan `php artisan config:cache`, lalu lakukan **satu transaksi kecil sungguhan**
   untuk memastikan semuanya tersambung.

> Webhook sudah diverifikasi dengan token, dikecualikan dari CSRF, dan aman menerima
> panggilan berulang. Aktifkan metode pembayaran yang diinginkan (QRIS, VA, e-wallet)
> dari dashboard Xendit; kode aplikasi tidak membatasinya.

---

## 13. Backup database

Buat `/usr/local/bin/backup-voucher.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail
DIR=/var/backups/voucher
mkdir -p "$DIR"
PGPASSWORD="$DB_PASSWORD" pg_dump -h 127.0.0.1 -U voucher voucher_production \
  | gzip > "$DIR/voucher-$(date +%F-%H%M).sql.gz"
find "$DIR" -name 'voucher-*.sql.gz' -mtime +14 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-voucher.sh
```

Jadwalkan tiap hari pukul 02.00, dengan password dibaca dari `.env`:

```cron
0 2 * * * set -a && . /var/www/voucher/.env && set +a && /usr/local/bin/backup-voucher.sh
```

**Salin backup ke luar server** (S3, Google Drive, atau komputer lain). Backup yang hanya
ada di server yang sama tidak menolong kalau servernya bermasalah.

Uji pemulihan sesekali:

```bash
gunzip -c /var/backups/voucher/voucher-XXXX.sql.gz | psql -h 127.0.0.1 -U voucher -d voucher_uji
```

---

## 14. Checklist keamanan

- [ ] `APP_DEBUG=false` dan `APP_ENV=production`
- [ ] HTTPS aktif, HTTP dialihkan ke HTTPS
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] File `.env` ber-mode `640` dan tidak pernah masuk git
- [ ] Password database kuat, PostgreSQL hanya mendengarkan `localhost`
- [ ] Firewall: hanya port 22, 80, dan 443 terbuka
      (`sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable`)
- [ ] Login SSH memakai kunci, bukan password; root login dimatikan
- [ ] Aplikasi berjalan sebagai user `deploy`, bukan root
- [ ] Akun demo kosong di `.env`
- [ ] Password akun super admin pertama sudah diganti dari nilai sementara
- [ ] `XENDIT_CALLBACK_TOKEN` terisi, sehingga webhook palsu ditolak
- [ ] Pembaruan keamanan sistem rutin (`sudo apt update && sudo apt upgrade`)

---

## 15. Uji setelah deploy

Lakukan berurutan setelah server siap:

1. **Situs terbuka** di `https://voucher.domainanda.id` dan katalog tampil.
2. **Login super admin** berhasil di `/login`.
3. **Buat paket** lalu **generate beberapa voucher** dari halaman admin.
4. **Checkout sebagai pembeli** dengan email asli milik Anda.
5. **Email "Selesaikan pembayaran" masuk** (cek juga folder spam).
   → Kalau tidak masuk: queue worker atau SMTP bermasalah.
6. **Bayar** dengan nominal kecil sungguhan.
7. **Pesanan menjadi Lunas** dan kode voucher tampil.
8. **Email berisi kode voucher masuk**, status pesanan menjadi **Selesai**.
9. **Buat satu pesanan lagi lalu biarkan**. Setelah lewat batas waktu, statusnya menjadi
   **Kedaluwarsa** dan stok kembali.
   → Kalau tidak: scheduler belum jalan.
10. **Halaman Laporan** menampilkan transaksi tadi.
11. **Coba kode voucher** di hotspot untuk memastikan cocok dengan perangkat Anda.

---

## 16. Deploy versi berikutnya

Urutan aman untuk memperbarui aplikasi:

```bash
cd /var/www/voucher && php artisan down --render="errors::503"
```

```bash
git pull && composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build
```

```bash
php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

```bash
php artisan queue:restart && php artisan up
```

> Di tahap berikutnya, semua langkah ini akan saya bungkus menjadi satu skrip
> `deploy.sh` beserta file konfigurasi siap pakai di folder `deploy/`.

---

## 17. Masalah umum

| Gejala | Penyebab yang paling sering | Cara periksa |
|---|---|---|
| Email tidak terkirim | Queue worker mati | `sudo supervisorctl status`; cek `storage/logs/worker.log` |
| Pesanan kedaluwarsa tapi stok tidak kembali | Cron scheduler belum jalan | `php artisan schedule:list`; jalankan `php artisan orders:expire` manual |
| Pembayaran lunas di Xendit tapi pesanan tetap *Menunggu* | URL webhook salah atau token tidak cocok | Cek log webhook di dashboard Xendit; cek `XENDIT_CALLBACK_TOKEN` |
| Tombol bayar error "Gagal membuat tagihan" | Secret key salah atau tanpa izin *Money-in: Write* | `storage/logs/laravel.log`, cari `REQUEST_FORBIDDEN_ERROR` |
| Halaman tanpa gaya / tombol tidak berfungsi | `npm run build` belum dijalankan di server | Pastikan folder `public/build` ada dan terisi |
| Error 500 setelah mengubah `.env` | Cache konfigurasi lama | `php artisan config:cache` |
| "Permission denied" saat menulis log | Izin folder | `sudo chown -R deploy:www-data storage bootstrap/cache && sudo chmod -R 775 storage bootstrap/cache` |
| Halaman admin menolak akses padahal role benar | Cache permission Spatie | `php artisan permission:cache-reset` |

Log utama ada di `/var/www/voucher/storage/logs/laravel.log`.
