# Installation & Operations Checklist — PT Gemilang Sari Husada

> **Dokumen**: Checklist instalasi, go-live, dan operasional pasca deployment
> **Target Audience**: DevOps engineer + System Admin Client
> **Tanggal Dibuat**: 16 April 2026
> **Referensi**: [CLIENT_GEMILANG_MASTER_PLAN.md](./CLIENT_GEMILANG_MASTER_PLAN.md), [CLIENT_GEMILANG_CONVERSION_TASKS.md](./CLIENT_GEMILANG_CONVERSION_TASKS.md)

---

## Table of Contents

1. [Server Requirements](#1-server-requirements)
2. [Pre-Installation Checklist](#2-pre-installation-checklist)
3. [Installation Step-by-Step](#3-installation-step-by-step)
4. [Web Server Configuration](#4-web-server-configuration)
5. [SSL / HTTPS Setup](#5-ssl--https-setup)
6. [Systemd Services (Queue & Scheduler)](#6-systemd-services-queue--scheduler)
7. [Backup Automation](#7-backup-automation)
8. [Monitoring & Logging](#8-monitoring--logging)
9. [Security Hardening (Server)](#9-security-hardening-server)
10. [Go-Live Checklist](#10-go-live-checklist)
11. [Rollback Plan](#11-rollback-plan)
12. [Operations & Maintenance](#12-operations--maintenance)
13. [Troubleshooting](#13-troubleshooting)
14. [Handover Documentation](#14-handover-documentation)

---

## 1. Server Requirements

### 1.1 Minimum Spec (≤ 100 karyawan)

| Resource | Minimum | Rekomendasi |
|----------|---------|-------------|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 4 GB | 8 GB |
| Storage | 40 GB SSD | 80 GB SSD |
| Bandwidth | 100 Mbps | 1 Gbps |
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |

### 1.2 Rekomendasi Spec (100-500 karyawan)

| Resource | Spec |
|----------|------|
| CPU | 4-8 vCPU |
| RAM | 8-16 GB |
| Storage | 100-200 GB SSD |
| Database | MySQL 8.0+ (separate server opsional) |

### 1.3 Software Requirements

- [ ] Ubuntu 22.04 LTS atau 24.04 LTS (atau Debian 12)
- [ ] PHP 8.3+ dengan extensions:
  - [ ] `php8.3-fpm`
  - [ ] `php8.3-mysql`
  - [ ] `php8.3-mbstring`
  - [ ] `php8.3-xml`
  - [ ] `php8.3-curl`
  - [ ] `php8.3-zip`
  - [ ] `php8.3-gd` atau `php8.3-imagick`
  - [ ] `php8.3-bcmath`
  - [ ] `php8.3-intl`
  - [ ] `php8.3-redis` (jika pakai Redis)
- [ ] MySQL 8.0+ atau MariaDB 10.11+
- [ ] Nginx 1.24+ atau Apache 2.4+
- [ ] Composer 2.6+
- [ ] Node.js 20 LTS + npm
- [ ] Git 2.40+
- [ ] Redis 7.0+ (opsional, untuk cache & queue)
- [ ] Supervisor (untuk queue worker) atau systemd
- [ ] Certbot (Let's Encrypt) untuk SSL

---

## 2. Pre-Installation Checklist

### 2.1 Infrastructure

- [ ] Server provisioned dan accessible via SSH
- [ ] Root/sudo access tersedia
- [ ] Domain/subdomain sudah di-pointing ke IP server (A record)
- [ ] Firewall opening: 80, 443, 22 (ssh), 3306 (jika DB separate)
- [ ] Timezone server di-set ke `Asia/Jakarta`
- [ ] Swap configured (min 2GB jika RAM < 8GB)
- [ ] Non-root user dibuat untuk deployment (mis. `deploy`)
- [ ] SSH key authentication aktif, password login disabled

### 2.2 Network & DNS

- [ ] Domain aktif (mis. `hris.gemilangsari.com`)
- [ ] DNS A record menunjuk ke IP server
- [ ] (Opsional) DNS CNAME untuk `www`
- [ ] Test domain resolvable: `dig +short hris.gemilangsari.com`
- [ ] MX record setup jika kirim email dari domain ini

### 2.3 Database

- [ ] MySQL 8.0+ installed & running
- [ ] Database `gajipro_production` dibuat
- [ ] User `gajipro_user` dibuat dengan password strong
- [ ] Privileges: ALL on `gajipro_production.*` untuk user
- [ ] `innodb_buffer_pool_size` di-tune (25-50% RAM)
- [ ] Backup user (read-only) dibuat

```sql
CREATE DATABASE gajipro_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gajipro_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON gajipro_production.* TO 'gajipro_user'@'localhost';
CREATE USER 'gajipro_backup'@'localhost' IDENTIFIED BY 'BACKUP_PASSWORD';
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON gajipro_production.* TO 'gajipro_backup'@'localhost';
FLUSH PRIVILEGES;
```

### 2.4 Email (SMTP)

- [ ] SMTP credentials dari provider client (Gmail Workspace, Zoho, Mailgun, dll)
- [ ] Test SMTP dengan telnet atau openssl sebelum setup app
- [ ] SPF/DKIM record di domain (jika kirim dari own domain)
- [ ] Test kirim email dari server via `msmtp` atau `swaks`

### 2.5 Storage & File Permissions

- [ ] Mount point untuk `/var/www/gajipro` sudah diputuskan
- [ ] User `www-data` (atau user yang nginx/php-fpm pakai) akan jadi owner
- [ ] Minimal 20GB free untuk aplikasi + uploads + logs

---

## 3. Installation Step-by-Step

### 3.1 Update System

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl wget unzip git
sudo timedatectl set-timezone Asia/Jakarta
```

### 3.2 Install PHP 8.3

```bash
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3-fpm php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath \
  php8.3-intl php8.3-redis php8.3-cli

php -v  # Verify 8.3.x
```

**Tune PHP-FPM** (`/etc/php/8.3/fpm/php.ini`):

```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
date.timezone = Asia/Jakarta
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  ; Production only
```

Restart: `sudo systemctl restart php8.3-fpm`

### 3.3 Install MySQL

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Run DB & user creation SQL from section 2.3
```

### 3.4 Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
```

### 3.5 Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 3.6 Install Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v && npm -v
```

### 3.7 Install Redis (Opsional, direkomendasikan)

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
redis-cli ping  # Expect: PONG
```

### 3.8 Clone & Setup Application

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone <REPO_URL> gajipro
sudo chown -R deploy:www-data /var/www/gajipro
cd /var/www/gajipro

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Environment
cp .env.production.example .env
nano .env  # Fill in credentials (see section 3.9)

# Generate app key
php artisan key:generate

# Storage symlink
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Database migrations
php artisan migrate --force

# Seed initial data (single company, departments, positions, leave types)
php artisan db:seed --class=ProductionSeeder --force

# Create admin user
php artisan make:filament-user  # atau artisan command custom

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 3.9 Environment Variables (`.env`)

```ini
APP_NAME="HRIS Gemilang"
APP_ENV=production
APP_KEY=base64:GENERATED_KEY
APP_DEBUG=false
APP_URL=https://hris.gemilangsari.com
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

# Single tenant lock (custom config)
SINGLE_TENANT_MODE=true
SINGLE_TENANT_COMPANY_ID=1

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gajipro_production
DB_USERNAME=gajipro_user
DB_PASSWORD=STRONG_PASSWORD

# Redis (recommended)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@gemilangsari.com
MAIL_PASSWORD=APP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@gemilangsari.com"
MAIL_FROM_NAME="HRIS Gemilang"

# Storage
FILESYSTEM_DISK=local

# Security
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Disable public registration
REGISTRATION_ENABLED=false
BILLING_ENABLED=false
```

---

## 4. Web Server Configuration

### 4.1 Nginx Config (`/etc/nginx/sites-available/gajipro`)

```nginx
server {
    listen 80;
    server_name hris.gemilangsari.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name hris.gemilangsari.com;
    root /var/www/gajipro/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/hris.gemilangsari.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/hris.gemilangsari.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    client_max_body_size 50M;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffers 16 32k;
        fastcgi_buffer_size 64k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Logging
    access_log /var/log/nginx/gajipro-access.log;
    error_log /var/log/nginx/gajipro-error.log;
}
```

**Enable site**:

```bash
sudo ln -s /etc/nginx/sites-available/gajipro /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 5. SSL / HTTPS Setup

### 5.1 Let's Encrypt (Gratis, direkomendasikan)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d hris.gemilangsari.com --non-interactive --agree-tos -m admin@gemilangsari.com

# Auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# Test renewal
sudo certbot renew --dry-run
```

### 5.2 Commercial SSL (jika client sudah punya)

- [ ] Upload certificate ke `/etc/ssl/certs/gajipro.crt`
- [ ] Upload private key ke `/etc/ssl/private/gajipro.key`
- [ ] Update Nginx config dengan path yang benar
- [ ] Test SSL dengan: `https://www.ssllabs.com/ssltest/`
- [ ] Rating minimal: **A**

---

## 6. Systemd Services (Queue & Scheduler)

### 6.1 Queue Worker Service

Create `/etc/systemd/system/gajipro-queue.service`:

```ini
[Unit]
Description=GajiPro Queue Worker
After=network.target mysql.service redis-server.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php /var/www/gajipro/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=600
WorkingDirectory=/var/www/gajipro
StandardOutput=append:/var/log/gajipro-queue.log
StandardError=append:/var/log/gajipro-queue-error.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable gajipro-queue
sudo systemctl start gajipro-queue
sudo systemctl status gajipro-queue
```

### 6.2 Scheduler Cron

Tambahkan ke crontab `www-data`:

```bash
sudo crontab -u www-data -e
```

Isi:

```
* * * * * cd /var/www/gajipro && php artisan schedule:run >> /dev/null 2>&1
```

### 6.3 Alternatif: Supervisor (jika tidak pakai systemd)

`/etc/supervisor/conf.d/gajipro-queue.conf`:

```ini
[program:gajipro-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/gajipro/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/gajipro-queue.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gajipro-queue:*
```

---

## 7. Backup Automation

### 7.1 Install `spatie/laravel-backup`

```bash
cd /var/www/gajipro
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

### 7.2 Configure Backup (`config/backup.php`)

```php
'backup' => [
    'name' => env('APP_NAME', 'gajipro'),
    'source' => [
        'files' => [
            'include' => [
                base_path('.env'),
                storage_path('app/public'),
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
                storage_path('logs'),
            ],
        ],
        'databases' => ['mysql'],
    ],
    'destination' => [
        'disks' => ['backups', 's3_backups'],  // Local + Off-site
    ],
],

'cleanup' => [
    'default_strategy' => [
        'keep_all_backups_for_days' => 7,
        'keep_daily_backups_for_days' => 30,
        'keep_weekly_backups_for_weeks' => 8,
        'keep_monthly_backups_for_months' => 12,
        'delete_oldest_backups_when_using_more_megabytes_than' => 10000,
    ],
],
```

### 7.3 Schedule Backup

`app/Console/Kernel.php` atau `routes/console.php`:

```php
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('02:00');
Schedule::command('backup:monitor')->daily()->at('03:00');
```

### 7.4 Configure Off-site Backup (S3 / Wasabi / Backblaze)

Di `.env`:

```ini
BACKUP_ARCHIVE_PASSWORD=STRONG_ARCHIVE_PASSWORD

AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=gajipro-backup
```

`config/filesystems.php`:

```php
'backups' => [
    'driver' => 'local',
    'root' => storage_path('app/backups'),
],
's3_backups' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
],
```

### 7.5 Manual Database Backup Script

`/usr/local/bin/gajipro-db-backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/gajipro"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
mysqldump -u gajipro_backup -pBACKUP_PASSWORD gajipro_production | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
```

```bash
sudo chmod +x /usr/local/bin/gajipro-db-backup.sh

# Cron: run every day at 2 AM
echo "0 2 * * * /usr/local/bin/gajipro-db-backup.sh" | sudo crontab -
```

### 7.6 Test Backup & Restore

- [ ] Jalankan `php artisan backup:run` manual
- [ ] Verify backup file dibuat di `storage/app/backups/`
- [ ] Test restore di server staging:
  ```bash
  gunzip < db_xxx.sql.gz | mysql -u gajipro_user -p gajipro_staging
  ```
- [ ] Dokumentasikan prosedur restore di runbook

---

## 8. Monitoring & Logging

### 8.1 Application Logs

- [ ] Log level production: `LOG_LEVEL=warning`
- [ ] Log rotation via `logrotate`:

`/etc/logrotate.d/gajipro`:

```
/var/www/gajipro/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0664 www-data www-data
}
```

### 8.2 System Monitoring

- [ ] Install monitoring tool (pilih salah satu):
  - [ ] **Simple**: `htop`, `iotop`, `nethogs` untuk manual check
  - [ ] **Prometheus + Grafana** untuk dashboard metrik
  - [ ] **Netdata** untuk real-time monitoring (minimal setup)
  - [ ] **Third-party**: New Relic, Datadog, Better Uptime

### 8.3 Uptime Monitoring (Critical)

- [ ] Setup external monitor (UptimeRobot, Better Uptime, Pingdom)
- [ ] Monitor endpoint: `https://hris.gemilangsari.com/health` (buat endpoint ini)
- [ ] Alert via email/SMS/Telegram ke admin client & tim internal

### 8.4 Error Tracking (Opsional)

- [ ] Integrate Sentry atau Bugsnag:
  ```bash
  composer require sentry/sentry-laravel
  php artisan sentry:publish --dsn=SENTRY_DSN
  ```

### 8.5 Database Monitoring

- [ ] Enable slow query log:
  ```ini
  # /etc/mysql/mysql.conf.d/mysqld.cnf
  slow_query_log = 1
  slow_query_log_file = /var/log/mysql/slow.log
  long_query_time = 2
  ```
- [ ] Review slow queries weekly

### 8.6 Health Check Endpoint

Buat route `/health` untuk uptime monitor:

```php
// routes/web.php
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        Cache::put('health-check', now(), 10);
        return response()->json(['status' => 'ok', 'time' => now()], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 503);
    }
});
```

---

## 9. Security Hardening (Server)

### 9.1 SSH Security

- [ ] Disable root login: `PermitRootLogin no` di `/etc/ssh/sshd_config`
- [ ] Disable password auth: `PasswordAuthentication no`
- [ ] Change default port (opsional): `Port 2222`
- [ ] Install `fail2ban`:
  ```bash
  sudo apt install -y fail2ban
  sudo systemctl enable fail2ban
  ```

### 9.2 Firewall (UFW)

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp       # SSH
sudo ufw allow 80/tcp       # HTTP
sudo ufw allow 443/tcp      # HTTPS
sudo ufw enable
sudo ufw status verbose
```

### 9.3 File Permissions

```bash
cd /var/www/gajipro
sudo chown -R www-data:www-data .
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 600 .env
```

### 9.4 Automatic Security Updates

```bash
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

### 9.5 Database Security

- [ ] MySQL bind only to localhost: `bind-address = 127.0.0.1`
- [ ] Remove anonymous users & test database: sudah via `mysql_secure_installation`
- [ ] Rotate DB password setiap 6 bulan

### 9.6 Application Security

- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production`
- [ ] Session cookie secure + httpOnly
- [ ] CSRF middleware aktif pada semua form
- [ ] Rate limiting pada login endpoint (already in GajiPro)
- [ ] Remove Telescope & Debugbar di production (`composer install --no-dev`)

---

## 10. Go-Live Checklist

### 10.1 Pre Go-Live (H-7 hari)

- [ ] Staging environment fully tested & signed-off by client
- [ ] UAT completed dengan sign-off document
- [ ] Data master (departments, positions, schedules) siap di Excel
- [ ] User accounts (admin, HR, karyawan) sudah disiapkan data-nya
- [ ] Backup strategy tested (backup + restore)
- [ ] SSL certificate valid & auto-renewal aktif
- [ ] Monitoring & alerting configured
- [ ] Communication plan ke karyawan client (go-live date, login credentials)

### 10.2 Go-Live Day (H-0)

**Jadwal rekomendasi: Jumat sore atau Sabtu pagi (low traffic)**

- [ ] Final database backup dari staging (jika ada data)
- [ ] Deploy aplikasi ke production server
- [ ] Run migration: `php artisan migrate --force`
- [ ] Import master data (departments, positions, karyawan) via Excel import
- [ ] Create admin accounts:
  - [ ] Admin utama (PIC client)
  - [ ] HR Manager
  - [ ] Payroll Manager (jika ada)
- [ ] Test login dari setiap role
- [ ] Test flow utama:
  - [ ] Employee login → portal
  - [ ] Clock in/out attendance
  - [ ] Submit leave request
  - [ ] Admin approve leave
  - [ ] HR add new employee
  - [ ] Payroll preview (tanpa process)
- [ ] Email test: system kirim email welcome ke 1 karyawan
- [ ] Test from client's network (internal IP, VPN jika ada)
- [ ] Mobile responsiveness check
- [ ] Announce go-live ke client admin PIC

### 10.3 Smoke Tests (H+0 hingga H+3)

- [ ] Check error logs setiap 2 jam pertama
- [ ] Monitor resource usage (CPU, RAM, disk)
- [ ] Check queue jobs processing dengan benar
- [ ] Check scheduled tasks running (cron job)
- [ ] Daily backup berjalan (verify file di `storage/app/backups/`)
- [ ] Tidak ada error email/notification bouncing

### 10.4 Post Go-Live Communication

- [ ] Email blast ke semua karyawan dengan:
  - [ ] URL aplikasi
  - [ ] Username/password mereka (atau link reset password)
  - [ ] Panduan quickstart (PDF)
  - [ ] Kontak support
- [ ] Pengumuman di grup WhatsApp/Slack internal client
- [ ] Video tutorial 5 menit (login + clock in + cek slip gaji)

---

## 11. Rollback Plan

### 11.1 Kriteria Rollback

Rollback ke versi sebelumnya jika:
- Critical bug yang affect > 20% karyawan
- Data corruption terdeteksi
- Performance degradation > 5x baseline
- Security breach

### 11.2 Rollback Procedure

```bash
# 1. Backup current state (just in case)
mysqldump -u gajipro_user -p gajipro_production > /tmp/pre-rollback.sql
tar -czf /tmp/pre-rollback-files.tar.gz /var/www/gajipro

# 2. Checkout previous stable tag
cd /var/www/gajipro
git fetch --tags
git checkout v1.0.0-stable  # atau tag yang stabil

# 3. Reinstall dependencies (if needed)
composer install --no-dev --optimize-autoloader

# 4. Run rollback migrations (jika migrasi terakhir reversible)
php artisan migrate:rollback --step=1 --force

# 5. Clear cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
sudo systemctl restart php8.3-fpm nginx gajipro-queue

# 7. Verify
curl -I https://hris.gemilangsari.com
```

### 11.3 Database Rollback (jika critical)

- [ ] Restore dari backup terakhir sebelum go-live:
  ```bash
  gunzip < /var/backups/gajipro/db_pre_golive.sql.gz | mysql -u gajipro_user -p gajipro_production
  ```
- [ ] Communicate ke client: downtime estimasi + RCA (Root Cause Analysis)

---

## 12. Operations & Maintenance

### 12.1 Daily Tasks

- [ ] Check uptime monitor dashboard
- [ ] Review error logs: `tail -f /var/www/gajipro/storage/logs/laravel.log`
- [ ] Verify backup ran successfully: check file `storage/app/backups/`
- [ ] Queue worker status: `sudo systemctl status gajipro-queue`

### 12.2 Weekly Tasks

- [ ] Review slow query log MySQL
- [ ] Disk usage check: `df -h`
- [ ] Review failed jobs: `php artisan queue:failed`
- [ ] Check for Laravel/package security advisories: `composer audit`
- [ ] Review activity log untuk anomaly

### 12.3 Monthly Tasks

- [ ] Apply OS security updates: `sudo apt update && sudo apt upgrade`
- [ ] Review & rotate application logs (if not auto-rotated)
- [ ] Review user accounts: disable terminated employees
- [ ] SSL certificate expiry check (Let's Encrypt auto-renews, tapi confirm)
- [ ] Test restore backup di staging environment
- [ ] Review metrik: total requests, error rate, response time

### 12.4 Quarterly Tasks

- [ ] Major dependency updates (Laravel minor, packages)
- [ ] Rotate database user passwords
- [ ] Review firewall rules
- [ ] Penetration test internal (atau third-party)
- [ ] DR (Disaster Recovery) drill: full restore dari backup ke fresh server
- [ ] Client feedback session (improvement request)

### 12.5 Update/Deployment Procedure

```bash
# 1. Backup first
php artisan backup:run

# 2. Enable maintenance mode
php artisan down --secret="bypass-token-here"

# 3. Pull latest code
cd /var/www/gajipro
git fetch origin
git checkout main
git pull origin main

# 4. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 5. Run migrations
php artisan migrate --force

# 6. Clear & rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Restart queue
sudo systemctl restart gajipro-queue

# 8. Disable maintenance mode
php artisan up

# 9. Smoke test
curl -I https://hris.gemilangsari.com
```

### 12.6 Log Rotation & Cleanup

- [ ] Activity log: retention 6 bulan (delete older via scheduled command)
- [ ] Laravel logs: 30 hari (via logrotate)
- [ ] Nginx logs: 30 hari (via logrotate default Ubuntu)
- [ ] MySQL slow log: 90 hari

---

## 13. Troubleshooting

### 13.1 Common Issues

| Symptom | Kemungkinan Penyebab | Solusi |
|---------|---------------------|--------|
| 500 Error | `.env` salah / permission | Check `storage/logs/laravel.log`, verify permissions |
| 502 Bad Gateway | PHP-FPM down | `sudo systemctl restart php8.3-fpm` |
| Slow page load | N+1 query / missing index | Enable query log, optimize |
| Queue stuck | Worker crashed | `sudo systemctl restart gajipro-queue` |
| Email not sending | SMTP config salah | Test via `php artisan tinker`: `Mail::raw('test', fn($m) => $m->to('x@y.com')->subject('test'));` |
| Storage full | Logs/backups membengkak | Clean old backups, rotate logs |
| CSRF token mismatch | Session/cache issue | `php artisan cache:clear && php artisan config:cache` |
| Face verification error | Camera permission | HTTPS required, check browser permissions |

### 13.2 Emergency Commands

```bash
# Enable maintenance mode
php artisan down --message="Sistem sedang maintenance"

# Check queue status
php artisan queue:work --once  # Run 1 job

# Retry all failed jobs
php artisan queue:retry all

# Clear all caches (nuclear option)
php artisan optimize:clear

# Check scheduled tasks
php artisan schedule:list

# Tail logs live
tail -f /var/www/gajipro/storage/logs/laravel.log
tail -f /var/log/nginx/gajipro-error.log
```

### 13.3 Performance Issues

```bash
# Check MySQL process list
mysql -u root -p -e "SHOW PROCESSLIST;"

# Check PHP-FPM status
sudo systemctl status php8.3-fpm

# Top processes by CPU/Memory
htop

# Disk I/O
iotop

# Network connections
ss -tunap | grep :443
```

### 13.4 Data Recovery

```bash
# Restore single table from backup
zcat /var/backups/gajipro/db_20260416.sql.gz | mysql -u gajipro_user -p gajipro_production

# Extract single table from full backup
zcat backup.sql.gz | sed -n '/-- Table structure for table `attendances`/,/-- Table structure for table/p' > attendances.sql
```

---

## 14. Handover Documentation

### 14.1 Dokumen yang diserahkan ke Client

- [ ] **Admin Manual** (PDF, Bahasa Indonesia)
  - [ ] Login & first-time setup
  - [ ] Employee management
  - [ ] Attendance management
  - [ ] Leave management
  - [ ] Payroll processing
  - [ ] Tax & BPJS setup
  - [ ] Report generation
- [ ] **Employee Manual** (PDF, ringkas)
  - [ ] Portal login
  - [ ] Clock in/out
  - [ ] Leave request
  - [ ] View payslip
- [ ] **System Admin Manual** (untuk IT client)
  - [ ] Server access
  - [ ] Backup procedure
  - [ ] Restore procedure
  - [ ] Basic troubleshooting
- [ ] **Credential Handover Document** (printed & signed):
  - [ ] Server SSH credentials
  - [ ] Database credentials
  - [ ] Admin application credentials
  - [ ] SMTP credentials
  - [ ] SSL certificate info & renewal date
  - [ ] Backup S3 bucket credentials (jika ada)

### 14.2 Video Tutorial

- [ ] Admin overview (10 menit)
- [ ] Employee basics (5 menit)
- [ ] Payroll processing walkthrough (15 menit)
- [ ] Leave & attendance workflow (5 menit)

### 14.3 Support Channel

- [ ] Email support: `support@[vendor-domain].com`
- [ ] WhatsApp support (office hours)
- [ ] Emergency hotline (untuk critical issue)
- [ ] Shared Google Drive untuk dokumentasi live

### 14.4 Warranty Period (30 Hari)

Selama 30 hari pertama, vendor (kita) handle:
- [ ] Bug fixes (free)
- [ ] Minor UX adjustment
- [ ] Data import assistance
- [ ] Training tambahan (1 sesi)

Di luar scope warranty:
- [ ] Feature baru (change request, biaya terpisah)
- [ ] Third-party integration (project terpisah)
- [ ] Custom report kompleks (biaya per laporan)

### 14.5 Post-Warranty Support Options

Tawarkan kontrak maintenance:
- **Tier 1 (Basic)**: Bug fix + email support, monthly fee
- **Tier 2 (Standard)**: + monthly update + 2h consultation/month
- **Tier 3 (Premium)**: + priority response + quarterly feature update + phone support

---

## Quick Reference Commands

```bash
# Deployment
cd /var/www/gajipro && git pull && composer install --no-dev && npm run build && php artisan migrate --force && php artisan optimize

# Restart everything
sudo systemctl restart php8.3-fpm nginx gajipro-queue

# Check logs
tail -100 /var/www/gajipro/storage/logs/laravel.log
tail -100 /var/log/nginx/gajipro-error.log

# Manual backup now
cd /var/www/gajipro && php artisan backup:run

# Clear cache
cd /var/www/gajipro && php artisan optimize:clear && php artisan optimize

# Create admin user
cd /var/www/gajipro && php artisan make:admin
```

---

## Final Sign-Off

| Item | Status | Tanggal | PIC |
|------|--------|---------|-----|
| Server installed & accessible | ☐ | | |
| Database created & seeded | ☐ | | |
| SSL aktif & auto-renew | ☐ | | |
| Queue worker running | ☐ | | |
| Backup automation works | ☐ | | |
| Monitoring configured | ☐ | | |
| Admin can login | ☐ | | |
| Test employee can login | ☐ | | |
| Smoke tests passed | ☐ | | |
| Documentation delivered | ☐ | | |
| Training completed | ☐ | | |
| Client sign-off | ☐ | | |

---

> **Catatan**: Dokumen ini wajib di-print, signed, dan disimpan sebagai bukti handover. Simpan juga versi digital di Google Drive client + internal repository (encrypted).
