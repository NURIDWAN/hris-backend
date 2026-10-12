# Sesi 6: SaaS Subscription & Payment Gateway (Xendit)

> **Durasi**: 2-3 jam
> **Tanggal**: 16 April 2026 (Minggu 2)
> **Prasyarat**: GajiPro running, paham multi-tenant (Sesi 2-5), punya email aktif untuk daftar Xendit
> **Tujuan**: Paham arsitektur SaaS billing GajiPro, mampu setup Xendit sandbox, dan menguji end-to-end flow pembayaran subscription sampai aktif lagi.

---

## Daftar Isi

1. [Refresher Multi-Tenant (Tipis)](#1-refresher-multi-tenant-tipis)
2. [Konsep SaaS Billing di GajiPro](#2-konsep-saas-billing-di-gajipro)
3. [Arsitektur Payment Gateway di GajiPro](#3-arsitektur-payment-gateway-di-gajipro)
4. [Kenapa Xendit?](#4-kenapa-xendit)
5. [Setup Xendit Sandbox Step-by-Step](#5-setup-xendit-sandbox-step-by-step)
6. [Flow Invoice Xendit dari A sampai Z](#6-flow-invoice-xendit-dari-a-sampai-z)
7. [Webhook Setup dengan ngrok](#7-webhook-setup-dengan-ngrok)
8. [Security: Callback Token Verification](#8-security-callback-token-verification)
9. [Test End-to-End: Upgrade Plan → Bayar → Aktif](#9-test-end-to-end-upgrade-plan--bayar--aktif)
10. [Troubleshooting yang Sering Terjadi](#10-troubleshooting-yang-sering-terjadi)
11. [Production Checklist](#11-production-checklist)
12. [Latihan Praktik](#12-latihan-praktik)

---

## 1. Refresher Multi-Tenant (Tipis)

Kita sudah bahas multi-tenant di sesi sebelumnya. Recap singkat untuk konteks billing:

```
1 Company = 1 Tenant = 1 Subscription
    │
    ├── SubscriptionPlan (Free / Starter / Pro / Enterprise)
    ├── Invoice (tagihan bulanan/tahunan)
    └── Payment (riwayat pembayaran)
```

### Middleware `SetTenant` + Subscription Check

File: `app/Http/Middleware/SetTenant.php`

```php
// Setiap request web akan dicek:
if (! $company->isSubscriptionActive()) {
    return redirect('/subscription-expired');
}
```

**Artinya**: Kalau subscription expired → user otomatis redirect ke halaman `/subscription-expired`. Tidak bisa akses fitur apa-apa kecuali halaman billing.

> **Key takeaway**: Subscription status = kunci akses aplikasi. Ini yang bikin SaaS jadi "SaaS" — bayar untuk akses.

---

## 2. Konsep SaaS Billing di GajiPro

### 2.1 Entitas Billing

| Tabel | Fungsi |
|-------|--------|
| `subscription_plans` | Katalog paket (Free, Starter, Pro, Enterprise) |
| `subscriptions` | Instance langganan milik suatu company (status, tanggal mulai/akhir) |
| `invoices` | Tagihan yang di-issue untuk subscription |
| `payments` | Bukti pembayaran terhadap invoice (lewat gateway) |
| `payment_gateway_settings` | Konfigurasi gateway (Xendit key, Midtrans key, dll) |

### 2.2 Plan yang Sudah Ter-seed

Lihat `database/seeders/DemoBillingSeeder.php`:

| Plan | Harga Bulanan | Harga Tahunan | Max Karyawan |
|------|--------------|---------------|--------------|
| **Free** | Rp 0 | Rp 0 | 5 |
| **Starter** | Rp 500.000 | Rp 5.000.000 | 25 |
| **Professional** | Rp 1.500.000 | Rp 15.000.000 | 100 |
| **Enterprise** | Rp 3.500.000 | Rp 35.000.000 | Unlimited |

### 2.3 Subscription Lifecycle

```
┌─────────┐  upgrade  ┌─────────┐  pay   ┌─────────┐
│  new    │ ────────> │ pending │ ─────> │ active  │
└─────────┘           └─────────┘        └────┬────┘
                                              │
                              ┌───────────────┼──────────────┐
                              │               │              │
                           expire         cancel         renew/pay
                              │               │              │
                              v               v              │
                        ┌─────────┐    ┌───────────┐         │
                        │ expired │    │ cancelled │         │
                        └─────────┘    └───────────┘         │
                              │                              │
                              └──────────────────────────────┘
```

**Status di code** (`app/Models/Subscription.php`):
- `STATUS_PENDING` — menunggu pembayaran
- `STATUS_ACTIVE` — aktif, bisa akses fitur
- `STATUS_EXPIRED` — expired (middleware redirect)
- `STATUS_CANCELLED` — user batalkan

### 2.4 Billing Cycle

```php
// app/Models/Subscription.php
public const CYCLE_MONTHLY = 'monthly';  // +1 bulan tiap bayar
public const CYCLE_YEARLY = 'yearly';    // +1 tahun tiap bayar
```

Ketika webhook "PAID" masuk → `PaymentGatewayService::extendSubscription()` akan extend `ends_at` sesuai cycle:

```php
// app/Services/PaymentGatewayService.php:236
$newEndsAt = $payment->billing_cycle === 'yearly'
    ? $currentEndsAt->addYear()
    : $currentEndsAt->addMonth();

$subscription->update([
    'status' => 'active',
    'ends_at' => $newEndsAt,
]);
```

---

## 3. Arsitektur Payment Gateway di GajiPro

### 3.1 Component Map

```
┌──────────────────────────────────────────────────────────────┐
│                   USER (Admin Company)                       │
└────────────────────────┬─────────────────────────────────────┘
                         │  POST /settings/billing/upgrade
                         v
┌──────────────────────────────────────────────────────────────┐
│  Settings\BillingController::processUpgrade()                │
│  - Buat Subscription (pending) + Payment (pending)           │
│  - Call PaymentGatewayService::createTransaction()           │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         v
┌──────────────────────────────────────────────────────────────┐
│  Services\PaymentGatewayService                              │
│  - Route ke Xendit OR Midtrans sesuai `gateway`              │
│  - createXenditInvoice() → HTTP POST ke api.xendit.co        │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         v
                  ┌─────────────┐
                  │   XENDIT    │ ← User bayar di hosted page
                  └──────┬──────┘
                         │  Webhook: POST /api/webhooks/xendit
                         v
┌──────────────────────────────────────────────────────────────┐
│  Api\WebhookController::xendit()                             │
│  - Verifikasi X-CALLBACK-TOKEN (hash_equals)                 │
│  - PaymentGatewayService::handleXenditCallback()             │
│    → Update Payment status = success                         │
│    → Extend Subscription ends_at                             │
└──────────────────────────────────────────────────────────────┘
```

### 3.2 File-File Penting yang Harus Dikenal

| File | Fungsi |
|------|--------|
| `app/Models/SubscriptionPlan.php` | Katalog plan |
| `app/Models/Subscription.php` | Instance langganan company |
| `app/Models/Payment.php` | Record pembayaran + auto-generate payment_number |
| `app/Models/PaymentGatewaySetting.php` | Konfigurasi gateway (credentials terenkripsi) |
| `app/Services/PaymentGatewayService.php` | Service utama — Xendit & Midtrans |
| `app/Http/Controllers/Settings/BillingController.php` | Upgrade/cancel dari sisi tenant |
| `app/Http/Controllers/Api/WebhookController.php` | Terima webhook dari gateway |
| `app/Http/Controllers/Superadmin/PlanController.php` | Manage plans (superadmin) |
| `app/Http/Controllers/Superadmin/PaymentGatewayController.php` | Setup gateway (superadmin) |
| `app/Http/Middleware/SetTenant.php` | Block akses jika expired |

### 3.3 Route Map

```php
// routes/web.php

// Tenant side (admin company)
Route::get('billing', [BillingController::class, 'index'])->name('settings.billing.index');
Route::get('billing/upgrade', [BillingController::class, 'upgrade']);
Route::post('billing/upgrade', [BillingController::class, 'processUpgrade']);
Route::post('billing/cancel', [BillingController::class, 'cancel']);

// Superadmin side
Route::resource('plans', PlanController::class);
Route::resource('payment-gateways', PaymentGatewayController::class);
Route::resource('subscriptions', SubscriptionController::class)->only(['index', 'show']);

// routes/api.php - WEBHOOK (public, tanpa auth!)
Route::prefix('webhooks')->middleware('throttle:60,1')->group(function () {
    Route::post('/xendit', [WebhookController::class, 'xendit']);
    Route::post('/midtrans', [WebhookController::class, 'midtrans']);
});
```

> **Catatan**: Webhook tidak butuh auth:sanctum karena yang call adalah server Xendit, bukan user. Security-nya pakai `X-CALLBACK-TOKEN` header.

---

## 4. Kenapa Xendit?

### Xendit vs Midtrans — Ringkas

| Aspek | **Xendit** ⭐ | **Midtrans** |
|-------|--------------|--------------|
| Developer Experience | API modern (mirip Stripe) | Docs bhs Indonesia lengkap |
| Invoice API | Super simple, 1 POST dapat link | Pakai Snap (juga simple) |
| Recurring/Subscription | Recurring Plan API matang | Subscription API terbatas |
| Settlement | T+1 | T+2/T+3 |
| Sandbox | Instant | Perlu verify merchant |
| Fee CC/VA/QRIS | Kompetitif | Kompetitif |

### Alasan Pilih Xendit di GajiPro

1. **Invoice API cleanest** untuk SaaS billing — 1 request bikin invoice, dapat `invoice_url`, user tinggal klik bayar
2. **Settlement cepat** (T+1) — cashflow lebih sehat
3. **Webhook reliable** dengan retry otomatis (kalau server kita down, Xendit retry sampai 24 jam)
4. **Sandbox langsung bisa dipakai** — tidak perlu verify merchant dulu
5. **Semua metode lokal**: VA (BCA, BNI, BRI, Mandiri, Permata), E-wallet (OVO, DANA, LinkAja, ShopeePay), QRIS, Retail (Alfamart, Indomaret), Credit Card

---

## 5. Setup Xendit Sandbox Step-by-Step

### 5.1 Daftar Akun Xendit

1. Buka https://dashboard.xendit.co/register
2. Daftar dengan email aktif
3. Verifikasi email (cek inbox / spam)
4. Login → pastikan berada di mode **Test Mode** (toggle di kanan atas)

### 5.2 Dapatkan API Key

1. Buka menu **Settings → Developers → API Keys**
2. Klik **Generate Secret Key**
3. Pilih permission minimal: `Money-in` (Invoice, QR, VA)
4. Copy Secret Key, contoh:
   ```
   xnd_development_abcDEF123456...
   ```
5. **Simpan baik-baik** — akan dipakai di step berikutnya

### 5.3 Setup Callback Token (Webhook Verification)

1. Buka **Settings → Developers → Webhooks**
2. Pada section **Verification Token**, klik "Generate new token" atau copy yang existing
3. Simpan callback token ini, contoh:
   ```
   hQwxr4T9lKjGmN2...
   ```

> **Penting**: Callback token ini yang akan dicek webhook controller pakai `hash_equals()`. Kalau mismatch → webhook direject. Ini yang mencegah orang fake webhook untuk mark payment sebagai sukses.

### 5.4 Input Credentials ke GajiPro (via Superadmin)

1. Login sebagai Superadmin: http://localhost:8000/superadmin/login
   - Email: `superadmin@gajipro.com`
   - Password: `password`
2. Buka menu **Payment Gateways** (sidebar)
3. Klik **Tambah Gateway** (atau Edit jika sudah ada)
4. Isi form:
   - **Gateway**: `Xendit`
   - **Nama**: `Xendit Sandbox`
   - **Environment**: `Sandbox`
   - **Credentials** (JSON):
     ```json
     {
       "secret_key": "xnd_development_abcDEF123456...",
       "callback_token": "hQwxr4T9lKjGmN2..."
     }
     ```
   - **Is Active**: ✅ ON
5. Simpan.

> **Security note**: Kolom `credentials` di-encrypt otomatis via `'credentials' => 'encrypted:array'` cast di model `PaymentGatewaySetting`. Di database tersimpan encrypted, tidak plain text.

### 5.5 Verifikasi Credentials Tersimpan

```bash
php artisan tinker
```

```php
use App\Models\PaymentGatewaySetting;

$xendit = PaymentGatewaySetting::where('gateway', 'xendit')->first();
dump($xendit->credentials);
// Expected: array ['secret_key' => '...', 'callback_token' => '...']

// Test credential dipakai service
$service = new \App\Services\PaymentGatewayService();
// No error = credentials berhasil di-load
```

---

## 6. Flow Invoice Xendit dari A sampai Z

### 6.1 Urutan Panggilan

```
Step 1: User klik "Upgrade ke Professional"
        → POST /settings/billing/upgrade
        → BillingController::processUpgrade()

Step 2: Controller buat record (semuanya pending)
        → Subscription (status: pending)
        → Payment   (status: pending, payment_number: PAY20260416xxxx)

Step 3: Controller call PaymentGatewayService
        → createXenditInvoice([
            'order_id' => 'PAY20260416xxxx',
            'amount' => 1500000,
            'customer_email' => 'admin@demo.gajipro.com',
            'customer_name' => 'Admin Demo',
          ])

Step 4: Service HTTP POST ke Xendit
        POST https://api.xendit.co/v2/invoices
        Authorization: Basic base64(secret_key:)
        Body:
        {
          "external_id": "PAY20260416xxxx",
          "amount": 1500000,
          "payer_email": "admin@demo.gajipro.com",
          "description": "Langganan Professional (monthly)",
          "invoice_duration": 86400,
          "success_redirect_url": "http://localhost:8000/settings/billing?status=success",
          "failure_redirect_url": "http://localhost:8000/settings/billing?status=failed"
        }

Step 5: Xendit return invoice_url
        {
          "id": "6612abc...",
          "invoice_url": "https://checkout-staging.xendit.co/web/6612abc...",
          "status": "PENDING",
          "amount": 1500000
        }

Step 6: Backend update payment dengan gateway_transaction_id
        → Payment.gateway_transaction_id = "6612abc..."

Step 7: Redirect user ke invoice_url
        → User pilih metode bayar (VA / e-wallet / QRIS / CC)
        → Bayar di simulator Xendit sandbox

Step 8: Xendit kirim webhook ke kita
        POST {YOUR_NGROK_URL}/api/webhooks/xendit
        Headers: X-CALLBACK-TOKEN: hQwxr4T9lKjGmN2...
        Body:
        {
          "id": "6612abc...",
          "external_id": "PAY20260416xxxx",
          "status": "PAID",
          "amount": 1500000,
          "payment_method": "EWALLET",
          ...
        }

Step 9: WebhookController::xendit() verifikasi token → handle callback
        → Payment.status = success
        → Payment.paid_at = now()
        → Subscription.status = active
        → Subscription.ends_at = +1 bulan (sesuai billing_cycle)

Step 10: User di-redirect balik ke /settings/billing?status=success
         → Halaman billing tampilkan "Langganan Aktif sampai 2026-05-16"
```

### 6.2 Simulasi Payment di Xendit Sandbox

Di sandbox, setelah redirect ke invoice_url:

- **Virtual Account**: Xendit kasih nomor VA fake. Klik tombol **Simulate Payment** di pojok kanan halaman.
- **E-wallet**: Pilih OVO/DANA → klik Simulate Success.
- **Credit Card**: Pakai test card `4000 0000 0000 0002` (success) atau `4000 0000 0000 0127` (failed).
- **QRIS**: Scan QR dummy atau klik Simulate.

---

## 7. Webhook Setup dengan ngrok

Karena kita develop di `localhost`, Xendit tidak bisa call `http://localhost:8000/api/webhooks/xendit` (itu localhost-nya Xendit, bukan kita). Solusi: expose localhost ke internet pakai **ngrok**.

### 7.1 Install ngrok

```bash
# Mac (Homebrew)
brew install ngrok/ngrok/ngrok

# Atau download manual dari https://ngrok.com/download
```

### 7.2 Daftar & Auth ngrok (sekali saja)

1. Daftar gratis di https://ngrok.com
2. Dapat authtoken dari dashboard ngrok
3. Jalankan:
   ```bash
   ngrok config add-authtoken YOUR_TOKEN_HERE
   ```

### 7.3 Expose localhost:8000

Di terminal baru (biarkan `php artisan serve` tetap jalan di terminal lain):

```bash
ngrok http 8000
```

Output:
```
Forwarding   https://abc123.ngrok-free.app -> http://localhost:8000
```

**Copy URL https://abc123.ngrok-free.app** — ini URL public ke localhost kita.

### 7.4 Register Webhook URL di Xendit

1. Kembali ke Xendit Dashboard → **Settings → Developers → Webhooks**
2. Pada section **Invoices paid**, isi URL:
   ```
   https://abc123.ngrok-free.app/api/webhooks/xendit
   ```
3. Klik **Test** untuk mengirim test webhook
4. Cek Laravel log:
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Harus muncul:
   ```
   [INFO] Xendit webhook received {"ip":"...","external_id":null}
   ```

> **Catatan**: Saat test dari dashboard, `external_id` biasanya null → webhook akan return 404 "Payment not found". Itu wajar, yang penting webhook **sampai** ke controller.

### 7.5 Event yang Perlu Di-subscribe

Minimal:
- ✅ `invoice.paid` — invoice berhasil dibayar (yang paling penting)
- ✅ `invoice.expired` — invoice expired tanpa dibayar

Untuk GajiPro, dua event ini sudah cukup.

---

## 8. Security: Callback Token Verification

### 8.1 Kenapa Penting?

Webhook URL kita **public** — siapa saja bisa POST ke `/api/webhooks/xendit`. Tanpa verifikasi, orang bisa fake webhook:

```bash
# Attacker kirim fake webhook:
curl -X POST https://abc123.ngrok-free.app/api/webhooks/xendit \
  -d '{"external_id":"PAY20260416xxxx","status":"PAID","amount":99999999}'
# Kalau tidak diverifikasi → payment Rp 0 bisa jadi "sukses"!
```

### 8.2 Verifikasi di GajiPro

File: `app/Http/Controllers/Api/WebhookController.php`

```php
$callbackToken = $request->header('X-CALLBACK-TOKEN');
$expectedToken = $gateway->credentials['callback_token'] ?? null;

if (! hash_equals($expectedToken, $callbackToken ?? '')) {
    Log::warning('Xendit callback token mismatch');
    return response()->json(['message' => 'Invalid callback token'], 401);
}
```

> **Kenapa `hash_equals`, bukan `===`?**
>
> `hash_equals` adalah **timing-safe comparison**. Perbandingan biasa `===` bisa di-attack lewat "timing attack" (attacker ukur berapa lama server respond untuk nebak karakter token satu per satu). `hash_equals` selalu memakan waktu sama untuk semua input.

### 8.3 Test Security

```bash
# 1. Tanpa X-CALLBACK-TOKEN → harus 401
curl -X POST https://abc123.ngrok-free.app/api/webhooks/xendit \
  -H "Content-Type: application/json" \
  -d '{"external_id":"test"}'

# 2. Dengan token salah → harus 401
curl -X POST https://abc123.ngrok-free.app/api/webhooks/xendit \
  -H "Content-Type: application/json" \
  -H "X-CALLBACK-TOKEN: salah123" \
  -d '{"external_id":"test"}'

# 3. Dengan token benar tapi external_id tidak ada → 404
curl -X POST https://abc123.ngrok-free.app/api/webhooks/xendit \
  -H "Content-Type: application/json" \
  -H "X-CALLBACK-TOKEN: hQwxr4T9lKjGmN2..." \
  -d '{"external_id":"PAY20260416-INVALID"}'
```

---

## 9. Test End-to-End: Upgrade Plan → Bayar → Aktif

### 9.1 Prerequisites Checklist

Sebelum mulai, pastikan:

- [ ] `php artisan serve` running di terminal 1
- [ ] `ngrok http 8000` running di terminal 2
- [ ] Laravel log terbuka di terminal 3: `tail -f storage/logs/laravel.log`
- [ ] Xendit Payment Gateway sudah di-setup di Superadmin (langkah 5.4)
- [ ] Webhook URL sudah di-register di Xendit Dashboard (langkah 7.4)
- [ ] Subscription Plans sudah ter-seed (cek di Superadmin → Plans)

### 9.2 Seed Plans (Kalau Belum)

Kalau tabel `subscription_plans` kosong:

```bash
php artisan db:seed --class=DemoBillingSeeder
php artisan db:seed --class=PaymentGatewaySeeder
```

> **Catatan**: Kita akan register seeder ini ke `DatabaseSeeder` agar otomatis jalan saat `migrate:fresh --seed`.

### 9.3 Jalankan Test

1. **Login sebagai admin company**:
   - http://localhost:8000/login
   - Email: `admin@demo.gajipro.com`
   - Password: `password`

2. **Buka halaman billing**:
   - Klik Avatar → **Pengaturan → Billing**
   - Atau langsung: http://localhost:8000/settings/billing
   - Cek: current subscription apa?

3. **Klik Upgrade**:
   - Pilih plan (misal: **Professional - Monthly**)
   - Pilih payment gateway: **Xendit**
   - Klik **Lanjut Bayar**

4. **Di halaman Xendit**:
   - Pilih metode pembayaran (misal: **BCA Virtual Account**)
   - Klik **Simulate Payment** di sandbox
   - Atau pilih **QRIS → Simulate**

5. **Verifikasi webhook masuk**:
   - Di terminal `tail -f storage/logs/laravel.log` harus muncul:
     ```
     [INFO] Xendit webhook received {"external_id":"PAY20260416xxxx"}
     [INFO] Subscription extended {"subscription_id":1,"payment_id":1,"new_ends_at":"2026-05-16 ..."}
     ```

6. **Verifikasi database**:
   ```bash
   php artisan tinker
   ```
   ```php
   use App\Models\Subscription;
   use App\Models\Payment;

   // Cek subscription
   Subscription::latest()->first()->toArray();
   // Expected: status => "active", ends_at => +1 month

   // Cek payment
   Payment::latest()->first()->toArray();
   // Expected: status => "success", paid_at => filled
   ```

7. **Verifikasi akses**:
   - Refresh halaman `/settings/billing`
   - Status harus **Aktif sampai 2026-05-16**
   - Akses fitur lain harus kembali normal

### 9.4 Test Subscription Expired

Simulasi subscription expired:

```bash
php artisan tinker
```

```php
use App\Models\Subscription;

$sub = Subscription::where('company_id', 1)->latest()->first();
$sub->update(['ends_at' => now()->subDay()]);
```

Refresh halaman → harus **redirect ke /subscription-expired** (karena middleware `SetTenant` cek `isSubscriptionActive()`).

Perbaiki kembali:
```php
$sub->update(['ends_at' => now()->addMonth()]);
```

---

## 10. Troubleshooting yang Sering Terjadi

### 10.1 Webhook Tidak Masuk

**Gejala**: User bayar sukses tapi di GajiPro payment masih `pending`.

**Diagnosis:**
```bash
# 1. Cek ngrok masih jalan?
curl http://localhost:4040/api/tunnels
# atau buka http://localhost:4040 (ngrok inspector)

# 2. Cek webhook URL di Xendit Dashboard masih match ngrok URL?
# NOTE: ngrok URL free plan berubah tiap restart!

# 3. Cek Laravel log
tail -f storage/logs/laravel.log
```

**Fix**: ngrok URL berubah? Update webhook URL di Xendit Dashboard.

### 10.2 Webhook 401 "Invalid callback token"

**Gejala**: Log muncul `Xendit callback token mismatch`.

**Fix**:
- Pastikan `callback_token` di Xendit Dashboard sama dengan yang disimpan di `payment_gateway_settings.credentials`
- Check via tinker:
  ```php
  PaymentGatewaySetting::where('gateway', 'xendit')->first()->credentials;
  ```
- Update via Superadmin → Payment Gateways → Edit

### 10.3 "Payment not found"

**Gejala**: Webhook masuk tapi `Payment not found`.

**Penyebab umum**:
- `external_id` di Xendit tidak match `payment_number` di database
- Biasanya karena test webhook dari dashboard (external_id = null)

**Fix**: Aman diabaikan untuk test webhook. Untuk real payment, pastikan `BillingController::processUpgrade` mem-pass `order_id` = `payment_number`.

### 10.4 Invoice URL Tidak Muncul / Error 500

**Gejala**: Klik upgrade → error atau redirect balik tanpa invoice url.

**Diagnosis**:
```bash
tail -f storage/logs/laravel.log
```

Biasanya karena:
- Secret key salah → log: `Xendit invoice creation failed` dengan response Xendit
- Amount 0 → Xendit menolak
- Currency bukan IDR

**Fix**: Cek log response body dari Xendit, biasanya jelas.

### 10.5 Subscription Tidak Extend Walau Payment Success

**Gejala**: Payment status success tapi subscription masih expired.

**Penyebab**: Payment tidak terhubung ke subscription (`subscription_id` null).

**Cek**:
```php
Payment::latest()->first()->subscription_id; // harus not null
```

**Fix**: Di `BillingController::processUpgrade()` pastikan `subscription_id` diset saat create payment. Sudah ada di code:
```php
Payment::create([
    'company_id' => $tenant->id,
    'subscription_id' => $subscription->id,  // ← ini
    ...
]);
```

### 10.6 CSRF Token Mismatch pada Webhook

**Gejala**: Webhook response 419.

**Penyebab**: Webhook ter-include dalam `web` middleware yang punya CSRF.

**Fix** (sudah aman di GajiPro): Webhook ada di `routes/api.php`, yang tidak memakai CSRF by default. Jangan pindah ke `routes/web.php`.

### 10.7 ngrok Warning Page

**Gejala**: ngrok free menampilkan halaman warning sebelum forward request.

**Fix**: Xendit auto-handle header `ngrok-skip-browser-warning`. Kalau masih bermasalah, upgrade ke ngrok paid atau pakai alternatif:
- Cloudflare Tunnel (gratis)
- Tailscale Funnel
- localtunnel

---

## 11. Production Checklist

Sebelum go-live dengan real money:

### 11.1 Xendit Settings
- [ ] Switch ke **Live Mode** di Xendit Dashboard
- [ ] KYC & verifikasi merchant sudah complete
- [ ] Generate **Live Secret Key** (berbeda dari sandbox key)
- [ ] Generate **Live Callback Token** (berbeda dari sandbox)
- [ ] Update webhook URL ke **production domain** (bukan ngrok!)
- [ ] Subscribe event minimal: `invoice.paid`, `invoice.expired`

### 11.2 GajiPro Settings
- [ ] Update `PaymentGatewaySetting`:
  - `environment` = `production`
  - `credentials.secret_key` = live key
  - `credentials.callback_token` = live token
- [ ] APP_URL di `.env` = domain production (untuk `success_redirect_url`)
- [ ] HTTPS enabled di production
- [ ] `APP_DEBUG=false`
- [ ] Queue worker running untuk async tasks

### 11.3 Monitoring
- [ ] Setup alert kalau webhook gagal (3x retry)
- [ ] Monitor `payments.status = pending` yang > 24 jam
- [ ] Daily reconciliation: compare Xendit dashboard vs database
- [ ] Log retention minimal 30 hari

### 11.4 Legal
- [ ] Invoice PDF include: nama PT, NPWP, alamat
- [ ] PPN 11% ditambahkan kalau applicable
- [ ] Kebijakan refund jelas di TOS
- [ ] Tanda bukti pembayaran bisa di-download user

---

## 12. Latihan Praktik

### Latihan 1: Setup Xendit Sandbox (30 menit)

1. Daftar akun Xendit Test Mode
2. Dapatkan secret key & callback token
3. Input ke Superadmin GajiPro
4. Verifikasi via tinker bahwa credentials ter-load

### Latihan 2: Expose Localhost dengan ngrok (15 menit)

1. Install ngrok + auth
2. Run `ngrok http 8000`
3. Copy URL https://xxx.ngrok-free.app
4. Register di Xendit Webhook settings
5. Click "Test" di Xendit → verify log Laravel muncul

### Latihan 3: Buat Invoice Manual via Tinker (20 menit)

```bash
php artisan tinker
```

```php
use App\Services\PaymentGatewayService;

$service = new PaymentGatewayService();
$service->setGateway('xendit');

$result = $service->createTransaction([
    'order_id' => 'MANUAL-TEST-' . time(),
    'amount' => 50000,
    'customer_name' => 'Test User',
    'customer_email' => 'test@example.com',
    'description' => 'Test invoice manual',
]);

dd($result);
// Expected: ['invoice_id' => '...', 'invoice_url' => 'https://checkout-staging.xendit.co/...']
```

Buka `invoice_url` di browser → simulate payment → cek log webhook masuk.

### Latihan 4: Test E2E dari UI (30 menit)

Ikuti Section 9.3 step-by-step. Dokumentasikan:
- Screenshot halaman billing before/after
- Screenshot Xendit checkout
- Screenshot log webhook
- Screenshot database subscription status

### Latihan 5: Trigger Subscription Expired (15 menit)

Ikuti Section 9.4. Tujuan: paham bahwa middleware `SetTenant` yang menjaga akses berdasarkan status subscription.

### Latihan 6: Tambah Seeder ke DatabaseSeeder (15 menit)

Tambahkan di `database/seeders/DatabaseSeeder.php` sebelum baris terakhir:

```php
// Register di dalam run() method
$this->call(DemoBillingSeeder::class);
$this->call(PaymentGatewaySeeder::class);
```

Lalu:
```bash
php artisan migrate:fresh --seed
```

Verifikasi:
```bash
php artisan tinker
```
```php
\App\Models\SubscriptionPlan::count(); // expected: 4
\App\Models\PaymentGatewaySetting::count(); // expected: > 0
```

### Latihan 7 (Challenge): Tulis Pest Test untuk Webhook (30 menit)

Buat test:

```bash
php artisan make:test --pest Api/WebhookXenditTest
```

Test cases:

```php
it('rejects webhook without callback token', function () {
    $response = $this->postJson('/api/webhooks/xendit', [
        'external_id' => 'PAY-TEST',
    ]);
    $response->assertStatus(401);
});

it('rejects webhook with wrong callback token', function () {
    PaymentGatewaySetting::factory()->xendit()->active()->create([
        'credentials' => ['secret_key' => 'x', 'callback_token' => 'valid-token'],
    ]);

    $response = $this->postJson('/api/webhooks/xendit', [
        'external_id' => 'PAY-TEST',
    ], [
        'X-CALLBACK-TOKEN' => 'wrong-token',
    ]);

    $response->assertStatus(401);
});

it('marks payment as success when valid webhook received', function () {
    // ... setup gateway, subscription, payment pending
    // ... POST webhook dengan valid token
    // ... assert payment status success + subscription extended
});
```

Run:
```bash
php artisan test --compact --filter=WebhookXendit
```

---

## Rangkuman Sesi 6

### Yang Sudah Dipelajari

| Topik | Key Takeaway |
|-------|-------------|
| SaaS Billing | Company = tenant, tiap tenant punya subscription & riwayat payment |
| Subscription Lifecycle | pending → active → expired/cancelled (+ renewal) |
| Arsitektur PG | Controller → Service → Gateway → Webhook → Extend Sub |
| Xendit Setup | API key + Callback token, simpan di PaymentGatewaySetting (encrypted) |
| ngrok | Expose localhost agar Xendit bisa call webhook |
| Security | `hash_equals()` untuk cek callback token (timing-safe) |
| Middleware Guard | `SetTenant` block akses kalau subscription expired |
| E2E Flow | Upgrade → bayar → webhook → Payment success + Subscription extended |

### 3 Golden Rules Payment Gateway

```
🔒 Rule 1: JANGAN pernah trust webhook tanpa verifikasi signature/token
   → hash_equals() is your friend

🔒 Rule 2: Status payment HANYA diubah via webhook, BUKAN dari redirect
   → Redirect success bisa di-fake, webhook signed tidak

🔒 Rule 3: Idempotency — webhook bisa datang berkali-kali
   → Pastikan proses double webhook tidak double-extend subscription
```

### Mindset Developer SaaS Billing

```
Setiap kali menulis integrasi PG, tanya:
"Apakah saya verify signature/token dari gateway?"

Setiap kali proses webhook, tanya:
"Apakah logic ini idempotent (aman kalau double-call)?"

Setiap kali handle status payment, tanya:
"Apa yang terjadi kalau webhook gagal / terlambat?"

Setiap kali deploy, tanya:
"Production credentials beda dari sandbox, sudah di-update?"
```

---

## Recap Minggu 2 (Sesi 4-6)

| Sesi | Fokus | Hasil |
|------|-------|-------|
| **Sesi 4** | Running GajiPro Web | Bisa jalankan app, paham login flow |
| **Sesi 5** | Flutter Mobile App | Paham API auth + integrasi mobile |
| **Sesi 6** | SaaS Billing + Xendit | Paham subscription lifecycle + E2E payment gateway |

### Preview Minggu 3

Minggu depan: **Hands-on Development** — menambah fitur baru pakai TDD:
- Implementasi WFH/WFA (lihat `docs/WFH_WFA_IMPLEMENTATION_TASKS.md`)
- Migration → Model → Controller → View → Test (full stack)
- Code review + best practices

---

## Referensi Tambahan

- **Xendit Docs**: https://docs.xendit.co/xendit-api-old-version/indonesia
- **Xendit PHP SDK**: https://github.com/xendit/xendit-php (alternatif dari Http::)
- **ngrok Docs**: https://ngrok.com/docs
- **Midtrans vs Xendit**: tabel lengkap di `docs/analisis/talenta-comparison.md`
- **Multi-Tenant Testing (Appendix)**: `materi/references/multi-tenant-testing.md`
- **System Analysis**: `docs/JAGOGAJI_SYSTEM_ANALYSIS.md`

---

> **Catatan Instruktur**:
> - Pastikan semua peserta **sudah register akun Xendit** sebelum sesi mulai (bisa dijadikan PR)
> - Demonstrasi ngrok + webhook paling impactful di depan layar — peserta bisa langsung ikuti
> - Tekankan **security** (callback token) — ini bedanya developer amatir vs pro
> - Jangan lupa bahas **idempotency** di Latihan 7 — konsep penting untuk webhook handling
