# Sesi 11: Deploy GajiPro ke Shared Hosting Hostinger

## Tujuan

Deploy project Laravel GajiPro ke shared hosting Hostinger menggunakan subdomain dari `jagofullstack.com`, lengkap dari beli hosting sampai web running production.

---

## Kompatibilitas Paket Hosting & Terminal

### Apakah Hostinger Support Terminal?

| Paket | SSH/Terminal | Git di hPanel | Harga (est.) |
|-------|-------------|---------------|--------------|
| **Single** | **TIDAK** | **TIDAK** | ~Rp 13.900/bln |
| **Premium** | **YA** (SSH + Browser Terminal) | **YA** | ~Rp 24.900/bln |
| **Business** | **YA** (SSH + Browser Terminal) | **YA** | ~Rp 44.900/bln |
| **Cloud** | **YA** (SSH + full access) | **YA** | ~Rp 89.900/bln |

### Dua Jalur Deploy

Materi ini menyediakan **2 jalur**:

| | Jalur A: Dengan Terminal | Jalur B: Tanpa Terminal |
|--|--------------------------|-------------------------|
| **Paket** | Premium / Business / Cloud | Single (atau SSH bermasalah) |
| **Cara upload** | Git clone via SSH | ZIP upload via File Manager / FTP |
| **Install composer** | `composer install` via terminal | Install di local, upload folder `vendor/` |
| **Artisan commands** | Via SSH terminal | Via Hostinger Cron Job (one-time trick) |
| **Tingkat kesulitan** | Mudah | Agak ribet tapi **tetap bisa** |
| **Recommended?** | **YA** | Darurat / budget ketat |

> **Rekomendasi**: Gunakan minimal paket **Premium** untuk pengalaman deploy yang lancar. Tapi jika terpaksa pakai Single, ikuti **Jalur B**.

---

## Daftar Isi

1. [Beli Paket Shared Hosting](#1-beli-paket-shared-hosting)
2. [Setup Subdomain](#2-setup-subdomain)
3. [Setup Database MySQL](#3-setup-database-mysql)
4. [Upload Project ke Hosting](#4-upload-project-ke-hosting)
5. [Setup Document Root](#5-setup-document-root)
6. [Install Composer Dependencies](#6-install-composer-dependencies)
7. [Konfigurasi Environment (.env)](#7-konfigurasi-environment-env)
8. [Build & Upload Frontend Assets](#8-build--upload-frontend-assets)
9. [Jalankan Migration & Seeder](#9-jalankan-migration--seeder)
10. [Set Permission & Storage Link](#10-set-permission--storage-link)
11. [Cache & Optimize](#11-cache--optimize)
12. [Setup Cron Job](#12-setup-cron-job)
13. [Setup SSL (HTTPS)](#13-setup-ssl-https)
14. [Testing & Verifikasi](#14-testing--verifikasi)
15. [Troubleshooting](#15-troubleshooting)
16. [Update / Redeploy](#16-update--redeploy)

---

## Prasyarat

- Akun Hostinger (bisa pakai akun existing yang sudah punya domain `jagofullstack.com`)
- Repository Git (GitHub/GitLab) berisi project GajiPro
- Project sudah tested di local dan siap production

### Spesifikasi Project

| Requirement | Versi |
|-------------|-------|
| PHP | >= 8.2 (project pakai 8.3) |
| MySQL | 5.7+ atau 8.0 |
| Node.js | >= 18 (untuk build assets — di local saja) |
| Composer | 2.x |

---

## 1. Beli Paket Shared Hosting

### Pilih Paket yang Tepat

Buka [hostinger.co.id](https://www.hostinger.co.id/web-hosting) dan pilih paket:

| Paket | Cocok? | Alasan |
|-------|--------|--------|
| Single | ⚠️ | Bisa tapi **tidak ada SSH/Terminal** — deploy lebih ribet |
| **Premium** | ✅ | **Recommended** — SSH, Browser Terminal, Git, 100 website |
| Business | ✅ | Lebih banyak resource, staging tool |

> **PENTING**: Fitur yang dibutuhkan per paket:
>
> | Fitur | Single | Premium+ |
> |-------|--------|----------|
> | PHP 8.2+ | ✅ | ✅ |
> | MySQL Database | ✅ | ✅ |
> | Cron Jobs | ✅ | ✅ |
> | File Manager | ✅ | ✅ |
> | FTP Access | ✅ | ✅ |
> | **SSH Access** | ❌ | ✅ |
> | **Browser Terminal** | ❌ | ✅ |
> | **Git di hPanel** | ❌ | ✅ |

### Langkah Pembelian

1. Login ke [Hostinger](https://www.hostinger.co.id)
2. Klik **Hosting** → **Web Hosting**
3. Pilih paket **Premium** atau **Business**
4. Pilih periode (12 bulan biasanya paling worth it)
5. Jika sudah punya akun & domain `jagofullstack.com`, pilih **"I already have a domain"**
6. Selesaikan pembayaran

### Setelah Beli

Tunggu provisioning selesai (biasanya 1-5 menit), lalu masuk ke **hPanel** (Hostinger Panel).

---

## 2. Setup Subdomain

Kita akan deploy di subdomain: `gajipro.jagofullstack.com`

### Via hPanel

1. Login ke **hPanel** → [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Pilih hosting plan yang aktif
3. Menu: **Domains** → **Subdomains**
4. Isi form:
   - **Subdomain**: `gajipro`
   - **Domain**: pilih `jagofullstack.com`
   - **Document Root**: biarkan default dulu (akan kita ubah nanti)
     - Default biasanya: `domains/gajipro.jagofullstack.com/public_html`
5. Klik **Create**

### Verifikasi DNS

Subdomain otomatis mendapat DNS record. Cek di:
- **Domains** → **DNS / Nameservers**
- Pastikan ada A record untuk `gajipro.jagofullstack.com` mengarah ke IP hosting

> DNS propagation bisa memakan waktu 5-30 menit untuk subdomain di Hostinger yang sama.

---

## 3. Setup Database MySQL

### Buat Database

1. Di hPanel → **Databases** → **MySQL Databases**
2. Isi form:
   - **Database name**: `gajipro_db` (akan otomatis di-prefix, misal: `u123456789_gajipro_db`)
   - **Username**: `gajipro_user` (akan otomatis di-prefix)
   - **Password**: buat password yang kuat (simpan baik-baik!)
3. Klik **Create**

### Catat Informasi Database

```
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_gajipro_db
DB_USERNAME=u123456789_gajipro_user
DB_PASSWORD=password_yang_dibuat
```

> **TIP**: Username dan database name di Hostinger akan di-prefix dengan ID akun (contoh: `u123456789_`). Catat nama lengkapnya dari panel.

---

## 4. Upload Project ke Hosting

### Jalur A: Via Terminal / SSH (Premium+)

#### A1. Akses Terminal

Ada 2 cara akses terminal:

**Cara 1 — Browser Terminal (paling mudah, tanpa install apapun):**
1. Di hPanel → **Advanced** → **Terminal**
2. Klik **Start** — terminal langsung terbuka di browser
3. Sudah auto-login, tidak perlu password

**Cara 2 — SSH dari komputer lokal:**
1. Di hPanel → **Advanced** → **SSH Access**
2. Catat informasi SSH:
   ```
   Host: ssh.jagofullstack.com (atau IP yang diberikan)
   Port: 65002 (Hostinger pakai port non-standar)
   Username: u123456789
   Password: password hPanel
   ```
3. Buka terminal lokal:
   ```bash
   ssh u123456789@ssh.jagofullstack.com -p 65002
   ```

#### A2. Verifikasi Tools

```bash
php -v          # Expected: PHP 8.2+ atau 8.3
composer -V     # Expected: Composer 2.x
git --version   # Expected: git 2.x
```

#### A3. Setup PHP Version

Jika PHP default bukan 8.2+:
1. hPanel → **Advanced** → **PHP Configuration**
2. Pilih **PHP 8.2** atau **PHP 8.3**
3. Klik **Save**

#### A4. Setup SSH Key untuk GitHub (Opsional tapi Recommended)

```bash
ssh-keygen -t ed25519 -C "hosting-gajipro"
# Tekan Enter untuk semua prompt (tanpa passphrase)

cat ~/.ssh/id_ed25519.pub
```

Copy output key, lalu tambahkan di GitHub:
1. GitHub → **Settings** → **SSH and GPG keys** → **New SSH key**
2. Paste key, beri judul "Hostinger GajiPro"

Verifikasi:
```bash
ssh -T git@github.com
# Output: Hi username! You've successfully authenticated...
```

#### A5. Clone Repository

```bash
cd ~/domains/gajipro.jagofullstack.com

# Clone via SSH
git clone git@github.com:USERNAME/ultimate-jagogaji-system.git .

# ATAU via HTTPS (pakai personal access token)
git clone https://github.com/USERNAME/ultimate-jagogaji-system.git .
```

> **PENTING**: Titik (`.`) di akhir berarti clone ke folder saat ini, bukan buat subfolder.

```bash
# Checkout branch yang benar
git checkout main
# atau
git checkout single-company
```

---

### Jalur B: Tanpa Terminal / SSH (Paket Single atau SSH Bermasalah)

Jika tidak punya akses terminal, kita persiapkan **semua** di komputer lokal dulu, lalu upload.

#### B1. Persiapan di Komputer Lokal

```bash
# Masuk ke project directory
cd /Users/bahri/development/AcademyJF/ultimate-jagogaji-system

# Install composer dependencies (production mode)
composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend assets
npm install
npm run build
```

Sekarang folder project sudah berisi:
- `vendor/` (dari composer install)
- `public/build/` (dari npm run build)

#### B2. Buat File .env untuk Production

```bash
cp .env.example .env.production
```

Edit `.env.production` di text editor (VS Code, dll):

```env
APP_NAME=GajiPro
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://gajipro.jagofullstack.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_gajipro_db
DB_USERNAME=u123456789_gajipro_user
DB_PASSWORD=password_yang_dibuat

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@jagofullstack.com
MAIL_PASSWORD=password_email
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@jagofullstack.com
MAIL_FROM_NAME="GajiPro"
```

> **APP_KEY** biarkan kosong dulu — kita generate nanti di hosting.

#### B3. Buat ZIP untuk Upload

```bash
cd /Users/bahri/development/AcademyJF/ultimate-jagogaji-system

# Buat ZIP (tanpa node_modules, .git, dan file dev)
zip -r gajipro-deploy.zip . \
  -x "node_modules/*" \
  -x ".git/*" \
  -x "tests/*" \
  -x ".env" \
  -x ".env.example" \
  -x "*.md" \
  -x "phpunit.xml" \
  -x ".github/*"
```

> File ZIP biasanya sekitar 30-80 MB (vendor/ yang besar).

#### B4. Upload via hPanel File Manager

1. Di hPanel → **Files** → **File Manager**
2. Navigate ke: `domains/gajipro.jagofullstack.com/`
3. Klik **Upload** → pilih file `gajipro-deploy.zip`
4. Tunggu upload selesai (tergantung kecepatan internet)
5. Klik kanan pada file ZIP → **Extract**
6. Pilih extract ke folder saat ini (`domains/gajipro.jagofullstack.com/`)
7. Hapus file ZIP setelah extract (hemat disk space)

#### B5. Upload via FTP (Alternatif — untuk file besar)

Jika File Manager lambat atau timeout, gunakan FTP:

1. Di hPanel → **Files** → **FTP Accounts**
2. Catat info FTP:
   ```
   Host: ftp.jagofullstack.com (atau dari panel)
   Port: 21
   Username: u123456789
   Password: password FTP
   ```
3. Download **FileZilla** (gratis): [filezilla-project.org](https://filezilla-project.org)
4. Connect ke FTP → navigate ke `domains/gajipro.jagofullstack.com/`
5. Upload **semua file** dari project lokal (kecuali `node_modules/`, `.git/`)

> **FTP Tips:**
> - Transfer mode: Binary (bukan ASCII)
> - Upload bisa memakan waktu 15-45 menit tergantung koneksi
> - Pastikan folder `vendor/` ter-upload lengkap

#### B6. Rename .env.production ke .env

Setelah upload:
1. Di File Manager → navigate ke project root
2. Cari file `.env.production` (atau `.env.example` jika belum buat)
3. Rename menjadi `.env`
4. Edit isi `.env` via File Manager (klik kanan → Edit) — sesuaikan nilai DB, dll

---

## 5. Setup Document Root

### Masalah: Document Root vs Laravel Structure

Shared hosting mengarahkan domain ke folder `public_html`, sedangkan Laravel memerlukan folder `public/` sebagai document root.

### Solusi 1: Ubah Document Root di hPanel (RECOMMENDED)

1. Di hPanel → **Websites** → klik subdomain `gajipro.jagofullstack.com`
2. Atau: **Domains** → **Subdomains** → klik ikon pensil (edit)
3. Ubah **Document Root** menjadi:
   ```
   domains/gajipro.jagofullstack.com/public
   ```
4. Klik **Save**

### Solusi 2: Pindah isi `public/` ke `public_html/` + Edit `index.php`

Jika tidak bisa ubah document root (paket Single):

**Langkah via File Manager:**

1. **Pindahkan semua file** dari `public/` ke `public_html/`:
   - Buka File Manager
   - Masuk ke `domains/gajipro.jagofullstack.com/public/`
   - Select all files → Cut
   - Navigate ke `domains/gajipro.jagofullstack.com/public_html/`
   - Paste

2. **Edit `public_html/index.php`** — ubah path bootstrap:
   
   Cari baris:
   ```php
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```
   
   **Jika project ada di level atas** (`domains/gajipro.jagofullstack.com/`):
   
   Ubah menjadi (sesuaikan path relatif):
   ```php
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```
   
   Path `../` harus mengarah dari `public_html/` ke folder project root. Jika `public_html/` dan folder project (`app/`, `vendor/`, dll) berada di level yang sama di dalam `domains/gajipro.jagofullstack.com/`, maka `../` sudah benar.

3. **Edit `.htaccess` di `public_html/`** — pastikan isinya sama dengan file `public/.htaccess` asli dari Laravel.

### Solusi 3: Symlink (Via Terminal — paket Premium+)

```bash
cd ~/domains/gajipro.jagofullstack.com

# Hapus/rename public_html default
mv public_html public_html_backup

# Buat symlink
ln -s ~/domains/gajipro.jagofullstack.com/public public_html
```

---

## 6. Install Composer Dependencies

### Jalur A: Via Terminal (Premium+)

```bash
cd ~/domains/gajipro.jagofullstack.com

# Install tanpa dev dependencies
composer install --no-dev --optimize-autoloader --no-interaction
```

> **Jika kehabisan memory:**
> ```bash
> php -d memory_limit=-1 $(which composer) install --no-dev --optimize-autoloader --no-interaction
> ```

> **Jika composer tidak tersedia:**
> ```bash
> curl -sS https://getcomposer.org/installer | php
> php composer.phar install --no-dev --optimize-autoloader --no-interaction
> ```

### Jalur B: Tanpa Terminal

**Sudah dilakukan di Step 4 (Jalur B)** — folder `vendor/` sudah ter-upload dari lokal.

Pastikan folder `vendor/` ada dan lengkap:
1. File Manager → navigate ke `domains/gajipro.jagofullstack.com/`
2. Cek folder `vendor/` exists dan berisi banyak subfolder
3. Cek file `vendor/autoload.php` ada

> **PENTING**: Versi PHP di hosting **harus sama atau lebih tinggi** dari PHP di lokal.
> Jika lokal pakai PHP 8.3, hosting juga harus PHP 8.3.
> Set di: hPanel → **Advanced** → **PHP Configuration**

---

## 7. Konfigurasi Environment (.env)

### Jalur A: Via Terminal (Premium+)

```bash
cd ~/domains/gajipro.jagofullstack.com

# Copy template
cp .env.example .env

# Edit
nano .env
```

Ubah konfigurasi berikut:

```env
APP_NAME=GajiPro
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://gajipro.jagofullstack.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=en

# Database (sesuaikan dengan info dari Step 3)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_gajipro_db
DB_USERNAME=u123456789_gajipro_user
DB_PASSWORD=password_yang_dibuat

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Filesystem
FILESYSTEM_DISK=local

# Mail (konfigurasi sesuai kebutuhan)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@jagofullstack.com
MAIL_PASSWORD=password_email
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@jagofullstack.com
MAIL_FROM_NAME="GajiPro"
```

Simpan: `Ctrl+O` → Enter → `Ctrl+X`

#### Generate App Key

```bash
php artisan key:generate
```

### Jalur B: Tanpa Terminal

**Jika sudah buat `.env` di Step 4 (Jalur B):**
- Pastikan file `.env` sudah ada di root project (via File Manager → cek exists)
- Edit via File Manager (klik kanan → Edit) jika perlu perbaikan

**Generate APP_KEY tanpa terminal:**

1. Di komputer **lokal**, generate key:
   ```bash
   cd /Users/bahri/development/AcademyJF/ultimate-jagogaji-system
   php artisan key:generate --show
   # Output: base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
   ```
2. Copy output key tersebut
3. Di File Manager hosting → edit `.env` → paste di baris `APP_KEY=`:
   ```env
   APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
   ```
4. Save file

---

## 8. Build & Upload Frontend Assets

### Selalu Build di Lokal (Berlaku untuk Semua Jalur)

Shared hosting punya keterbatasan memory untuk `npm run build`. Selalu build di lokal:

```bash
# Di komputer LOCAL
cd /Users/bahri/development/AcademyJF/ultimate-jagogaji-system

npm install
npm run build

# Verifikasi
ls -la public/build/
# Harus ada: manifest.json dan folder assets/
```

### Upload Hasil Build

**Jalur A — Via SCP (Terminal tersedia):**
```bash
scp -P 65002 -r public/build/ u123456789@ssh.jagofullstack.com:~/domains/gajipro.jagofullstack.com/public/build/
```

**Jalur B — Via File Manager:**
1. Di lokal, zip folder build:
   ```bash
   cd public
   zip -r build.zip build/
   ```
2. Di hPanel File Manager → navigate ke `domains/gajipro.jagofullstack.com/public/`
3. Upload file `build.zip`
4. Klik kanan → **Extract**
5. Hapus `build.zip` setelah extract

**Jalur B — Via FTP (FileZilla):**
1. Connect FTP
2. Navigate ke `domains/gajipro.jagofullstack.com/public/`
3. Upload folder `build/` dari `public/build/` lokal

---

## 9. Jalankan Migration & Seeder

### Jalur A: Via Terminal (Premium+)

```bash
cd ~/domains/gajipro.jagofullstack.com

# Jalankan migration
php artisan migrate --force

# Jalankan seeder untuk data awal
php artisan db:seed --force

# Atau seeder spesifik
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=SuperadminSeeder --force

# Verifikasi
php artisan migrate:status
```

> Flag `--force` diperlukan karena APP_ENV=production.

### Jalur B: Tanpa Terminal — Trik Cron Job

Karena tidak bisa jalankan `php artisan` langsung, kita gunakan **Cron Job sebagai pengganti terminal**:

#### B1. Buat Script PHP Helper

Di File Manager, buat file baru di root project:

**File: `domains/gajipro.jagofullstack.com/artisan-web.php`**

```php
<?php
/**
 * Helper untuk jalankan artisan commands tanpa terminal.
 * HAPUS FILE INI SETELAH SETUP SELESAI! (security risk)
 */

// Security: hanya bisa diakses dengan secret key
$secret = 'GANTI_DENGAN_SECRET_KEY_RANDOM_PANJANG_123';

if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(404);
    exit('Not found');
}

$command = $_GET['cmd'] ?? '';

$allowedCommands = [
    'migrate' => 'migrate --force',
    'seed' => 'db:seed --force',
    'key' => 'key:generate',
    'storage' => 'storage:link',
    'config-cache' => 'config:cache',
    'route-cache' => 'route:cache',
    'view-cache' => 'view:cache',
    'config-clear' => 'config:clear',
    'route-clear' => 'route:clear',
    'view-clear' => 'view:clear',
    'cache-clear' => 'cache:clear',
    'optimize' => 'optimize',
    'swagger' => 'l5-swagger:generate',
    'migrate-status' => 'migrate:status',
];

if (!isset($allowedCommands[$command])) {
    echo "<h2>Available Commands:</h2><ul>";
    foreach ($allowedCommands as $key => $val) {
        echo "<li><a href='?key={$secret}&cmd={$key}'>{$key}</a> → php artisan {$val}</li>";
    }
    echo "</ul>";
    echo "<p style='color:red;'><strong>HAPUS FILE INI SETELAH SETUP SELESAI!</strong></p>";
    exit;
}

// Execute
$artisanCmd = $allowedCommands[$command];
$fullPath = __DIR__;

chdir($fullPath);
$output = [];
$exitCode = 0;
exec("cd {$fullPath} && php artisan {$artisanCmd} 2>&1", $output, $exitCode);

echo "<h2>php artisan {$artisanCmd}</h2>";
echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
echo "<p>Exit code: {$exitCode}</p>";
echo "<p style='color:red;'><strong>HAPUS FILE INI SETELAH SETUP SELESAI!</strong></p>";
```

#### B2. Akses via Browser

Buka URL berikut di browser (ganti secret key):

```
https://gajipro.jagofullstack.com/artisan-web.php?key=GANTI_DENGAN_SECRET_KEY_RANDOM_PANJANG_123
```

Halaman akan menampilkan daftar command yang tersedia. Jalankan **satu per satu** dalam urutan:

1. `?key=SECRET&cmd=key` → Generate APP_KEY
2. `?key=SECRET&cmd=migrate` → Jalankan migration
3. `?key=SECRET&cmd=seed` → Jalankan seeder
4. `?key=SECRET&cmd=storage` → Buat storage link
5. `?key=SECRET&cmd=config-cache` → Cache config
6. `?key=SECRET&cmd=route-cache` → Cache routes
7. `?key=SECRET&cmd=view-cache` → Cache views

#### B3. Alternatif: Cron Job One-Time

1. Di hPanel → **Advanced** → **Cron Jobs**
2. Buat cron job:
   - **Command**:
     ```
     cd /home/u123456789/domains/gajipro.jagofullstack.com && php artisan migrate --force >> /home/u123456789/migration-log.txt 2>&1
     ```
   - **Interval**: bisa pilih yang terdekat (misalnya 1 menit dari sekarang)
3. Tunggu cron berjalan (cek log: `/home/u123456789/migration-log.txt` via File Manager)
4. **Hapus cron job** setelah selesai
5. Ulangi untuk command lain (`db:seed --force`, `storage:link`, dll)

#### B4. PENTING: Hapus `artisan-web.php`

Setelah semua setup selesai, **WAJIB hapus** file `artisan-web.php` dari hosting!
File ini adalah **security risk** jika dibiarkan di production.

---

## 10. Set Permission & Storage Link

### Jalur A: Via Terminal

```bash
cd ~/domains/gajipro.jagofullstack.com

# Set permission
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Buat storage link
php artisan storage:link

# Verifikasi
ls -la public/storage
# Harus symlink ke ../storage/app/public
```

### Jalur B: Tanpa Terminal

**Permission** biasanya sudah benar setelah upload. Jika ada error permission:

1. hPanel → **Files** → **File Manager**
2. Klik kanan folder `storage` → **Permissions**
3. Set ke `775` (rwxrwxr-x) → apply recursively
4. Lakukan hal yang sama untuk folder `bootstrap/cache`

**Storage link** — sudah dijalankan via `artisan-web.php` di Step 9 (command `storage`).

Jika storage link belum jalan, buat manual:

1. Di File Manager → navigate ke `public/`
2. Cek apakah ada folder/link bernama `storage`
3. Jika tidak ada, dan File Manager tidak support symlink:
   - Gunakan **Cron Job one-time** (lihat Step 9, B3) dengan command:
     ```
     cd /home/u123456789/domains/gajipro.jagofullstack.com && php artisan storage:link
     ```

---

## 11. Cache & Optimize

### Jalur A: Via Terminal

```bash
cd ~/domains/gajipro.jagofullstack.com

# Clear semua cache dulu
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Generate API docs
php artisan l5-swagger:generate

# Cache untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### Jalur B: Tanpa Terminal

Jalankan via `artisan-web.php` (lihat Step 9) dalam urutan:

1. `?key=SECRET&cmd=config-clear`
2. `?key=SECRET&cmd=route-clear`
3. `?key=SECRET&cmd=view-clear`
4. `?key=SECRET&cmd=cache-clear`
5. `?key=SECRET&cmd=swagger`
6. `?key=SECRET&cmd=config-cache`
7. `?key=SECRET&cmd=route-cache`
8. `?key=SECRET&cmd=view-cache`

Atau via Cron Job one-time:
```
cd /home/u123456789/domains/gajipro.jagofullstack.com && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 12. Setup Cron Job

Laravel membutuhkan cron job untuk scheduled tasks (tersedia di **semua paket** Hostinger).

### Via hPanel

1. Di hPanel → **Advanced** → **Cron Jobs**
2. Tambahkan cron job baru:
   - **Command**:
     ```
     cd /home/u123456789/domains/gajipro.jagofullstack.com && php artisan schedule:run >> /dev/null 2>&1
     ```
   - **Interval**: Pilih **Every minute** (atau custom: `* * * * *`)
3. Klik **Create**

### Verifikasi Path

Cari full path ke project:
- **Jalur A**: di terminal jalankan `pwd`
- **Jalur B**: di File Manager, path ditampilkan di breadcrumb atas

Format path biasanya: `/home/u123456789/domains/gajipro.jagofullstack.com`

---

## 13. Setup SSL (HTTPS)

### Install SSL Gratis

1. Di hPanel → **Security** → **SSL**
2. Pilih subdomain `gajipro.jagofullstack.com`
3. Install **Free SSL** (Let's Encrypt)
4. Tunggu beberapa menit sampai aktif (ada tanda gembok hijau)

### Force HTTPS

Edit `.htaccess` di folder `public/` (atau `public_html/` jika pakai Solusi 2):

**Jalur A:** `nano ~/domains/gajipro.jagofullstack.com/public/.htaccess`

**Jalur B:** File Manager → edit file `.htaccess`

Tambahkan **setelah** baris `RewriteEngine On`:

```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

File `.htaccess` lengkap seharusnya:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 14. Testing & Verifikasi

### Cek Website

1. Buka browser: `https://gajipro.jagofullstack.com`
2. Harus muncul halaman GajiPro (landing page atau login page)

### Checklist Verifikasi

| No | Item | Cara Cek | Status |
|----|------|----------|--------|
| 1 | Website bisa diakses | Buka URL di browser | ☐ |
| 2 | HTTPS aktif (SSL) | Cek gembok di address bar | ☐ |
| 3 | Login berfungsi | Coba login dengan akun | ☐ |
| 4 | Database connected | Login berhasil = DB OK | ☐ |
| 5 | CSS/JS loaded | Halaman tampil dengan styling | ☐ |
| 6 | Storage link bekerja | Upload/lihat avatar/foto | ☐ |
| 7 | API endpoint bekerja | Test dari Flutter app / Postman | ☐ |
| 8 | Email bisa terkirim | Test fitur yang kirim email | ☐ |
| 9 | Cron job berjalan | Cek scheduled tasks | ☐ |
| 10 | `artisan-web.php` sudah dihapus | Pastikan tidak ada di public | ☐ |

---

## 15. Troubleshooting

### Error 500 (Internal Server Error)

**Cara cek error:**

- **Jalur A**: `tail -50 storage/logs/laravel.log`
- **Jalur B**: File Manager → buka `storage/logs/laravel.log` → Edit (untuk baca)

**Penyebab umum:**
| Masalah | Solusi |
|---------|--------|
| `.env` belum ada | Copy `.env.example` → rename → edit |
| APP_KEY kosong | Generate key (lihat Step 7) |
| Permission error | chmod 775 pada `storage/` dan `bootstrap/cache/` |
| vendor/ tidak lengkap | Re-upload atau re-install composer |
| PHP version salah | Ubah di hPanel → PHP Configuration |

### Error 403 (Forbidden)

**Penyebab:**
- Document root salah (tidak mengarah ke `public/`)
- File permission terlalu ketat

**Solusi:**
- Pastikan document root sudah benar (Step 5)
- Set permission: folder `public` = 755, file `index.php` & `.htaccess` = 644

### Error 404 (Not Found) — Route Tidak Jalan

**Penyebab:**
- `mod_rewrite` tidak aktif
- `.htaccess` tidak terbaca

**Solusi:**
1. hPanel → **Advanced** → **PHP Configuration** → **PHP Options**
2. Pastikan `mod_rewrite` enabled
3. Pastikan `.htaccess` ada di folder public

### CSS/JS Tidak Muncul (Blank / Unstyled Page)

**Penyebab:**
- `public/build/` belum di-upload
- manifest.json tidak ada

**Solusi:**
- Build ulang di local: `npm run build`
- Upload folder `public/build/` ke hosting

### Database Connection Error

**Penyebab umum:**
| Masalah | Solusi |
|---------|--------|
| Username/password salah | Cek di hPanel → MySQL Databases |
| Database name salah | Ingat prefix `u123456789_` |
| DB_HOST salah | Gunakan `localhost` (bukan 127.0.0.1) |
| DB_CONNECTION salah | Harus `mysql` (bukan `sqlite`) |

### Composer Memory Limit (Jalur A)

```bash
php -d memory_limit=-1 $(which composer) install --no-dev --optimize-autoloader
```

### Symlink Tidak Bisa Dibuat (Jalur B)

Jika `php artisan storage:link` gagal tanpa terminal:

1. File Manager → masuk ke `public/`
2. Buat folder manual bernama `storage`
3. Copy isi dari `storage/app/public/` ke `public/storage/`

> Ini bukan symlink tapi "hardcopy" — file yang di-upload ke `storage/app/public/` harus di-copy manual ke `public/storage/`. Tidak ideal tapi workaround jika symlink gagal.

### exec() Disabled (artisan-web.php Tidak Jalan)

Beberapa shared hosting men-disable fungsi `exec()`. Jika `artisan-web.php` menampilkan error:

**Solusi**: Gunakan Cron Job one-time (Step 9 B3) sebagai gantinya — cron job menjalankan command langsung tanpa melalui web server.

---

## 16. Update / Redeploy

### Jalur A: Via Terminal — Script Deploy

```bash
cd ~/domains/gajipro.jagofullstack.com

# Buat deploy script
cat > deploy.sh << 'SCRIPT'
#!/bin/bash
set -e

cd ~/domains/gajipro.jagofullstack.com

echo "Pulling latest changes..."
git pull origin main

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Running migrations..."
php artisan migrate --force

echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Generating API docs..."
php artisan l5-swagger:generate

echo "Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deploy selesai!"
SCRIPT

chmod +x deploy.sh
```

Setiap kali update:
```bash
ssh u123456789@ssh.jagofullstack.com -p 65002
cd ~/domains/gajipro.jagofullstack.com
./deploy.sh
```

### Jalur B: Tanpa Terminal — Upload Ulang

1. Di lokal, pastikan code sudah updated:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm run build
   ```
2. Buat ZIP baru (hanya file yang berubah, atau full project)
3. Upload via File Manager / FTP
4. Extract & overwrite
5. Jalankan migration via Cron Job one-time:
   ```
   cd /home/u123456789/domains/gajipro.jagofullstack.com && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache >> /home/u123456789/deploy-log.txt 2>&1
   ```
6. Cek log di File Manager: `/home/u123456789/deploy-log.txt`

### Update Frontend Assets (Semua Jalur)

Selalu build di local, lalu upload:

```bash
# Di lokal
npm run build

# Upload via SCP (Jalur A)
scp -P 65002 -r public/build/ u123456789@ssh.jagofullstack.com:~/domains/gajipro.jagofullstack.com/public/build/

# Atau ZIP + upload via File Manager (Jalur B)
cd public && zip -r build.zip build/
# Upload build.zip → extract di hosting
```

---

## Ringkasan Alur Deploy

### Jalur A: Dengan Terminal (Premium+)

```
Beli Hosting Premium+
       ↓
Setup Subdomain & Database
       ↓
SSH/Browser Terminal → git clone
       ↓
Ubah Document Root → public/
       ↓
composer install --no-dev
       ↓
Setup .env + key:generate
       ↓
Build assets (local) → SCP upload
       ↓
php artisan migrate + seed
       ↓
Permission + Cache + SSL + Cron
       ↓
Website LIVE!
```

### Jalur B: Tanpa Terminal (Paket Single)

```
Beli Hosting Single
       ↓
Setup Subdomain & Database
       ↓
Lokal: composer install + npm build
       ↓
ZIP project → Upload File Manager/FTP
       ↓
Edit document root / index.php
       ↓
Edit .env via File Manager
       ↓
Generate APP_KEY di lokal → paste
       ↓
artisan-web.php / Cron Job → migrate + seed
       ↓
Permission + Cache + SSL + Cron
       ↓
HAPUS artisan-web.php!
       ↓
Website LIVE!
```

---

## Tips Production

1. **Selalu `APP_DEBUG=false`** di production — jangan pernah `true`, bisa expose data sensitif
2. **Backup database** secara berkala — bisa setup dari hPanel → Backups
3. **Monitor storage** — shared hosting punya batas disk space
4. **Jangan edit code langsung di hosting** — selalu edit di local, push ke git, lalu deploy
5. **Gunakan `.gitignore`** dengan benar — `.env`, `vendor/`, `node_modules/`, `public/build/` tidak boleh masuk git
6. **Setup monitoring** — cek `storage/logs/laravel.log` secara berkala untuk error
7. **Hapus file helper** — setelah setup, pastikan `artisan-web.php` sudah dihapus
8. **PHP version** — pastikan konsisten antara lokal dan hosting

---

## Perbandingan Quick Reference

| Aksi | Jalur A (Terminal) | Jalur B (Tanpa Terminal) |
|------|-------------------|--------------------------|
| Upload code | `git clone` / `git pull` | ZIP + File Manager / FTP |
| Install vendor | `composer install` | Upload `vendor/` dari lokal |
| Edit .env | `nano .env` | File Manager → Edit |
| Generate key | `php artisan key:generate` | Generate di lokal → copy paste |
| Migration | `php artisan migrate` | artisan-web.php / Cron Job |
| Storage link | `php artisan storage:link` | artisan-web.php / Cron Job |
| Cache | `php artisan config:cache` | artisan-web.php / Cron Job |
| Build assets | Build lokal → SCP | Build lokal → ZIP upload |
| Cek error | `tail storage/logs/...` | File Manager → buka log |
| Redeploy | `./deploy.sh` | Upload ulang + Cron Job |

---

## Referensi

- [Hostinger hPanel Documentation](https://support.hostinger.com)
- [Hostinger SSH Access Guide](https://support.hostinger.com/en/articles/1583245)
- [Laravel Deployment Documentation](https://laravel.com/docs/12.x/deployment)
