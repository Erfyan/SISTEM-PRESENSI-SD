# Sipreline — Sistem Presensi & Komunikasi Siswa SD

Sipreline adalah aplikasi web presensi dan komunikasi orang tua berbasis Progressive Web App (PWA) untuk Sekolah Dasar (SD). Aplikasi ini dirancang hemat biaya (tanpa API WhatsApp berbayar, SQLite default), tahan sinyal lemah (bisa isi absensi offline dengan IndexedDB + auto sync), cepat, dan mobile-first.

---

## 1. Persyaratan Sistem

- **Sistem Operasi**: Linux (Ubuntu 22.04 LTS direkomendasikan) / Windows Server (Laragon)
- **Web Server**: Nginx atau Caddy (dengan HTTPS/SSL aktif)
- **PHP**: Versi 8.2 atau lebih baru
  - Ekstensi PHP wajib: `pdo_sqlite`, `sqlite3`, `mbstring`, `fileinfo`, `gd`, `zip`, `openssl`, `curl`
- **Node.js**: Versi 20 LTS atau 22 LTS & npm
- **Database**: SQLite (default, WAL Mode aktif) atau MySQL/MariaDB

---

## 2. Cara Pemasangan (Instalasi Pertama Kali)

### Langkah A: Clone & Dependensi
```bash
# 1. Unduh repositori
cd /var/www
git clone <url-repo-sipreline> sipreline
cd sipreline

# 2. Pasang dependensi PHP
composer install --no-dev --optimize-autoloader

# 3. Pasang dependensi JavaScript & build aset frontend PWA
npm ci
npm run build
```

### Langkah B: Konfigurasi Lingkungan (`.env`)
```bash
# Salin konfigurasi sampel
cp .env.example .env

# Generate encryption key
php artisan key:generate
```

Pastikan variabel penting di `.env` disesuaikan:
```ini
APP_NAME="Sipreline"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://absensi.sdnegeri1.sch.id

# Konfigurasi SQLite Berkinerja Tinggi
DB_CONNECTION=sqlite
DB_BUSY_TIMEOUT=5000
DB_JOURNAL_MODE=WAL
DB_SYNCHRONOUS=NORMAL

# Keamanan Sesi Cookie
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_LIFETIME=120
```

### Langkah C: Database & Izin Folder
```bash
# Buat file database jika belum ada
touch database/database.sqlite

# Jalankan migrasi dan seeder awal
php artisan migrate --force --seed

# Berikan izin akses folder cache dan penyimpanan
chmod -R 775 storage bootstrap/cache database
chown -R www-data:www-data storage bootstrap/cache database
```

---

## 3. Konfigurasi HTTPS (Wajib untuk PWA & Keamanan)

PWA (Service Worker) dan cookie keamanan `Secure` **wajib** berjalan di atas protokol HTTPS.

### Pilihan A: Menggunakan Caddy (Paling Mudah, Otomatis SSL Let's Encrypt)
```caddy
absensi.sdnegeri1.sch.id {
    root * /var/www/sipreline/public
    php_fastcgi unix//run/php/php8.2-fpm.sock
    file_server

    header {
        X-Frame-Options "SAMEORIGIN"
        X-Content-Type-Options "nosniff"
        Referrer-Policy "strict-origin-when-cross-origin"
    }
}
```

### Pilihan B: Menggunakan Nginx + Certbot
```nginx
server {
    listen 80;
    server_name absensi.sdnegeri1.sch.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name absensi.sdnegeri1.sch.id;

    root /var/www/sipreline/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/absensi.sdnegeri1.sch.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/absensi.sdnegeri1.sch.id/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 4. Prosedur Pencadangan (Backup) Database

Sipreline telah dilengkapi perintah Artisan otomatis untuk mencadangkan database SQLite secara aman (menggunakan checkpoint WAL) dan menghapus cadangan yang lebih tua dari 14 hari.

### Pencadangan Manual:
```bash
php artisan app:backup-db
```
File cadangan akan disimpan di direktori `storage/app/backups/sipreline_backup_YYYY-MM-DD_His.sqlite`.

### Pencadangan Otomatis Terjadwal (Cron / Scheduler):
Tambahkan entri berikut ke crontab server (`crontab -e -u www-data`):
```cron
* * * * * cd /var/www/sipreline && php artisan schedule:run >> /dev/null 2>&1
```
*Scheduler Laravel otomatis menjalankan `app:backup-db --keep=14` setiap hari pada pukul 01:00 dini hari.*

---

## 5. Prosedur Pemulihan (Disaster Recovery)

Jika terjadi kendala data rusak atau server bermigrasi:

1. **Pulihkan dari file cadangan terbaru secara otomatis**:
   ```bash
   php artisan app:restore-db --latest --force
   ```
2. **Atau pulihkan file cadangan tertentu**:
   ```bash
   php artisan app:restore-db sipreline_backup_2026-05-01_010000.sqlite --force
   ```
3. Bersihkan cache aplikasi setelah pemulihan:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

*Catatan: Sistem secara otomatis membuat salinan darurat `pre_restore_*.sqlite` sebelum menimpa data.*

---

## 6. Prosedur Pembaruan Aplikasi (Update)

Langkah aman saat memperbarui versi kode aplikasi:

```bash
cd /var/www/sipreline

# 1. Aktifkan mode pemeliharaan
php artisan down --message="Sedang pembaruan sistem presensi..."

# 2. Cadangkan database saat ini
php artisan app:backup-db

# 3. Ambil kode terbaru
git pull origin main

# 4. Perbarui dependensi
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 5. Jalankan migrasi database
php artisan migrate --force

# 6. Bersihkan dan perbarui cache Laravel
php artisan optimize:clear
php artisan optimize

# 7. Matikan mode pemeliharaan
php artisan up
```

---

## 7. Matriks Penanggung Jawab Operasional (RACI)

| Peran | Pihak Bertanggung Jawab | Tugas Utama |
|---|---|---|
| **Admin IT / Operator Sekolah** | Guru TIK / Operator Dapodik | - Memantau halaman Status Sistem di menu admin.<br>- Memastikan SSL/HTTPS aktif dan sertifikat diperpanjang.<br>- Memverifikasi keberadaan file cadangan berkala di `storage/app/backups`.<br>- Mengelola akun guru dan kelas tiap tahun ajaran baru. |
| **Wali Kelas / Guru** | Seluruh Guru Kelas | - Mengisi absensi harian sebelum batas waktu (default 09:00).<br>- Menghubungi orang tua siswa yang tidak hadir via antrean WhatsApp.<br>- Menandai hari libur khusus kelas jika ada kegiatan sekolah. |
| **Kepala Sekolah** | Kepala Sekolah | - Memantau ringkasan persentase kehadiran bulanan dan rekap sekolah. |
| **Orang Tua / Wali Murid** | Orang Tua Siswa | - Mengakses Portal Orang Tua untuk memantau kehadiran anak.<br>- Mengganti PIN default pada saat login pertama kali. |
