# Sesi 5: Running Flutter Mobile App — Connect ke Backend API

> **Durasi**: 2-3 jam
> **Tanggal**: 15 April 2026 (Minggu 2)
> **Prasyarat**: GajiPro web sudah running & demo data ter-seed (Sesi 4)
> **Tujuan**: Menjalankan Flutter app, memahami API authentication flow, dan testing koneksi BE-FE

---

## Daftar Isi

1. [Arsitektur BE-FE GajiPro](#1-arsitektur-be-fe-gajipro)
2. [API Overview — Semua Endpoint](#2-api-overview--semua-endpoint)
3. [Laravel Sanctum — Cara Kerja Token Auth](#3-laravel-sanctum--cara-kerja-token-auth)
4. [API Login Flow — Step by Step](#4-api-login-flow--step-by-step)
5. [Perbedaan Tenant Context: Web vs API](#5-perbedaan-tenant-context-web-vs-api)
6. [Setup Flutter Project](#6-setup-flutter-project)
7. [Connect Flutter ke Laravel Backend](#7-connect-flutter-ke-laravel-backend)
8. [Testing API dengan cURL & Postman](#8-testing-api-dengan-curl--postman)
9. [Swagger UI — Dokumentasi API Interaktif](#9-swagger-ui--dokumentasi-api-interaktif)
10. [Attendance API — Clock In/Out dengan GPS & Face](#10-attendance-api--clock-inout-dengan-gps--face)
11. [Leave API — Pengajuan Cuti dari Mobile](#11-leave-api--pengajuan-cuti-dari-mobile)
12. [Payslip API — Lihat & Download Slip Gaji](#12-payslip-api--lihat--download-slip-gaji)
13. [Face Recognition — Arsitektur Client-Side](#13-face-recognition--arsitektur-client-side)
14. [Push Notification — Device Token Flow](#14-push-notification--device-token-flow)
15. [Latihan Praktik](#15-latihan-praktik)

---

## 1. Arsitektur BE-FE GajiPro

### Big Picture

```
┌──────────────────────────────────────────────────────┐
│                  FLUTTER MOBILE APP                    │
│          (Employee Self-Service Portal)                │
│                                                        │
│  ┌─────────┐  ┌──────────┐  ┌───────────┐            │
│  │ Login   │  │ Dashboard│  │ Attendance│            │
│  │ Screen  │  │ Screen   │  │ Screen    │            │
│  └────┬────┘  └────┬─────┘  └─────┬─────┘            │
│       │            │              │                    │
│       └────────────┼──────────────┘                    │
│                    │                                   │
│              ┌─────▼──────┐                            │
│              │  HTTP Client│  ← Dio / http package     │
│              │  + Bearer   │                           │
│              │    Token    │                           │
│              └─────┬──────┘                            │
└────────────────────┼───────────────────────────────────┘
                     │
              HTTPS REST API
              JSON Request/Response
                     │
┌────────────────────▼───────────────────────────────────┐
│                LARAVEL BACKEND                          │
│                                                         │
│  ┌──────────────────────────────────────────────┐      │
│  │  API Middleware Pipeline                      │      │
│  │  DetectAttack → CheckBlockedIp → Sanctum Auth │      │
│  └──────────────────────┬───────────────────────┘      │
│                         │                               │
│  ┌──────────────────────▼───────────────────────┐      │
│  │  API Controllers (/api/v1/*)                  │      │
│  │  Auth, Attendance, Leave, Payslip, etc.       │      │
│  └──────────────────────┬───────────────────────┘      │
│                         │                               │
│  ┌──────────────────────▼───────────────────────┐      │
│  │  Eloquent Models + MySQL Database             │      │
│  │  (company_id scoping via $user->company)      │      │
│  └──────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────┘
```

### 2 Interface, 1 Backend

| Aspek | Web Dashboard | Flutter App |
|-------|--------------|-------------|
| User target | Admin, HR, Payroll | Employee (self-service) |
| Rendering | Server-side (Blade) | Client-side (Flutter) |
| Auth method | Session + Cookie | Sanctum Bearer Token |
| Tenant context | `SetTenant` middleware → `app('tenant')` | `$request->user()->company` |
| Data format | HTML views | JSON responses |
| State | Server session | Local storage + state management |

---

## 2. API Overview — Semua Endpoint

### Base URL

```
http://localhost:8000/api/v1/
```

### Endpoint Map

#### 🔐 Authentication (Public)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `POST` | `/auth/login` | Login, dapat Bearer token |
| `POST` | `/auth/demo-register` | Register akun demo |

#### 👤 Profile (Authenticated)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `POST` | `/auth/logout` | Logout, revoke token |
| `GET` | `/auth/profile` | Get profil user + employee |
| `PATCH` | `/auth/profile` | Update profil |
| `POST` | `/auth/change-password` | Ganti password |
| `DELETE` | `/auth/delete-account` | Hapus akun (soft delete 30 hari) |

#### 📊 Dashboard

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/dashboard` | Summary dashboard |
| `GET` | `/dashboard/attendance-chart` | Data chart kehadiran |
| `GET` | `/dashboard/quick-stats` | Stat cards cepat |

#### ⏰ Attendance

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/attendance/today` | Status kehadiran hari ini |
| `POST` | `/attendance/clock-in` | Clock in (GPS + face) |
| `POST` | `/attendance/clock-out` | Clock out |
| `GET` | `/attendance/history` | Riwayat kehadiran |
| `GET` | `/attendance/summary` | Ringkasan bulanan |

#### 🏖️ Leave (Cuti)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/leaves` | Daftar pengajuan cuti |
| `POST` | `/leaves` | Ajukan cuti baru |
| `GET` | `/leaves/balance` | Saldo cuti |
| `GET` | `/leaves/types` | Jenis-jenis cuti |
| `GET` | `/leaves/{id}` | Detail cuti |
| `POST` | `/leaves/{id}/cancel` | Batalkan cuti |

#### ⏱️ Overtime (Lembur)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/overtimes` | Daftar lembur |
| `POST` | `/overtimes` | Ajukan lembur |
| `GET` | `/overtimes/summary` | Ringkasan lembur |
| `GET` | `/overtimes/{id}` | Detail lembur |
| `POST` | `/overtimes/{id}/cancel` | Batalkan lembur |

#### 💰 Payslip (Slip Gaji)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/payslips` | Daftar slip gaji |
| `GET` | `/payslips/summary` | Ringkasan tahunan |
| `GET` | `/payslips/{id}` | Detail slip gaji |
| `GET` | `/payslips/{id}/download` | Download signed URL |
| `GET` | `/payslips/{id}/pdf` | Stream PDF langsung |

#### 📋 Tax Forms (Bukti Potong)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/tax-forms` | Daftar bukti potong 1721-A1 |
| `GET` | `/tax-forms/years` | Tahun yang tersedia |
| `GET` | `/tax-forms/{id}` | Detail bukti potong |
| `GET` | `/tax-forms/{id}/pdf` | Download PDF |

#### 💸 Reimbursement

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/reimbursements` | Daftar reimbursement |
| `POST` | `/reimbursements` | Ajukan reimbursement |
| `GET` | `/reimbursements/categories` | Kategori reimbursement |
| `GET` | `/reimbursements/summary` | Ringkasan |
| `GET` | `/reimbursements/{id}` | Detail |

#### 📢 Announcements (Pengumuman)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/announcements` | Daftar pengumuman |
| `GET` | `/announcements/unread-count` | Jumlah belum dibaca |
| `GET` | `/announcements/{id}` | Detail pengumuman |
| `POST` | `/announcements/{id}/read` | Tandai sudah dibaca |

#### 😊 Face Recognition

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/face-recognition/status` | Status enrollment |
| `POST` | `/face-recognition/enroll` | Daftarkan wajah |
| `POST` | `/face-recognition/verify` | Verifikasi wajah |
| `DELETE` | `/face-recognition/enrollment` | Hapus data wajah |

#### 📱 Device Tokens (Push Notification)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `POST` | `/device-tokens/register` | Register FCM token |
| `DELETE` | `/device-tokens/unregister` | Unregister token |
| `POST` | `/device-tokens/refresh` | Refresh token |

#### ✅ Approvals (Manager Only)

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/approvals/pending` | List pending approval |
| `GET` | `/approvals/history` | Riwayat approval |
| `POST` | `/approvals/leave/{id}/approve` | Approve cuti |
| `POST` | `/approvals/leave/{id}/reject` | Reject cuti |
| `POST` | `/approvals/overtime/{id}/approve` | Approve lembur |
| `POST` | `/approvals/overtime/{id}/reject` | Reject lembur |
| `POST` | `/approvals/reimbursement/{id}/approve` | Approve reimburse |
| `POST` | `/approvals/reimbursement/{id}/reject` | Reject reimburse |

#### 📍 Office Locations

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/office-locations` | Semua lokasi kantor |
| `GET` | `/office-locations/assigned` | Lokasi yang di-assign ke user |
| `POST` | `/office-locations/validate-gps` | Validasi GPS |

**Total: 55+ API endpoints** untuk fitur employee self-service lengkap!

---

## 3. Laravel Sanctum — Cara Kerja Token Auth

### Apa itu Sanctum?

Sanctum adalah package Laravel untuk API authentication. Dua mode:

| Mode | Digunakan Untuk | Cara Kerja |
|------|-----------------|------------|
| **SPA** | Single Page App (same domain) | Session + Cookie |
| **Token** | Mobile App / Third-party | Bearer Token di header |

GajiPro Flutter app menggunakan **Token mode**.

### Flow Token Authentication

```
┌──────────────┐                    ┌──────────────────┐
│  Flutter App  │                    │  Laravel Backend  │
└──────┬───────┘                    └────────┬─────────┘
       │                                      │
       │  POST /api/v1/auth/login             │
       │  { email, password }                 │
       ├─────────────────────────────────────►│
       │                                      │  1. Validate credentials
       │                                      │  2. Delete old tokens
       │                                      │  3. Create new token
       │                                      │  4. Return token + data
       │◄─────────────────────────────────────┤
       │  { token: "1|abc123...",             │
       │    user: {...},                      │
       │    employee: {...},                  │
       │    company: {...} }                  │
       │                                      │
       │  (Simpan token di secure storage)    │
       │                                      │
       │  GET /api/v1/attendance/today        │
       │  Authorization: Bearer 1|abc123...   │
       ├─────────────────────────────────────►│
       │                                      │  Sanctum validates token
       │                                      │  → Resolve user
       │                                      │  → user->company = tenant
       │◄─────────────────────────────────────┤
       │  { data: { clock_in: "08:00", ...} } │
       │                                      │
       │  POST /api/v1/auth/logout            │
       │  Authorization: Bearer 1|abc123...   │
       ├─────────────────────────────────────►│
       │                                      │  Delete current token
       │◄─────────────────────────────────────┤
       │  { message: "Logged out" }           │
```

### Token Disimpan di Mana?

**Di Laravel (Server):**

```
Tabel: personal_access_tokens
┌────┬─────────┬──────────────┬────────────────────────┐
│ id │ user_id │ name         │ token (hashed)         │
├────┼─────────┼──────────────┼────────────────────────┤
│ 1  │ 5       │ mobile-app   │ sha256_hash_of_token   │
└────┴─────────┴──────────────┴────────────────────────┘
```

**Di Flutter (Client):**
```dart
// Simpan di secure storage (encrypted)
await secureStorage.write(key: 'auth_token', value: '1|abc123...');

// Pasang di setiap request
dio.options.headers['Authorization'] = 'Bearer $token';
```

### Poin Penting

1. **Single session** — Login baru menghapus semua token lama (`$user->tokens()->delete()`)
2. **Token format** — `{id}|{random_string}` (contoh: `1|abc123xyz`)
3. **Server menyimpan hash** — Yang disimpan di DB adalah SHA-256 hash, bukan plain text
4. **Tidak ada expiry default** — Token berlaku sampai di-revoke (logout)

---

## 4. API Login Flow — Step by Step

### Kode Login di Backend

**File:** `app/Http/Controllers/Api/V1/AuthController.php`

```php
public function login(Request $request): JsonResponse
{
    $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    // 1. Cari user dan cek credentials
    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Email atau password salah.',
        ], 401);
    }

    // 2. Cek user aktif
    if (! $user->is_active) {
        return response()->json([
            'message' => 'Akun Anda telah dinonaktifkan.',
        ], 403);
    }

    // 3. Hapus semua token lama (single session)
    $user->tokens()->delete();

    // 4. Buat token baru
    $token = $user->createToken('mobile-app')->plainTextToken;

    // 5. Load employee data
    $employee = $user->employee?->load(['department', 'position']);

    // 6. Return token + user data + employee data + company settings
    return response()->json([
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'employee' => $employee ? [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'full_name' => $employee->full_name,
                'department' => $employee->department?->name,
                'position' => $employee->position?->name,
                'face_enrolled' => $employee->faceEmbedding()->exists(),
                'face_embedding' => $employee->faceEmbedding?->embedding_data,
                'assigned_offices' => $employee->officeLocations->map(fn ($o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'latitude' => $o->latitude,
                    'longitude' => $o->longitude,
                    'radius' => $o->radius,
                ]),
            ] : null,
            'company' => [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'enable_face_recognition' => $user->company->face_recognition_enabled,
                'face_match_threshold' => $user->company->face_match_threshold ?? 0.6,
                'enable_gps_validation' => $user->company->gps_enabled,
            ],
        ],
        'message' => 'Login berhasil.',
    ]);
}
```

### Apa yang Dikembalikan Saat Login?

```json
{
    "data": {
        "token": "1|abc123xyz...",
        "user": {
            "id": 5,
            "name": "Demo Karyawan",
            "email": "karyawan@demo.gajipro.com"
        },
        "employee": {
            "id": 10,
            "employee_id": "EMP20260010",
            "full_name": "Demo Karyawan",
            "department": "Engineering",
            "position": "Staff",
            "face_enrolled": true,
            "face_embedding": [0.123, -0.456, ...],
            "assigned_offices": [
                {
                    "id": 1,
                    "name": "Kantor Pusat Jakarta",
                    "latitude": -6.2088,
                    "longitude": 106.8456,
                    "radius": 200
                }
            ]
        },
        "company": {
            "id": 1,
            "name": "PT Demo GajiPro",
            "enable_face_recognition": true,
            "face_match_threshold": 0.6,
            "enable_gps_validation": true
        }
    },
    "message": "Login berhasil."
}
```

### Kenapa Data Sebanyak Itu di Login Response?

**Optimasi untuk mobile!** Satu request login langsung dapat:

| Data | Kenapa? |
|------|---------|
| `token` | Untuk auth semua request selanjutnya |
| `user` | Info akun |
| `employee` | Info karyawan (nama, dept, jabatan) |
| `face_embedding` | Untuk face verification di device (offline) |
| `assigned_offices` | Untuk GPS validation di device (offline) |
| `company settings` | Untuk tahu fitur apa yang aktif |

Tanpa ini, Flutter harus buat 4-5 request terpisah setelah login!

---

## 5. Perbedaan Tenant Context: Web vs API

### Ini Penting untuk Dipahami!

| Aspek | Web (Blade) | API (Flutter) |
|-------|-------------|---------------|
| **Middleware** | `SetTenant` → `app('tenant')` | TIDAK ada SetTenant |
| **Resolve company** | `app('tenant')` | `$request->user()->company` |
| **Subscription check** | Di middleware (redirect) | Di controller (JSON error) |
| **Permission team** | `setPermissionsTeamId()` di middleware | Manual di controller |

### Kenapa API Tidak Pakai SetTenant?

```php
// SetTenant middleware melakukan redirect:
if (! $company->isSubscriptionActive()) {
    return redirect('/subscription-expired');  // ← Ini HTML redirect!
}

// API tidak bisa redirect ke halaman HTML!
// API harus return JSON:
return response()->json(['message' => 'Subscription expired'], 403);
```

### Cara API Controller Mengakses Tenant

```php
// Di WEB controller:
$tenant = app('tenant');
$employees = Employee::where('company_id', $tenant->id)->get();

// Di API controller:
$user = $request->user();
$company = $user->company;
$employee = $user->employee;

// Query tetap di-scope ke company:
$attendances = Attendance::where('company_id', $company->id)
    ->where('employee_id', $employee->id)
    ->get();
```

---

## 6. Setup Flutter Project

### Clone Repository

```bash
# Clone Flutter project
git clone <flutter-repository-url> flutter_jagogajian_app
cd flutter_jagogajian_app
```

### Install Dependencies

```bash
# Get Flutter dependencies
flutter pub get

# Cek apakah Flutter SDK OK
flutter doctor
```

### Konfigurasi API Base URL

Cari file konfigurasi (biasanya di `lib/core/constants/` atau `lib/config/`):

```dart
// Contoh konfigurasi
class AppConfig {
  // Development
  static const String baseUrl = 'http://10.0.2.2:8000/api/v1';
  // 10.0.2.2 = localhost dari Android Emulator

  // Atau jika pakai device fisik (ganti dengan IP komputer):
  // static const String baseUrl = 'http://192.168.1.100:8000/api/v1';

  // Production
  // static const String baseUrl = 'https://gajipro.jagoflutter.com/api/v1';
}
```

### Penting: IP Address untuk Emulator vs Device

| Platform | Cara Akses localhost Laravel |
|----------|----------------------------|
| Android Emulator | `10.0.2.2:8000` (alias untuk host machine) |
| iOS Simulator | `localhost:8000` atau `127.0.0.1:8000` |
| Device Fisik (WiFi) | IP LAN komputer, contoh `192.168.1.100:8000` |

```bash
# Cek IP komputer kamu
# macOS:
ifconfig | grep "inet " | grep -v 127.0.0.1

# Linux:
ip addr show | grep "inet " | grep -v 127.0.0.1

# Windows:
ipconfig | findstr /i "IPv4"
```

### Jalankan Flutter App

```bash
# Pastikan emulator/device sudah connected
flutter devices

# Run app
flutter run

# Atau pilih device specific
flutter run -d chrome    # Web
flutter run -d emulator  # Android Emulator
```

---

## 7. Connect Flutter ke Laravel Backend

### Pastikan Backend Running

```bash
# Terminal 1: Jalankan Laravel
cd ultimate-jagogaji-system
php artisan serve --host=0.0.0.0 --port=8000
# Note: --host=0.0.0.0 agar bisa diakses dari device/emulator
```

### Test Koneksi Dasar

Dari terminal lain, test apakah API accessible:

```bash
# Test dari komputer
curl http://localhost:8000/api/v1/auth/login \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"karyawan@demo.gajipro.com","password":"password"}'
```

Expected response:

```json
{
    "data": {
        "token": "1|xxxxx...",
        "user": { "id": ..., "name": "Demo Karyawan", ... },
        "employee": { ... },
        "company": { ... }
    },
    "message": "Login berhasil."
}
```

### Arsitektur Flutter (Clean Architecture + BLoC)

```
lib/
├── core/
│   ├── constants/       # API URLs, app config
│   ├── error/           # Failure classes
│   └── network/         # Dio client, interceptors
├── features/
│   ├── auth/
│   │   ├── data/        # Repository impl, data sources
│   │   ├── domain/      # Entities, use cases, repository interface
│   │   └── presentation/# BLoC, screens, widgets
│   ├── attendance/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   ├── leave/
│   ├── payslip/
│   └── ...
```

### Contoh HTTP Client Setup (Dio)

```dart
class ApiClient {
  late final Dio _dio;

  ApiClient() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConfig.baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));

    // Interceptor untuk auto-attach token
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await SecureStorage.getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Token expired/invalid → logout
          // Navigate to login screen
        }
        handler.next(error);
      },
    ));
  }
}
```

---

## 8. Testing API dengan cURL & Postman

### cURL: Login

```bash
# Login
TOKEN=$(curl -s http://localhost:8000/api/v1/auth/login \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"karyawan@demo.gajipro.com","password":"password"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

echo "Token: $TOKEN"
```

### cURL: Authenticated Requests

```bash
# Dashboard
curl -s http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool

# Attendance hari ini
curl -s http://localhost:8000/api/v1/attendance/today \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool

# Saldo cuti
curl -s http://localhost:8000/api/v1/leaves/balance \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool

# Daftar payslip
curl -s http://localhost:8000/api/v1/payslips \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool
```

### cURL: Clock In

```bash
# Clock In dengan GPS
curl -s http://localhost:8000/api/v1/attendance/clock-in \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "notes": "Clock in dari cURL test"
  }' | python3 -m json.tool
```

### cURL: Ajukan Cuti

```bash
# Ajukan cuti
curl -s http://localhost:8000/api/v1/leaves \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "leave_type_id": 1,
    "start_date": "2026-04-20",
    "end_date": "2026-04-21",
    "reason": "Keperluan keluarga"
  }' | python3 -m json.tool
```

### cURL: Logout

```bash
curl -s http://localhost:8000/api/v1/auth/logout \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool
```

### Postman Collection

Import endpoint-endpoint di atas ke Postman:

1. **Create Environment**: `GajiPro Local` dengan variable `base_url` = `http://localhost:8000/api/v1`
2. **Login request**: POST `{{base_url}}/auth/login`
3. **Set token otomatis** di Tests tab login:
   ```javascript
   var jsonData = pm.response.json();
   pm.environment.set("token", jsonData.data.token);
   ```
4. **Collection auth**: Bearer Token = `{{token}}`

---

## 9. Swagger UI — Dokumentasi API Interaktif

### Akses Swagger

Buka browser: **http://localhost:8000/api/documentation**

### Fitur Swagger UI

1. **Browse semua endpoint** — Grouped by tag (Auth, Attendance, Leave, dll.)
2. **Try it out** — Kirim request langsung dari browser
3. **Auth** — Klik "Authorize" dan masukkan Bearer token
4. **Request/Response schema** — Lihat format data yang diharapkan

### Generate/Update Swagger Docs

```bash
php artisan l5-swagger:generate
```

### Catatan

Semua API controller punya OpenAPI PHP 8 attributes:

```php
#[OA\Post(
    path: '/auth/login',
    summary: 'Login user',
    tags: ['Authentication'],
    // ... request body, responses
)]
public function login(Request $request): JsonResponse
{
    // ...
}
```

---

## 10. Attendance API — Clock In/Out dengan GPS & Face

### Flow Clock In dari Flutter

```
Flutter App                          Laravel API
    │                                    │
    │  1. Get GPS coordinates            │
    │     (Geolocator plugin)            │
    │                                    │
    │  2. Capture face (optional)        │
    │     (Camera + MLKit/TFLite)        │
    │                                    │
    │  3. Local face verification        │
    │     (Compare dengan cached         │
    │      embedding dari login)         │
    │                                    │
    │  POST /attendance/clock-in         │
    │  { latitude, longitude,            │
    │    face_descriptors, notes }       │
    ├───────────────────────────────────►│
    │                                    │  4. Validate GPS
    │                                    │     (GpsValidationService)
    │                                    │     - Cek dalam radius office?
    │                                    │
    │                                    │  5. Validate face (optional)
    │                                    │     (FaceRecognitionService)
    │                                    │     - Compare embedding
    │                                    │
    │                                    │  6. Record attendance
    │                                    │     - Set status (on_time/late)
    │                                    │     - Calculate late_minutes
    │                                    │
    │◄───────────────────────────────────┤
    │  { data: {                         │
    │      id: 123,                      │
    │      date: "2026-04-15",           │
    │      clock_in: "08:05",            │
    │      status: "late",               │
    │      late_minutes: 5,              │
    │      office_location: "Kantor..."  │
    │  }}                                │
```

### GPS Validation

```
Office Location: lat=-6.2088, lng=106.8456, radius=200m

User GPS: lat=-6.2090, lng=106.8460

Jarak = haversine(office, user) = 35m

35m < 200m → ✅ Valid!
35m > 200m → ❌ "Anda berada di luar area kantor"
```

### Status Kehadiran

| Status | Kondisi |
|--------|---------|
| `present` / `on_time` | Clock in ≤ schedule start time |
| `late` | Clock in > schedule start time |
| `early_leave` | Clock out < schedule end time |
| `absent` | Tidak clock in sama sekali |
| `on_leave` | Ada approved leave request |

---

## 11. Leave API — Pengajuan Cuti dari Mobile

### Flow Pengajuan Cuti

```
1. GET /leaves/types        → Ambil jenis cuti (Annual, Sick, dll.)
2. GET /leaves/balance      → Cek saldo cuti
3. POST /leaves             → Submit pengajuan
4. GET /leaves              → Lihat status (pending/approved/rejected)
5. POST /leaves/{id}/cancel → Batalkan jika masih pending
```

### Validasi di Backend

```php
// Validasi saat create leave request:
// 1. start_date harus >= hari ini
// 2. end_date harus >= start_date
// 3. Cek saldo: entitled_days - used_days - pending_days >= total_days
// 4. Cek overlap: tidak boleh ada leave lain di tanggal yang sama
// 5. Half-day: is_half_day = true → hitung 0.5 hari
```

### Response Format

```json
{
    "data": {
        "id": 15,
        "leave_type": "Cuti Tahunan",
        "start_date": "2026-04-20",
        "end_date": "2026-04-21",
        "total_days": 2,
        "reason": "Keperluan keluarga",
        "status": "pending",
        "created_at": "2026-04-15T10:30:00"
    },
    "message": "Pengajuan cuti berhasil."
}
```

---

## 12. Payslip API — Lihat & Download Slip Gaji

### Flow di Flutter

```
1. GET /payslips              → List slip gaji (paginated)
2. GET /payslips/{id}         → Detail lengkap (earnings, deductions)
3. GET /payslips/{id}/download → Dapat signed URL
4. Buka signed URL di WebView atau download
```

### Detail Payslip Response

```json
{
    "data": {
        "id": 42,
        "payroll_number": "PAY20260301",
        "period": "Maret 2026",
        "status": "paid",
        "paid_at": "2026-03-28",
        "earnings": [
            { "name": "Gaji Pokok", "amount": 10000000 },
            { "name": "Tunjangan Transport", "amount": 500000 },
            { "name": "Tunjangan Makan", "amount": 500000 }
        ],
        "deductions": [
            { "name": "BPJS Kesehatan", "amount": 100000 },
            { "name": "BPJS TK - JHT", "amount": 200000 },
            { "name": "PPh 21", "amount": 250000 }
        ],
        "gross_salary": 11000000,
        "total_deductions": 550000,
        "net_salary": 10450000
    }
}
```

### PDF Download — Signed URL Pattern

```php
// Backend generate signed URL:
$token = hash('sha256', $payslip->id . config('app.key'));
$url = url("/api/v1/payslips/{$payslip->id}/pdf?token={$token}");

// Flutter download:
// 1. GET /payslips/{id}/download → { "url": "https://...?token=xxx" }
// 2. Buka URL di WebView atau download dengan Dio
```

---

## 13. Face Recognition — Arsitektur Client-Side

### Kenapa Client-Side?

```
❌ Server-side: Upload foto → Server compare → Response
   - Butuh upload gambar besar (1-5MB per request)
   - Latency tinggi
   - Server compute intensif

✅ Client-side: Capture face → Compare lokal → Kirim result
   - Face embedding sudah di-cache dari login (512 bytes)
   - Perbandingan di device (< 100ms)
   - Hemat bandwidth
   - Bisa offline verification
```

### Flow Lengkap

```
ENROLLMENT (Pertama kali):
1. Flutter capture wajah
2. MLKit/TFLite extract face embedding (128/192 float array)
3. POST /face-recognition/enroll { embedding_data: [...] }
4. Server simpan di employee_face_embeddings table

LOGIN (Setiap kali):
1. POST /auth/login
2. Response includes face_embedding: [0.123, -0.456, ...]
3. Flutter cache embedding di secure storage

CLOCK IN (Setiap hari):
1. Flutter capture live face
2. Extract live embedding di device
3. Compare dengan cached embedding (cosine similarity)
4. Jika similarity > threshold (0.6) → match!
5. POST /attendance/clock-in (kirim face_descriptors sebagai bukti)
```

### Dimensi Embedding

| Library | Dimensi | Platform |
|---------|---------|----------|
| face-api.js | 128 floats | Web/fallback |
| MobileFaceNet (TFLite) | 192 floats | Mobile (recommended) |

---

## 14. Push Notification — Device Token Flow

### Flow FCM (Firebase Cloud Messaging)

```
┌──────────┐     ┌──────────┐     ┌──────────┐
│  Flutter  │     │  Laravel  │     │   FCM    │
│   App     │     │  Backend  │     │  Server  │
└────┬─────┘     └────┬─────┘     └────┬─────┘
     │                 │                 │
     │  1. Get FCM token from Firebase   │
     │◄──────────────────────────────────┤
     │                 │                 │
     │  2. POST /device-tokens/register  │
     │  { token, platform, device_name } │
     ├────────────────►│                 │
     │                 │  Save to DB     │
     │                 │                 │
     │    ... waktu berlalu ...          │
     │                 │                 │
     │                 │  3. Event terjadi (cuti diapprove)
     │                 │  → Send push notification
     │                 ├────────────────►│
     │                 │  FCM message    │
     │                 │                 │
     │  4. Receive push notification     │
     │◄──────────────────────────────────┤
     │  "Cuti Anda telah disetujui"      │
```

### Register Token

```bash
# Setelah login, register FCM token
curl -s http://localhost:8000/api/v1/device-tokens/register \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "fcm_token_from_firebase...",
    "platform": "android",
    "device_name": "Samsung Galaxy S24",
    "device_model": "SM-S921B",
    "app_version": "1.0.0"
  }'
```

### Kapan Token Di-refresh?

```dart
// Firebase bisa rotate FCM token kapan saja
FirebaseMessaging.instance.onTokenRefresh.listen((newToken) async {
  await apiClient.post('/device-tokens/refresh', data: {
    'old_token': currentToken,
    'new_token': newToken,
  });
});
```

---

## 15. Latihan Praktik

### Latihan 1: API Login via cURL (10 menit)

Pastikan backend running, lalu:

```bash
# 1. Login dan simpan token
curl -s http://localhost:8000/api/v1/auth/login \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"karyawan@demo.gajipro.com","password":"password"}'

# 2. Copy token dari response

# 3. Panggil dashboard API
curl -s http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Pertanyaan:**
- Apa yang terjadi jika token salah? (Coba kirim token "invalid")
- Apa yang terjadi jika tidak kirim header Authorization?

### Latihan 2: Eksplorasi Semua Employee Endpoint (15 menit)

Login sebagai `karyawan@demo.gajipro.com`, lalu panggil:

1. `GET /attendance/today` — Apa status hari ini?
2. `GET /leaves/balance` — Berapa saldo cuti?
3. `GET /leaves/types` — Jenis cuti apa saja?
4. `GET /payslips` — Ada slip gaji tidak?
5. `GET /announcements` — Ada pengumuman?
6. `GET /office-locations/assigned` — Lokasi kantor mana?

### Latihan 3: Clock In via API (15 menit)

```bash
# Clock in
curl -s http://localhost:8000/api/v1/attendance/clock-in \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456
  }'

# Cek attendance hari ini
curl -s http://localhost:8000/api/v1/attendance/today \
  -H "Authorization: Bearer $TOKEN"

# Clock out
curl -s http://localhost:8000/api/v1/attendance/clock-out \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456
  }'
```

**Pertanyaan:**
- Apa yang terjadi jika clock in 2x? (Coba!)
- Apa yang terjadi jika GPS jauh dari kantor?

### Latihan 4: Ajukan Cuti via API (15 menit)

```bash
# 1. Cek jenis cuti
curl -s http://localhost:8000/api/v1/leaves/types \
  -H "Authorization: Bearer $TOKEN"

# 2. Cek saldo
curl -s http://localhost:8000/api/v1/leaves/balance \
  -H "Authorization: Bearer $TOKEN"

# 3. Ajukan cuti (ganti leave_type_id sesuai response #1)
curl -s http://localhost:8000/api/v1/leaves \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "leave_type_id": 1,
    "start_date": "2026-04-20",
    "end_date": "2026-04-20",
    "reason": "Test dari latihan sesi 5"
  }'

# 4. Cek daftar cuti
curl -s http://localhost:8000/api/v1/leaves \
  -H "Authorization: Bearer $TOKEN"
```

### Latihan 5: Buka Swagger & Test (10 menit)

1. Buka **http://localhost:8000/api/documentation**
2. Klik **Authorize** → Masukkan token (tanpa "Bearer " prefix)
3. Coba endpoint `GET /attendance/today`:
   - Klik **Try it out**
   - Klik **Execute**
   - Lihat response

### Latihan 6: Jalankan Flutter App (20 menit)

1. Setup Flutter project
2. Konfigurasi base URL ke `http://10.0.2.2:8000/api/v1` (Android Emulator)
3. Run app: `flutter run`
4. Login dengan `karyawan@demo.gajipro.com` / `password`
5. Eksplorasi fitur yang tersedia

**Jika Flutter belum setup**, fokus latihan 1-5 (cURL/Postman).

---

## Rangkuman Sesi 5

### Apa yang Sudah Dipelajari

| Topik | Key Takeaway |
|-------|-------------|
| Arsitektur BE-FE | Web = Blade (server-side), Mobile = Flutter + API (client-side) |
| API Endpoints | 55+ endpoint di `/api/v1/` untuk semua fitur employee portal |
| Sanctum Auth | Token-based auth, single session, token di header setiap request |
| Login Response | Token + user + employee + company settings (optimasi 1 request) |
| Tenant di API | `$request->user()->company` (bukan `app('tenant')`) |
| Attendance | GPS validation + face recognition (client-side) |
| Face Recognition | Embedding di-cache dari login, compare di device, hemat bandwidth |
| Push Notification | FCM token → register → server kirim via Firebase |
| Swagger | `/api/documentation` untuk browse & test API interaktif |

### Poin Arsitektural Penting

```
🔑 API GajiPro dirancang untuk MOBILE-FIRST:

1. Login response LENGKAP — minimasi round-trip
2. Face verification CLIENT-SIDE — hemat bandwidth
3. GPS validation BISA client-side — data office dari login
4. Single session — 1 device aktif per user
5. Signed URL untuk PDF — secure file download
```

### Preview Sesi 6

Di sesi berikutnya kita akan:
- Testing **multi-company scenario** secara mendalam
- Membuat 2+ tenant dan verifikasi **data isolation**
- Test **cross-tenant security** (akses data perusahaan lain)
- Memahami **approval workflow** lintas role

---

> **Catatan Instruktur:** Pastikan semua peserta berhasil melakukan API call via cURL minimal untuk login dan satu endpoint lainnya. Jika Flutter belum tersedia, tidak apa-apa — sesi ini fokus pada pemahaman API, bukan Flutter development.
