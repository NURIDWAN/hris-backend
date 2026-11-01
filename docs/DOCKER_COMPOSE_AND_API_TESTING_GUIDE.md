# Panduan Menjalankan Docker Compose & Eksekusi Test API

Dokumen ini merupakan panduan lengkap untuk menjalankan environment **GajiPro Backend** menggunakan Docker Compose serta mengeksekusi pengujian otomatis (*automated API tests*) menggunakan PHPUnit dan Pest Framework.

---

## Daftar Isi
1. [Arsitektur Container](#1-arsitektur-container)
2. [Prasyarat Sistem](#2-prasyarat-sistem)
3. [Cara Menjalankan Docker Compose](#3-cara-menjalankan-docker-compose)
   - [Opsi 1: Menjalankan Cluster Database Galera + Aplikasi (Disarankan)](#opsi-1-menjalankan-cluster-database-galera--aplikasi-disarankan)
   - [Opsi 2: Menjalankan Mode Standalone (Single Node Database)](#opsi-2-menjalankan-mode-standalone-single-node-database)
4. [Akses Port & Monitoring Service](#4-akses-port--monitoring-service)
5. [Cara Menjalankan Test API](#5-cara-menjalankan-test-api)
   - [Katalog Test API yang Tersedia](#katalog-test-api-yang-tersedia)
   - [Menjalankan Seluruh Test API](#menjalankan-seluruh-test-api)
   - [Menjalankan Test API Spesifik per Fitur](#menjalankan-test-api-spesifik-per-fitur)
   - [Menjalankan Test dengan Filter Kasus Uji](#menjalankan-test-dengan-filter-kasus-uji)
   - [Menjalankan Test Menggunakan Pest CLI](#menjalankan-test-menggunakan-pest-cli)
6. [Perintah Artisan & Database Migration di Docker](#6-perintah-artisan--database-migration-di-docker)
7. [Troubleshooting & Solusi Masalah Umum](#7-troubleshooting--solusi-masalah-umum)

---

## 1. Arsitektur Container

Proyek ini mendukung dua mode operasional:

```
[ Mode Cluster (High Availability) ]
                                   ┌───> mariadb-node1 (Primary Writer)
Aplikasi GajiPro ──> db-cluster ───┼───> mariadb-node2 (Standby Sync)
(app / queue)       (HAProxy:3306) └───> mariadb-node3 (Standby Quorum)
```

1. **Database Cluster Mode** (`docker-compose.db-cluster.yml` + `docker-compose.cluster.yml`):
   - 3 Node MariaDB 10.11 Galera Cluster (Multi-master synchronous replication).
   - 1 Node HAProxy Load Balancer sebagai Virtual Entrypoint (`db-cluster:3306`).
   - Aplikasi Laravel terhubung langsung ke HAProxy, menjamin zero-downtime failover.
2. **Standalone Mode** (`docker-compose.yml`):
   - 1 Node MariaDB konvensional untuk development lokal ringan tanpa replikasi.

---

## 2. Prasyarat Sistem

Pastikan Docker Engine dan Docker Compose Plugin telah terpasang:
```bash
docker --version         # Minimal Docker v24+ (v29+ disarankan)
docker compose version   # Minimal Docker Compose v2.20+ (v2.40+ disarankan)
```

Jika network `proxy` belum dibuat di Docker:
```bash
docker network create proxy 2>/dev/null || true
```

---

## 3. Cara Menjalankan Docker Compose

### Opsi 1: Menjalankan Cluster Database Galera + Aplikasi (Disarankan)

#### Langkah 1: Jalankan Cluster Database
Gunakan script inisialisasi otomatis agar 3 node Galera di-bootstrap secara berurutan:
```bash
./docker/cluster/cluster-init.sh start
```
*Script ini akan menyalakan Node 1 (bootstrap), menunggu healthy, menggabungkan Node 2 & 3, lalu menyalakan HAProxy.*

Verifikasi kesehatan cluster:
```bash
./docker/cluster/cluster-init.sh status
```
*Pastikan output menunjukkan `wsrep_cluster_size: 3` dan `wsrep_local_state_comment: Synced`.*

#### Langkah 2: Jalankan Aplikasi Backend & Queue Worker
Jalankan aplikasi Laravel dengan memanfaatkan file override cluster:
```bash
docker compose -f docker-compose.yml -f docker-compose.cluster.yml up -d
```

Layanan yang akan berjalan:
- `gajipro-app`: PHP 8.3-FPM Backend
- `toko-web`: Nginx Web Server
- `gajipro-redis`: Cache & Queue Driver
- `gajipro-queue`: Background Worker Process

---

### Opsi 2: Menjalankan Mode Standalone (Single Node Database)

Gunakan opsi ini jika Anda ingin menghemat memori RAM (cocok untuk development lokal harian di laptop):

#### Langkah 1: Matikan Cluster (jika sedang menyala)
Karena port `3307` dipakai bersama oleh HAProxy dan Single MariaDB pada host:
```bash
./docker/cluster/cluster-init.sh stop
```

#### Langkah 2: Jalankan Single Database & Aplikasi
```bash
# Menjalankan seluruh stack aplikasi dengan single database
docker compose up -d

# Atau jika HANYA ingin menjalankan container databasenya saja:
docker compose up -d mariadb
```

#### Konfigurasi `.env` untuk Single Database
- **Di dalam Container Docker**: `DB_HOST=mariadb`, `DB_PORT=3306`
- **Dari Luar Container / Host OS (DBeaver/Navicat/TablePlus)**: `DB_HOST=127.0.0.1`, `DB_PORT=3307`

#### Cara Beralih Kembali ke Mode Cluster:
```bash
# 1. Hentikan single database
docker compose stop mariadb

# 2. Nyalakan cluster
./docker/cluster/cluster-init.sh start

# 3. Jalankan aplikasi dengan override cluster
docker compose -f docker-compose.yml -f docker-compose.cluster.yml up -d
```

---

## 4. Akses Port & Monitoring Service

| Layanan / Fitur | Host Port | Internal Docker URL | Keterangan & Kredensial |
|---|---|---|---|
| **API Backend (Web)** | *Via Proxy/NPM* | `http://toko-web:80` | Endpoint REST API v1 |
| **MySQL Cluster (Main)** | `3307` | `db-cluster:3306` | User: `gajipro`, Pass: `secret`, DB: `gajipro` |
| **MySQL Read-Only Pool** | `3308` | `db-cluster:3308` | Load balancing SELECT ke 3 node |
| **HAProxy Monitoring GUI**| `8404` | `http://localhost:8404` | User: `admin`, Pass: `secret` |
| **Redis** | `6380` | `redis:6379` | Cache & Job Queue |

---

## 5. Cara Menjalankan Test API

Aplikasi ini menggunakan **Pest PHP** dan **PHPUnit**. Secara default, konfigurasi testing (`phpunit.xml`) menggunakan **SQLite In-Memory (`:memory:`)**, sehingga pengetesan berjalan sangat cepat dan tidak mengotori database utama.

### Katalog Test API yang Tersedia

File-file test API berada di direktori `tests/Feature/Api/`:

| File Test | Fitur yang Diuji |
|---|---|
| `TenantRegistrationApiTest.php` | Registrasi mandiri tenant, pembuatan admin, aktivasi paket, proteksi single-tenant |
| `BillingApiTest.php` | Status langganan tenant, upgrade paket, invoice history, pembatalan langganan |
| `SuperadminBillingApiTest.php` | Ekstensi langganan manual, konfirmasi pembayaran invoice oleh superadmin |
| `AuthenticationApiTest.php` | Login API, token Sanctum, demo register, forgot password, rate limiting |
| `AttendanceApiTest.php` | Check-in, check-out, geofencing GPS, offline sync presensi |
| `LeaveApiTest.php` | Pengajuan cuti, kuota cuti, persetujuan atasan |
| `OvertimeApiTest.php` | Pengajuan lembur, kalkulasi jam, approval lembur |
| `PayslipApiTest.php` | Slip gaji digital, komponen tunjangan, potongan PPh21 |
| `ReimbursementApiTest.php` | Klaim biaya, upload bukti struk, approval keuangan |
| `FaceRecognitionApiTest.php` | Pendaftaran wajah (*face enrollment*), verifikasi biometrik |

---

### Menjalankan Seluruh Test API

Jalankan seluruh test di folder `tests/Feature/Api`:

```bash
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan test --compact tests/Feature/Api
```

---

### Menjalankan Test API Spesifik per Fitur

#### 1. Uji Fitur Pendaftaran Tenant & Billing Langganan:
```bash
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan test --compact \
  tests/Feature/Api/TenantRegistrationApiTest.php \
  tests/Feature/Api/BillingApiTest.php \
  tests/Feature/Api/SuperadminBillingApiTest.php
```

#### 2. Uji Autentikasi & Login API:
```bash
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan test --compact \
  tests/Feature/Api/AuthenticationApiTest.php
```

#### 3. Uji Presensi Mobile & Geofencing:
```bash
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan test --compact \
  tests/Feature/Api/AttendanceApiTest.php
```

#### 4. Uji Penggajian (Payslip) & Klaim (Reimbursement):
```bash
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan test --compact \
  tests/Feature/Api/PayslipApiTest.php \
  tests/Feature/Api/ReimbursementApiTest.php
```

---

### Menjalankan Test dengan Filter Kasus Uji

Gunakan flag `--filter` untuk menjalankan method atau deskripsi test tertentu saja:

```bash
# Hanya jalankan test yang mengandung kata 'register'
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan test --compact --filter=register

# Hanya jalankan test validasi invoice
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan test --compact --filter=invoice
```

---

### Menjalankan Test Menggunakan Pest CLI

Anda juga dapat memanggil Pest CLI secara langsung di dalam container:

```bash
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app ./vendor/bin/pest tests/Feature/Api/TenantRegistrationApiTest.php
```

Jika container `gajipro-app` sudah dalam keadaan berjalan (`Up`), Anda bisa menggunakan `exec` yang jauh lebih cepat tanpa membuat container baru:
```bash
docker compose exec app php artisan test --compact tests/Feature/Api/TenantRegistrationApiTest.php
```

---

## 6. Perintah Artisan & Database Migration di Docker

Ketika cluster database aktif, Anda dapat menjalankan perintah Artisan langsung ke cluster:

```bash
# Menjalankan Database Migration
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan migrate

# Menjalankan Migration Fresh + Seed Data Master & Demo
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan migrate:fresh --seed

# Membuka Laravel Tinker Interactive Shell
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan tinker

# Membersihkan dan Mengoptimasi Cache Aplikasi
docker compose -f docker-compose.yml -f docker-compose.cluster.yml run --rm app php artisan optimize:clear
```

---

## 7. Troubleshooting & Solusi Masalah Umum

### 1. Error: `Bind for 0.0.0.0:3307 failed: port is already allocated`
- **Penyebab**: Container HAProxy (`gajipro-db-cluster`) sedang menggunakan port 3307, lalu Anda menjalankan `docker compose up` standar yang mencoba menjalankan single MariaDB pada port yang sama.
- **Solusi**: Gunakan flag override cluster `-f docker-compose.cluster.yml` yang secara otomatis menonaktifkan single MariaDB lokal:
  ```bash
  docker compose -f docker-compose.yml -f docker-compose.cluster.yml up -d
  ```

### 2. Error: `network proxy declared as external, but could not be found`
- **Penyebab**: Docker network `proxy` belum dibuat pada host Docker Anda.
- **Solusi**: Buat network secara manual:
  ```bash
  docker network create proxy
  ```

### 3. Galera Cluster Node Gagal Bergabung (Desync)
- **Penyebab**: Salah satu node database terhenti tidak wajar atau terjadi split-brain saat restart.
- **Solusi**: Gunakan perintah restart pada helper script:
  ```bash
  ./docker/cluster/cluster-init.sh restart
  ./docker/cluster/cluster-init.sh status
  ```

### 4. Membersihkan & Menghentikan Seluruh Container
Jika ingin menghentikan seluruh container dan mengembalikan resource:
```bash
# Hentikan aplikasi
docker compose -f docker-compose.yml -f docker-compose.cluster.yml down

# Hentikan cluster database
./docker/cluster/cluster-init.sh stop
```
