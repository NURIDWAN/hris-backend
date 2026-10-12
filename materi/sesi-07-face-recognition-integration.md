# Sesi 7: Face Recognition Integration — Setup & Konfigurasi

> **Durasi**: 2-3 jam
> **Tanggal**: 21 April 2026 (Minggu 3)
> **Prasyarat**: GajiPro web & Flutter app running (Sesi 4 & 5), paham API flow & tenant context (Sesi 5 & 6)
> **Tujuan**: Memahami arsitektur face recognition di GajiPro, cara kerja face embedding & cosine similarity, setup enrollment via web admin & mobile app, serta testing verifikasi wajah saat absensi

---

## Daftar Isi

1. [Kenapa Face Recognition di Absensi?](#1-kenapa-face-recognition-di-absensi)
2. [Arsitektur Face Recognition GajiPro](#2-arsitektur-face-recognition-gajipro)
3. [Database Schema — Tabel & Kolom](#3-database-schema--tabel--kolom)
4. [Konsep Face Embedding & Cosine Similarity](#4-konsep-face-embedding--cosine-similarity)
5. [Company Settings — Konfigurasi per Tenant](#5-company-settings--konfigurasi-per-tenant)
6. [Service Layer — FaceRecognitionService](#6-service-layer--facerecognitionservice)
7. [Web Admin — Face Enrollment Management](#7-web-admin--face-enrollment-management)
8. [API Endpoints — Mobile Integration](#8-api-endpoints--mobile-integration)
9. [Dua Mode Verifikasi: Client-Side vs Server-Side](#9-dua-mode-verifikasi-client-side-vs-server-side)
10. [Integrasi dengan Attendance (Clock In/Out)](#10-integrasi-dengan-attendance-clock-inout)
11. [Employee Portal — Face Verification Status](#11-employee-portal--face-verification-status)
12. [Audit Trail — Face Verification Logs](#12-audit-trail--face-verification-logs)
13. [Testing Face Recognition (Pest)](#13-testing-face-recognition-pest)
14. [Security Considerations](#14-security-considerations)
15. [Troubleshooting & Debugging](#15-troubleshooting--debugging)
16. [Latihan Praktik](#16-latihan-praktik)

---

## 1. Kenapa Face Recognition di Absensi?

### Problem Statement

```
Masalah Absensi Konvensional:

❌ Titip absen (buddy punching)
   → Karyawan A absen untuk karyawan B
   → Kerugian perusahaan: gaji dibayar tapi karyawan tidak hadir

❌ Manipulasi lokasi (fake GPS)
   → GPS bisa di-spoof dengan aplikasi third-party
   → Karyawan absen dari rumah padahal seharusnya di kantor

❌ Foto selfie biasa mudah diakali
   → Bisa pakai foto dari gallery
   → Bisa foto layar HP orang lain
```

### Solusi: Face Recognition + GPS

```
Triple Verification di GajiPro:

✅ GPS Validation
   └─ Pastikan karyawan di radius kantor

✅ Face Recognition
   └─ Pastikan yang absen adalah orang yang benar
   └─ Bandingkan wajah live dengan wajah terdaftar

```

### Perbandingan Metode Absensi

| Metode | Buddy Punching | Fake GPS | Cost | User Experience |
|--------|:--------------:|:--------:|:----:|:---------------:|
| Fingerprint | Aman | N/A | Perlu hardware | Perlu device fisik |
| QR Code | Mudah diakali | Bisa | Murah | Cepat |
| PIN/Password | Mudah diakali | Bisa | Murah | Cepat |
| GPS Only | Bisa | Bisa diakali | Murah | Mudah |
| **Face + GPS** | **Aman** | **Aman** | **Murah** | **Mudah** |

**Face Recognition + GPS adalah sweet spot** — aman, murah (tanpa hardware tambahan), dan user-friendly (hanya selfie).

---

## 2. Arsitektur Face Recognition GajiPro

### Big Picture

```
┌────────────────────────────────────────────────────────────┐
│                    FLUTTER MOBILE APP                        │
│                                                              │
│  ┌─────────────┐    ┌──────────────────┐                    │
│  │  Camera      │    │  ML Kit /         │                    │
│  │  (selfie)    │───▶│  face-api.js      │                    │
│  │              │    │  Face Detection    │                    │
│  └─────────────┘    └────────┬─────────┘                    │
│                              │                               │
│                    ┌─────────▼──────────┐                    │
│                    │  Face Embedding     │                    │
│                    │  128/192 float array│                    │
│                    │  [0.12, -0.45, ...] │                    │
│                    └─────────┬──────────┘                    │
│                              │                               │
│              ┌───────────────┼───────────────┐              │
│              │               │               │              │
│        ┌─────▼─────┐  ┌─────▼─────┐  ┌─────▼─────┐       │
│        │ Enrollment │  │ Client    │  │ Server    │       │
│        │ (daftar    │  │ Verify    │  │ Verify    │       │
│        │  wajah)    │  │ (lokal)   │  │ (legacy)  │       │
│        └─────┬─────┘  └─────┬─────┘  └─────┬─────┘       │
│              │               │               │              │
└──────────────┼───────────────┼───────────────┼──────────────┘
               │               │               │
         POST /enroll    POST /clock-in    POST /verify
         + embedding     + face_verified   + descriptors
               │               │               │
┌──────────────▼───────────────▼───────────────▼──────────────┐
│                    LARAVEL BACKEND                             │
│                                                                │
│  ┌──────────────────────────────────────────────────────┐    │
│  │              FaceRecognitionService                    │    │
│  │                                                        │    │
│  │  enrollFace()        → Simpan embedding ke DB          │    │
│  │  verifyFace()        → Cosine similarity matching      │    │
│  │  calculateSimilarity() → Hitung kecocokan vektor       │    │
│  │  getEnrollmentStatus() → Cek status enrollment         │    │
│  │  removeEnrollment()  → Hapus data wajah                │    │
│  └────────────────────────────┬─────────────────────────┘    │
│                               │                               │
│  ┌────────────────────────────▼─────────────────────────┐    │
│  │                    MySQL Database                      │    │
│  │                                                        │    │
│  │  employee_face_embeddings  → Data embedding wajah     │    │
│  │  face_verification_logs    → Log verifikasi           │    │
│  │  companies (settings)      → Konfigurasi per tenant   │    │
│  │  attendances (face cols)   → Hasil verifikasi absensi │    │
│  └──────────────────────────────────────────────────────┘    │
└────────────────────────────────────────────────────────────────┘
```

### Processing Flow: Enrollment

```
Step 1: Admin/Karyawan buka kamera
         │
Step 2: Ambil foto wajah (selfie)
         │
Step 3: Face detection library (ML Kit / face-api.js)
         │  → Detect wajah dalam foto
         │  → Extract face embedding (128/192 float values)
         │
Step 4: Kirim ke API: POST /api/v1/face-recognition/enroll
         │  Body: { embedding_data: { model, version, embedding: [...] }, photo }
         │
Step 5: Backend simpan ke employee_face_embeddings
         │  → JSON column: embedding_data
         │  → File: enrollment_photo
         │
Step 6: ✅ Wajah terdaftar!
```

### Processing Flow: Verification (saat clock in/out)

```
Step 1: Karyawan buka kamera untuk absen
         │
Step 2: Face detection → Extract live embedding
         │
Step 3a: CLIENT-SIDE (preferred)
         │  → Bandingkan live embedding vs stored embedding di device
         │  → Hitung cosine similarity
         │  → Kirim hasil: { face_verified: true, face_confidence: 0.85 }
         │
Step 3b: SERVER-SIDE (legacy)
         │  → Kirim raw descriptors ke backend
         │  → Backend hitung cosine similarity
         │  → Backend tentukan match/not match
         │
Step 4: Backend catat di attendances & face_verification_logs
         │
Step 5: ✅ Absen berhasil / ❌ Wajah tidak cocok
```

---

## 3. Database Schema — Tabel & Kolom

### Tabel: `employee_face_embeddings`

```sql
CREATE TABLE employee_face_embeddings (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     BIGINT UNSIGNED NOT NULL UNIQUE,  -- 1 embedding per employee
    embedding_data  JSON NOT NULL,                     -- Face descriptor vector
    enrollment_photo VARCHAR(255) NOT NULL,            -- Path ke foto
    enrolled_at     TIMESTAMP NULL,                    -- Kapan didaftarkan
    enrolled_by     BIGINT UNSIGNED NULL,              -- Siapa yang mendaftarkan
    quality_score   DECIMAL(5,4) NULL,                 -- Kualitas foto (0.0000-1.0000)
    is_active       BOOLEAN DEFAULT TRUE,              -- Status aktif
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    deleted_at      TIMESTAMP NULL,                    -- Soft delete

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**Yang perlu diperhatikan:**
- `employee_id` di-UNIQUE → setiap karyawan hanya punya 1 embedding aktif
- `embedding_data` berformat JSON → fleksibel untuk berbagai model ML
- `SoftDeletes` → data bisa di-recover jika diperlukan

### Format `embedding_data` (JSON)

```json
{
    "version": "1.0",
    "model": "google_mlkit",
    "embedding": [0.1234, -0.5678, 0.9012, ..., 0.3456]
}
```

| Field | Tipe | Keterangan |
|-------|------|------------|
| `version` | string | Versi format data |
| `model` | string | Model ML yang digunakan (`google_mlkit`, `face-api.js`) |
| `embedding` | float[] | Array 128 atau 192 angka desimal (face descriptor) |

> **128 values** = face-api.js (JavaScript, web)
> **192 values** = MobileFaceNet TFLite (Flutter, native)

### Tabel: `face_verification_logs`

```sql
CREATE TABLE face_verification_logs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id         BIGINT UNSIGNED NOT NULL,
    attendance_id       BIGINT UNSIGNED NULL,
    verification_type   ENUM('clock_in', 'clock_out', 'enrollment'),
    is_successful       BOOLEAN DEFAULT FALSE,
    confidence_score    DECIMAL(5,4) NULL,          -- Cosine similarity (0-1)
    liveness_passed     BOOLEAN NULL,
    failure_reason      VARCHAR(255) NULL,          -- 'no_face_enrolled', 'face_not_matched'
    photo_path          VARCHAR(255) NULL,
    metadata            JSON NULL,
    ip_address          VARCHAR(45) NULL,
    user_agent          TEXT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX (employee_id, created_at),
    INDEX (verification_type)
);
```

### Kolom Tambahan di `companies`

```sql
ALTER TABLE companies ADD COLUMN enable_face_recognition     BOOLEAN DEFAULT TRUE;
ALTER TABLE companies ADD COLUMN face_match_threshold        DECIMAL(3,2) DEFAULT 0.60;
ALTER TABLE companies ADD COLUMN require_liveness_detection  BOOLEAN DEFAULT TRUE;
```

### Kolom Tambahan di `attendances`

```sql
ALTER TABLE attendances ADD COLUMN face_verified   BOOLEAN DEFAULT FALSE;
ALTER TABLE attendances ADD COLUMN face_confidence  DECIMAL(5,4) NULL;
```

### Migration Files

```
database/migrations/
├── 2026_02_12_074534_create_employee_face_embeddings_table.php
├── 2026_02_12_074537_create_face_verification_logs_table.php
├── 2026_02_12_074540_add_face_recognition_settings_to_companies_table.php
└── 2026_02_12_083103_add_face_verified_to_attendances_table.php
```

Mari lihat migration utama:

**File: `database/migrations/2026_02_12_074534_create_employee_face_embeddings_table.php`**

```php
Schema::create('employee_face_embeddings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
    $table->json('embedding_data');
    $table->string('enrollment_photo');
    $table->timestamp('enrolled_at')->nullable();
    $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
    $table->decimal('quality_score', 5, 4)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 4. Konsep Face Embedding & Cosine Similarity

### Apa itu Face Embedding?

```
Face Embedding = Representasi numerik dari wajah seseorang

Foto Wajah                    ML Model                    Embedding Vector
┌──────────┐     ┌──────────────────────┐     ┌──────────────────────┐
│ 📷       │ ──▶ │ Google ML Kit /      │ ──▶ │ [0.12, -0.45, 0.78, │
│ (pixel   │     │ face-api.js /        │     │  0.33, -0.91, 0.56, │
│  data)   │     │ MobileFaceNet        │     │  ...,               │
│          │     │                      │     │  0.22, -0.67]       │
└──────────┘     └──────────────────────┘     │                      │
                                               │ 128 atau 192 angka  │
                                               └──────────────────────┘
```

**Analogi sederhana:**
- Bayangkan setiap wajah sebagai titik dalam ruang 128 dimensi
- Wajah yang **mirip** → titiknya **berdekatan**
- Wajah yang **berbeda** → titiknya **berjauhan**

### Cosine Similarity — Cara Membandingkan Wajah

```
Cosine Similarity mengukur "sudut" antara dua vektor.

                     A · B
similarity = ─────────────────────
             ‖A‖ × ‖B‖

Di mana:
  A · B    = dot product (jumlah dari A[i] × B[i])
  ‖A‖      = magnitude/panjang vektor A
  ‖B‖      = magnitude/panjang vektor B
```

### Implementasi di GajiPro

**File: `app/Services/FaceRecognitionService.php`**

```php
/**
 * Calculate cosine similarity between two embedding vectors.
 * Returns a value between -1 and 1, where 1 means identical.
 */
public function calculateSimilarity(array $embedding1, array $embedding2): float
{
    if (count($embedding1) !== count($embedding2) || empty($embedding1)) {
        return 0.0;
    }

    $dotProduct = 0.0;
    $magnitude1 = 0.0;
    $magnitude2 = 0.0;

    for ($i = 0; $i < count($embedding1); $i++) {
        $dotProduct += $embedding1[$i] * $embedding2[$i];
        $magnitude1 += $embedding1[$i] * $embedding1[$i];
        $magnitude2 += $embedding2[$i] * $embedding2[$i];
    }

    $magnitude1 = sqrt($magnitude1);
    $magnitude2 = sqrt($magnitude2);

    if ($magnitude1 === 0.0 || $magnitude2 === 0.0) {
        return 0.0;
    }

    return $dotProduct / ($magnitude1 * $magnitude2);
}
```

### Interpretasi Hasil

```
Similarity Score     Interpretasi              Aksi
─────────────────────────────────────────────────────
  1.0                Identik (exact match)     ✅ Pass
  0.9 - 1.0         Sangat mirip              ✅ Pass
  0.7 - 0.9         Mirip (orang yang sama)   ✅ Pass
  0.6 - 0.7         Borderline                ⚠️ Tergantung threshold
  0.4 - 0.6         Kurang mirip              ❌ Fail
  0.0 - 0.4         Berbeda                   ❌ Fail
 -1.0 - 0.0         Sangat berbeda            ❌ Fail

Default threshold GajiPro: 0.60 (bisa dikonfigurasi per company)
Rekomendasi: 0.70 - 0.80 untuk balance antara keamanan dan kenyamanan
```

### Contoh Kalkulasi Manual

```
Embedding Terdaftar (A):  [0.5, 0.3, 0.8, -0.2]
Embedding Live (B):       [0.4, 0.3, 0.7, -0.1]

Dot Product = (0.5×0.4) + (0.3×0.3) + (0.8×0.7) + (-0.2×-0.1)
            = 0.20 + 0.09 + 0.56 + 0.02
            = 0.87

Magnitude A = √(0.25 + 0.09 + 0.64 + 0.04) = √1.02 = 1.01
Magnitude B = √(0.16 + 0.09 + 0.49 + 0.01) = √0.75 = 0.866

Similarity  = 0.87 / (1.01 × 0.866) = 0.87 / 0.875 = 0.994

→ Score 0.994 > threshold 0.60 → ✅ MATCH!
```

---

## 5. Company Settings — Konfigurasi per Tenant

### Tiga Setting Utama

| Setting | Default | Kolom | Keterangan |
|---------|---------|-------|------------|
| Enable Face Recognition | `true` | `enable_face_recognition` | On/Off fitur face recognition |
| Face Match Threshold | `0.60` | `face_match_threshold` | Minimum similarity score untuk match |
| Require Liveness | `true` | `require_liveness_detection` | Wajibkan liveness detection |

### Cara Akses di Controller

```php
// Get company settings
$company = $user->company;

$settings = [
    'face_recognition_enabled' => $company->enable_face_recognition ?? false,
    'liveness_required'        => $company->require_liveness_detection ?? true,
    'match_threshold'          => $company->face_match_threshold ?? 0.6,
];
```

### UI Pengaturan di Web Admin

**File: `resources/views/settings/attendance/index.blade.php`**

Halaman Settings > Attendance menampilkan:

```
┌─────────────────────────────────────────────┐
│  ⚙️ Pengaturan Face Recognition              │
│                                               │
│  [✓] Aktifkan Face Recognition               │
│                                               │
│  Face Match Threshold                         │
│  [████████████░░░░░░░░] 75%                  │
│  50%                                    100%  │
│                                               │
│  ℹ️ Karyawan perlu mendaftarkan wajah         │
│     terlebih dahulu sebelum bisa absen        │
│     dengan face recognition.                  │
│                                               │
│  Rekomendasi: 70-80% untuk balance            │
│  antara keamanan dan kenyamanan.              │
└─────────────────────────────────────────────┘
```

### Threshold Guidelines

```
50-60%:  Longgar → Mudah pass, tapi risiko false positive tinggi
         Cocok untuk: Testing/development

70-80%:  Balanced → Rekomendasi untuk production
         Cocok untuk: Mayoritas perusahaan

85-95%:  Ketat → Aman, tapi bisa sering gagal (cahaya, kacamata, dll)
         Cocok untuk: High-security environment

95-100%: Sangat ketat → Hampir pasti gagal kecuali kondisi ideal
         TIDAK direkomendasikan
```

---

## 6. Service Layer — FaceRecognitionService

### Overview

**File: `app/Services/FaceRecognitionService.php`**

Service ini adalah inti dari semua operasi face recognition. Digunakan oleh web controller dan API controller.

### Method 1: `enrollFace()`

```php
public function enrollFace(
    Employee $employee,
    array $embeddingData,        // { model, version, embedding: [...] }
    string $photoPath,           // Path foto tersimpan
    ?User $enrolledBy = null,    // Siapa yang daftarkan
    ?float $qualityScore = null  // Skor kualitas (0-1)
): EmployeeFaceEmbedding
```

**Yang terjadi:**
1. Cek apakah sudah ada embedding sebelumnya
2. Jika ada → `forceDelete()` (karena unique constraint pada `employee_id`)
3. Buat embedding baru dengan `is_active = true`
4. Catat log enrollment di `face_verification_logs`

```php
// Force delete existing embedding if any (unique constraint on employee_id)
$existingEmbedding = $employee->faceEmbedding;
if ($existingEmbedding) {
    $existingEmbedding->forceDelete();
}

// Create new embedding
$embedding = EmployeeFaceEmbedding::create([
    'employee_id' => $employee->id,
    'embedding_data' => $embeddingData,
    'enrollment_photo' => $photoPath,
    'enrolled_at' => now(),
    'enrolled_by' => $enrolledBy?->id,
    'quality_score' => $qualityScore,
    'is_active' => true,
]);
```

### Method 2: `verifyFace()`

```php
public function verifyFace(
    Employee $employee,
    array $liveDescriptors,            // Embedding dari kamera live
    float $threshold = 0.6,            // Minimum similarity
    string $verificationType = 'clock_in',
    ?string $photoPath = null
): array  // { matched: bool, confidence: float|null, error: string|null }
```

**Yang terjadi:**
1. Ambil embedding aktif dari database
2. Jika tidak ada → return error `no_face_enrolled`
3. Support dua format: `embedding` (baru) dan `descriptors` (legacy)
4. Hitung cosine similarity
5. Bandingkan dengan threshold
6. Log hasil verifikasi

```php
// Support both old 'descriptors' and new 'embedding' field names
$storedDescriptors = $embedding->embedding_data['embedding']
    ?? $embedding->embedding_data['descriptors']
    ?? [];

$similarity = $this->calculateSimilarity($storedDescriptors, $liveDescriptors);
$matched = $similarity >= $threshold;

return [
    'matched' => $matched,
    'confidence' => round($similarity, 4),
    'error' => $matched ? null : 'face_not_matched',
];
```

### Method 3: `getEnrollmentStatus()`

```php
public function getEnrollmentStatus(Employee $employee): array
// Returns: { enrolled: bool, enrolled_at: string|null, quality_score: float|null }
```

### Method 4: `removeEnrollment()`

```php
public function removeEnrollment(Employee $employee): bool
// Soft-delete embedding, returns false jika tidak ada
```

### Dependency Injection di Controller

```php
class FaceRecognitionController extends Controller
{
    public function __construct(
        protected FaceRecognitionService $faceRecognitionService
    ) {}

    // Service tersedia di semua method via $this->faceRecognitionService
}
```

---

## 7. Web Admin — Face Enrollment Management

### Routes

```php
// routes/web.php (dalam middleware admin)
Route::get('/face-recognition', [FaceRecognitionController::class, 'index'])
    ->name('face-recognition.index');
Route::get('/face-recognition/{employee}', [FaceRecognitionController::class, 'show'])
    ->name('face-recognition.show');
Route::post('/face-recognition/{employee}', [FaceRecognitionController::class, 'store'])
    ->name('face-recognition.store');
Route::delete('/face-recognition/{employee}', [FaceRecognitionController::class, 'destroy'])
    ->name('face-recognition.destroy');
```

### Halaman Index — Daftar Karyawan

**File: `resources/views/face-recognition/index.blade.php`**

```
┌─────────────────────────────────────────────────────────────────┐
│  Face Recognition — Pendaftaran Wajah                           │
│                                                                   │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐               │
│  │ 👥 Total   │  │ ✅ Terdaftar│  │ ⚠️ Belum    │               │
│  │    45      │  │    38      │  │    7       │               │
│  └────────────┘  └────────────┘  └────────────┘               │
│                                                                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ Nama         │ ID       │ Departemen │ Status    │ Aksi   │  │
│  ├──────────────┼──────────┼────────────┼───────────┼────────┤  │
│  │ Budi Santoso │ EMP0001  │ IT         │ Terdaftar │ Lihat  │  │
│  │ Ani Wulan    │ EMP0002  │ HR         │ Terdaftar │ Lihat  │  │
│  │ Dedi Kurnia  │ EMP0003  │ Finance    │ Belum     │ Daftar │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Halaman Show — Form Enrollment

**File: `resources/views/face-recognition/show.blade.php`**

```
┌─────────────────────────────────────────────────────────────────┐
│  Pendaftaran Wajah — Budi Santoso                               │
│                                                                   │
│  ┌──────────────────┐  ┌──────────────────────────────────────┐ │
│  │ Info Karyawan     │  │ Daftarkan Wajah                      │ │
│  │                    │  │                                      │ │
│  │ Nama: Budi S.     │  │  [Upload Foto]  [Buka Kamera]       │ │
│  │ ID: EMP0001       │  │                                      │ │
│  │ Dept: IT           │  │  ┌──────────────────────────┐       │ │
│  │ Posisi: Developer  │  │  │                          │       │ │
│  │                    │  │  │   📷 Preview Area        │       │ │
│  │ Status: Terdaftar  │  │  │                          │       │ │
│  │ Terdaftar: 15 Jan  │  │  │                          │       │ │
│  │ Quality: 0.95      │  │  └──────────────────────────┘       │ │
│  │                    │  │                                      │ │
│  │ [🗑 Hapus Data]    │  │  Panduan:                            │ │
│  └──────────────────┘  │  • Pastikan wajah terlihat jelas     │ │
│                          │  • Cahaya cukup terang              │ │
│                          │  • Tidak memakai masker             │ │
│                          │  • Posisi wajah menghadap kamera    │ │
│                          │                                      │ │
│                          │  [Daftarkan Wajah]                   │ │
│                          └──────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### Controller Logic — Store (Enrollment)

```php
public function store(Request $request, Employee $employee): RedirectResponse
{
    $tenant = app('tenant');

    // Tenant isolation check
    if ($employee->company_id !== $tenant->id) {
        abort(404);
    }

    $validated = $request->validate([
        'photo' => ['required', 'image', 'max:5120'], // Max 5MB
        'embedding_data' => ['sometimes', 'json'],
    ]);

    // Store foto di folder tenant-isolated
    $photoPath = $request->file('photo')->store(
        "face-enrollments/{$tenant->id}",
        'public'
    );

    // Parse embedding dari client (JavaScript face-api.js)
    $embeddingData = [];
    if ($request->has('embedding_data')) {
        $embeddingData = json_decode($request->input('embedding_data'), true);
    }

    // Daftarkan wajah via service
    $this->faceRecognitionService->enrollFace(
        $employee,
        $embeddingData,
        $photoPath,
        $request->user(),
        $embeddingData['quality_score'] ?? null
    );

    return redirect()
        ->route('face-recognition.show', $employee)
        ->with('success', 'Wajah karyawan berhasil didaftarkan.');
}
```

### Controller Logic — Destroy (Reset Wajah)

```php
public function destroy(Employee $employee): RedirectResponse
{
    // ... tenant check ...

    $embeddings = $employee->faceEmbedding()->get();

    foreach ($embeddings as $embedding) {
        // Hapus file foto
        if ($embedding->enrollment_photo) {
            Storage::disk('public')->delete($embedding->enrollment_photo);
        }
        // Hard delete (force) karena model pakai SoftDeletes
        $embedding->forceDelete();
    }

    return redirect()
        ->route('employees.show', $employee)
        ->with('success', 'Data wajah karyawan berhasil direset.');
}
```

---

## 8. API Endpoints — Mobile Integration

### Endpoint Overview

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| `GET` | `/api/v1/face-recognition/status` | Cek status enrollment |
| `POST` | `/api/v1/face-recognition/enroll` | Daftarkan wajah |
| `POST` | `/api/v1/face-recognition/verify` | Verifikasi wajah |
| `DELETE` | `/api/v1/face-recognition/enrollment` | Hapus pendaftaran |

Semua endpoint memerlukan **Sanctum authentication** (Bearer token).

### 1. GET /status — Cek Status Enrollment

**Request:**
```bash
curl -X GET http://localhost:8000/api/v1/face-recognition/status \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response (enrolled):**
```json
{
    "data": {
        "enrolled": true,
        "enrolled_at": "2026-01-15T10:30:00+07:00",
        "quality_score": 0.95,
        "company_settings": {
            "face_recognition_enabled": true,
            "liveness_required": true,
            "match_threshold": 0.6
        }
    }
}
```

**Response (not enrolled):**
```json
{
    "data": {
        "enrolled": false,
        "enrolled_at": null,
        "quality_score": null,
        "company_settings": {
            "face_recognition_enabled": true,
            "liveness_required": true,
            "match_threshold": 0.6
        }
    }
}
```

### 2. POST /enroll — Daftarkan Wajah

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/face-recognition/enroll \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: multipart/form-data" \
  -F "embedding_data[version]=1.0" \
  -F "embedding_data[model]=google_mlkit" \
  -F "embedding_data[embedding]=[0.12, -0.45, 0.78, ...]" \
  -F "photo=@/path/to/face.jpg" \
  -F "quality_score=0.95"
```

**Validation Rules:**
```php
'embedding_data'              => ['required', 'array'],
'embedding_data.version'      => ['required', 'string'],
'embedding_data.model'        => ['required', 'string'],
'embedding_data.embedding'    => ['required', 'array', 'min:128', 'max:192'],
'embedding_data.embedding.*'  => ['required', 'numeric'],
'photo'                       => ['nullable', 'image', 'max:5120'],
'quality_score'               => ['nullable', 'numeric', 'between:0,1'],
```

**Response (201 Created):**
```json
{
    "message": "Face enrolled successfully.",
    "data": {
        "enrolled": true,
        "enrolled_at": "2026-02-15T10:30:00+07:00",
        "quality_score": 0.95
    }
}
```

**Error (403 — fitur disabled):**
```json
{
    "message": "Face recognition is not enabled for this company."
}
```

### 3. POST /verify — Verifikasi Wajah

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/face-recognition/verify \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "descriptors": [0.12, -0.45, 0.78, ...],
    "verification_type": "clock_in"
  }'
```

**Validation Rules:**
```php
'descriptors'         => ['required', 'array', 'min:128', 'max:192'],
'descriptors.*'       => ['required', 'numeric'],
'verification_type'   => ['nullable', 'in:clock_in,clock_out'],
```

**Response (matched):**
```json
{
    "data": {
        "matched": true,
        "confidence": 0.8523,
        "error": null
    }
}
```

**Response (not matched):**
```json
{
    "data": {
        "matched": false,
        "confidence": 0.4215,
        "error": "face_not_matched"
    }
}
```

**Response (no enrollment):**
```json
{
    "data": {
        "matched": false,
        "confidence": null,
        "error": "no_face_enrolled"
    }
}
```

### 4. DELETE /enrollment — Hapus Pendaftaran

**Request:**
```bash
curl -X DELETE http://localhost:8000/api/v1/face-recognition/enrollment \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response (200):**
```json
{
    "message": "Face enrollment removed successfully."
}
```

---

## 9. Dua Mode Verifikasi: Client-Side vs Server-Side

### Mengapa Ada 2 Mode?

```
Mode 1: CLIENT-SIDE (Recommended)
─────────────────────────────────────
Flutter app melakukan matching secara lokal di device.
Keuntungan:
  ✅ Lebih cepat (tidak perlu kirim 128 float ke server)
  ✅ Lebih hemat bandwidth
  ✅ Bisa offline (jika embedding di-cache di device)
  ✅ Lebih private (raw embedding tidak keluar dari device)

Mode 2: SERVER-SIDE (Legacy)
─────────────────────────────────────
Flutter app kirim raw descriptors, server yang matching.
Keuntungan:
  ✅ Lebih mudah di-audit (semua data ada di server)
  ✅ Bisa update threshold tanpa update app
Kekurangan:
  ❌ Butuh internet
  ❌ Lebih lambat
  ❌ Payload besar (128+ float values)
```

### Implementasi di Attendance Controller

**File: `app/Http/Controllers/Api/V1/AttendanceController.php`**

```php
// Saat clock-in, face verification terjadi di sini:

// 1. Cek apakah face recognition enabled
if ($company->enable_face_recognition && $employee->hasFaceEnrolled()) {

    // 2a. Client-side mode: Flutter sudah verifikasi
    if ($request->has('face_verified')) {
        $faceVerified = (bool) $request->input('face_verified');
        $faceConfidence = (float) $request->input('face_confidence', 0);

        // Trust client result
        $attendance->face_verified = $faceVerified;
        $attendance->face_confidence = $faceConfidence;
    }

    // 2b. Server-side mode: Backend verifikasi
    elseif ($request->has('face_descriptors')) {
        $result = $this->faceRecognitionService->verifyFace(
            $employee,
            $request->input('face_descriptors'),
            $company->face_match_threshold ?? 0.6,
            'clock_in'
        );

        if (!$result['matched']) {
            return response()->json([
                'message' => 'Verifikasi wajah gagal.',
                'face_confidence' => $result['confidence'],
            ], 422);
        }

        $attendance->face_verified = true;
        $attendance->face_confidence = $result['confidence'];
    }

    // 2c. Tidak ada face data
    else {
        return response()->json([
            'message' => 'Verifikasi wajah diperlukan.',
        ], 422);
    }
}
```

### Flutter Side: Client-Side Flow

```dart
// Pseudocode Flutter (untuk referensi)

// 1. Ambil stored embedding dari cache/API
final storedEmbedding = await getStoredEmbedding();

// 2. Capture live face & extract embedding
final liveEmbedding = await extractFaceEmbedding(cameraImage);

// 3. Hitung cosine similarity di device
final similarity = cosineSimilarity(storedEmbedding, liveEmbedding);
final threshold = companySettings.matchThreshold;

// 4. Kirim hasil ke backend saat clock-in
await api.clockIn(
  latitude: position.latitude,
  longitude: position.longitude,
  face_verified: similarity >= threshold,
  face_confidence: similarity,
);
```

---

## 10. Integrasi dengan Attendance (Clock In/Out)

### Full Attendance Flow dengan Face Recognition

```
Karyawan buka app
       │
       ▼
┌──────────────┐     ┌──────────────┐
│ Cek GPS      │────▶│ Dalam radius │──── Tidak ──▶ ❌ "Di luar area kantor"
│ Location     │     │ kantor?      │
└──────────────┘     └──────┬───────┘
                            │ Ya
                            ▼
                    ┌──────────────┐
                    │ Face Recog   │──── Disabled ──▶ Skip face check
                    │ enabled?     │
                    └──────┬───────┘
                            │ Enabled
                            ▼
                    ┌──────────────┐
                    │ Has face     │──── Belum ──▶ ❌ "Daftarkan wajah dulu"
                    │ enrolled?    │
                    └──────┬───────┘
                            │ Sudah
                            ▼
                    ┌──────────────┐
                    │ 📷 Capture   │
                    │ selfie &     │
                    │ verify face  │
                    └──────┬───────┘
                            │
                    ┌──────────────┐
                    │ Face match?  │──── Tidak ──▶ ❌ "Wajah tidak cocok"
                    └──────┬───────┘
                            │ Ya
                            ▼
                    ┌──────────────┐
                    │ ✅ Clock In   │
                    │ Berhasil!    │
                    └──────────────┘
```

### Data yang Tersimpan di `attendances`

```
┌─────────────────────────────────────────────────────────────┐
│ attendances                                                   │
│                                                               │
│ id: 1234                                                      │
│ employee_id: 45                                               │
│ date: 2026-04-21                                              │
│ clock_in: 08:15:00                                            │
│ clock_in_latitude: -6.2088                                    │
│ clock_in_longitude: 106.8456                                  │
│ face_verified: true          ← ✅ Wajah terverifikasi        │
│ face_confidence: 0.8523      ← Tingkat kecocokan             │
│ status: present                                               │
│ late_minutes: 15                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 11. Employee Portal — Face Verification Status

### Portal Attendance Page

**File: `app/Http/Controllers/EmployeePortal/AttendanceController.php`**

```php
public function index(Request $request): View
{
    $tenant = app('tenant');
    $employee = $request->user()->employee;

    // Load face embedding data
    $employee->load('faceEmbedding');

    $faceRecognitionEnabled = $tenant->enable_face_recognition ?? false;
    $hasFaceEnrolled = $employee->hasFaceEnrolled();

    return view('portal.attendance.index', compact(
        'employee',
        'faceRecognitionEnabled',
        'hasFaceEnrolled',
        // ... other data
    ));
}
```

### Portal Face Recognition Status API

```php
// GET /portal/face-recognition/status
public function faceRecognitionStatus(Request $request): JsonResponse
{
    $employee = $request->user()->employee;

    $embedding = $employee->faceEmbedding()
        ->where('is_active', true)
        ->first();

    return response()->json([
        'enrolled' => $embedding !== null,
        'enrolled_at' => $embedding?->enrolled_at?->toIso8601String(),
    ]);
}
```

### Tampilan di Portal

```
┌──────────────────────────────────────┐
│ 📍 Absensi                           │
│                                       │
│ Status Face Recognition:              │
│ ┌──────────────────────────────────┐ │
│ │ ✅ Wajah Terdaftar               │ │
│ │ Terdaftar sejak: 15 Jan 2026    │ │
│ │ Quality Score: 0.95              │ │
│ └──────────────────────────────────┘ │
│                                       │
│ atau:                                 │
│ ┌──────────────────────────────────┐ │
│ │ ⚠️ Wajah Belum Terdaftar         │ │
│ │ Silakan daftarkan wajah Anda     │ │
│ │ melalui aplikasi mobile atau     │ │
│ │ hubungi HR.                      │ │
│ └──────────────────────────────────┘ │
│                                       │
│ [🕐 Clock In]  [🕐 Clock Out]        │
└──────────────────────────────────────┘
```

---

## 12. Audit Trail — Face Verification Logs

### Model: FaceVerificationLog

**File: `app/Models/FaceVerificationLog.php`**

```php
class FaceVerificationLog extends Model
{
    public $timestamps = false;  // Hanya created_at, tanpa updated_at

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'verification_type',    // clock_in, clock_out, enrollment
        'is_successful',
        'confidence_score',
        'liveness_passed',
        'failure_reason',       // no_face_enrolled, face_not_matched
        'photo_path',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_successful' => 'boolean',
            'liveness_passed' => 'boolean',
            'confidence_score' => 'float',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
```

### Kapan Log Dibuat?

| Event | `verification_type` | Data yang Dicatat |
|-------|--------------------|--------------------|
| Enrollment wajah baru | `enrollment` | employee_id, quality_score, photo_path |
| Clock In sukses | `clock_in` | confidence_score, liveness_passed |
| Clock In gagal | `clock_in` | failure_reason, confidence_score |
| Clock Out sukses | `clock_out` | confidence_score, liveness_passed |
| Clock Out gagal | `clock_out` | failure_reason |

### Contoh Query Audit

```php
// Semua verifikasi gagal bulan ini
$failedVerifications = FaceVerificationLog::query()
    ->whereHas('employee', fn ($q) => $q->where('company_id', $tenant->id))
    ->where('is_successful', false)
    ->where('created_at', '>=', now()->startOfMonth())
    ->with('employee')
    ->latest('created_at')
    ->get();

// Statistik success rate per bulan
$stats = FaceVerificationLog::query()
    ->selectRaw('
        COUNT(*) as total,
        SUM(is_successful) as success,
        AVG(confidence_score) as avg_confidence
    ')
    ->whereHas('employee', fn ($q) => $q->where('company_id', $tenant->id))
    ->where('verification_type', '!=', 'enrollment')
    ->where('created_at', '>=', now()->startOfMonth())
    ->first();
```

---

## 13. Testing Face Recognition (Pest)

### Test Files Overview

```
tests/Feature/
├── FaceRecognition/
│   ├── FaceRecognitionServiceTest.php   → Unit test service layer
│   └── FaceEnrollmentWebTest.php        → Web enrollment flow
├── Api/
│   ├── FaceRecognitionApiTest.php       → API endpoints
│   ├── AttendanceFaceRecognitionTest.php → Face + attendance integration
│   └── AttendanceClientSideFaceTest.php  → Client-side verify mode
└── EmployeePortal/
    └── FaceVerificationTest.php          → Portal face status
```

### Test 1: Service Layer — Cosine Similarity

```php
// tests/Feature/FaceRecognition/FaceRecognitionServiceTest.php

describe('calculateSimilarity', function () {
    it('returns approximately 1.0 for identical embeddings', function () {
        $embedding1 = array_fill(0, 128, 0.5);
        $embedding2 = array_fill(0, 128, 0.5);

        $similarity = $this->service->calculateSimilarity($embedding1, $embedding2);

        // Use floating point tolerance
        expect($similarity)->toBeGreaterThan(0.9999);
        expect($similarity)->toBeLessThanOrEqual(1.0);
    });

    it('returns 0.0 for completely opposite embeddings', function () {
        $embedding1 = array_fill(0, 128, 1.0);
        $embedding2 = array_fill(0, 128, -1.0);

        $similarity = $this->service->calculateSimilarity($embedding1, $embedding2);

        expect($similarity)->toBeLessThanOrEqual(0.0);
    });
});
```

### Test 2: API Enrollment

```php
// tests/Feature/Api/FaceRecognitionApiTest.php

describe('POST /api/v1/face-recognition/enroll', function () {
    it('enrolls face with embedding data', function () {
        Sanctum::actingAs($this->user, ['*']);

        $embeddingData = [
            'model' => 'google_mlkit',
            'version' => '1.0',
            'embedding' => array_fill(0, 128, 0.5),
        ];

        $response = $this->postJson('/api/v1/face-recognition/enroll', [
            'embedding_data' => $embeddingData,
            'photo' => UploadedFile::fake()->image('face.jpg', 640, 480),
            'quality_score' => 0.95,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.enrolled', true)
            ->assertJsonPath('message', 'Face enrolled successfully.');

        $this->assertDatabaseHas('employee_face_embeddings', [
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);
    });

    it('returns 403 when face recognition is disabled', function () {
        $this->company->update(['enable_face_recognition' => false]);
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/face-recognition/enroll', [
            'embedding_data' => [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ],
        ]);

        $response->assertForbidden();
    });
});
```

### Test 3: Face Verification Flow

```php
describe('POST /api/v1/face-recognition/verify', function () {
    it('verifies face successfully when matched', function () {
        Sanctum::actingAs($this->user, ['*']);

        // Buat stored embedding
        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'embedding_data' => [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ],
            'is_active' => true,
        ]);

        // Kirim embedding yang sama → harus match
        $response = $this->postJson('/api/v1/face-recognition/verify', [
            'descriptors' => array_fill(0, 128, 0.5),
            'verification_type' => 'clock_in',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.matched', true);
    });

    it('creates verification log', function () {
        Sanctum::actingAs($this->user, ['*']);

        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'embedding_data' => [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ],
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/face-recognition/verify', [
            'descriptors' => array_fill(0, 128, 0.5),
            'verification_type' => 'clock_in',
        ]);

        // Pastikan log tercatat
        $this->assertDatabaseHas('face_verification_logs', [
            'employee_id' => $this->employee->id,
            'verification_type' => 'clock_in',
        ]);
    });
});
```

### Test 4: Tenant Isolation

```php
// tests/Feature/FaceRecognition/FaceEnrollmentWebTest.php

it('prevents access to other tenant employees', function () {
    $otherCompany = Company::factory()->create();
    $otherEmployee = Employee::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('face-recognition.show', $otherEmployee));

    $response->assertNotFound();
});
```

### Menjalankan Test

```bash
# Semua face recognition tests
php artisan test --compact --filter="FaceRecognition"

# Service layer tests
php artisan test --compact tests/Feature/FaceRecognition/FaceRecognitionServiceTest.php

# API tests
php artisan test --compact tests/Feature/Api/FaceRecognitionApiTest.php

# Web enrollment tests
php artisan test --compact tests/Feature/FaceRecognition/FaceEnrollmentWebTest.php

# Portal tests
php artisan test --compact tests/Feature/EmployeePortal/FaceVerificationTest.php
```

---

## 14. Security Considerations

### 1. Tenant Isolation

```php
// ⚠️ KRITIS: Semua query HARUS di-scope ke company_id

// ✅ Benar
$employees = Employee::with('faceEmbedding')
    ->where('company_id', $tenant->id)
    ->get();

// ❌ SALAH — data semua tenant bocor!
$employees = Employee::with('faceEmbedding')->get();
```

### 2. File Storage Isolation

```php
// Foto enrollment disimpan per-tenant
$photoPath = $request->file('photo')->store(
    "face-enrollments/{$tenant->id}",  // ← Folder per company
    'public'
);
```

### 3. Embedding Data Protection

```
⚠️ Face embedding adalah data biometrik yang SANGAT sensitif!

Pertimbangan:
1. Embedding TIDAK boleh di-export tanpa persetujuan
2. Saat employee exit → pertimbangkan hapus data biometrik
3. Comply dengan UU PDP (Perlindungan Data Pribadi)
4. Log semua akses ke data biometrik

Di GajiPro:
- Embedding tersimpan sebagai JSON di database (terenkripsi at-rest oleh MySQL)
- File foto di storage (pertimbangkan enkripsi file-level)
- Soft delete → data bisa di-recover tapi juga bisa di-purge
- Activity log mencatat semua operasi enrollment/delete
```

### 4. Client-Side Trust

```
Mode client-side verification → backend "trust" hasil dari Flutter app.
Ini adalah trade-off:

Risiko:
- Attacker bisa kirim face_verified=true tanpa verifikasi nyata
- Confidence score bisa di-manipulasi

Mitigasi:
- Rate limiting pada endpoint attendance
- IP & device fingerprinting
- Anomaly detection (misalnya, selalu confidence 1.0 → suspicious)
- Combine dengan GPS validation
- Periodic re-enrollment untuk memastikan data up-to-date
```

### 5. Threshold Configuration

```
⚠️ Threshold yang terlalu rendah → false positive (orang lain bisa pass)
⚠️ Threshold yang terlalu tinggi → false negative (orang benar tidak bisa pass)

Security recommendation:
- Production default: 0.70 - 0.80
- High-security: 0.85+
- JANGAN pernah di bawah 0.50
```

---

## 15. Troubleshooting & Debugging

### Problem 1: "Wajah tidak cocok" padahal orang yang sama

```
Kemungkinan penyebab:
1. Cahaya berbeda saat enrollment vs verifikasi
   → Solusi: Re-enroll di kondisi cahaya normal

2. Memakai kacamata/masker saat salah satu proses
   → Solusi: Enrollment tanpa kacamata/masker

3. Threshold terlalu tinggi
   → Solusi: Turunkan threshold (Settings > Attendance > Face Match Threshold)

4. Kualitas kamera berbeda (web vs mobile)
   → Solusi: Enrollment dan verifikasi di device yang sama

5. Model ML berbeda (face-api.js vs ML Kit)
   → Solusi: Pastikan embedding dimension match (128 vs 192)
```

### Problem 2: Face enrollment gagal

```
Kemungkinan penyebab:
1. File terlalu besar (> 5MB)
   → Solusi: Kompres foto sebelum upload

2. Bukan file image
   → Solusi: Pastikan format JPG/PNG

3. Face recognition disabled di company settings
   → Solusi: Aktifkan di Settings > Attendance

4. User tidak punya employee record
   → Solusi: Buat employee dulu, link ke user
```

### Problem 3: Debugging Cosine Similarity

```php
// Gunakan tinker untuk debug
use App\Services\FaceRecognitionService;
use App\Models\Employee;

$service = new FaceRecognitionService();

// Ambil stored embedding
$employee = Employee::find(1);
$stored = $employee->faceEmbedding->embedding_data['embedding']
    ?? $employee->faceEmbedding->embedding_data['descriptors'];

// Test similarity dengan embedding lain
$test = array_fill(0, 128, 0.5);
$similarity = $service->calculateSimilarity($stored, $test);

echo "Similarity: {$similarity}";
// Output: Similarity: 0.7234
```

### Problem 4: Cek Verification Logs

```php
// Di tinker atau database query
use App\Models\FaceVerificationLog;

// 10 verifikasi terakhir untuk employee tertentu
$logs = FaceVerificationLog::where('employee_id', 1)
    ->latest('created_at')
    ->take(10)
    ->get(['verification_type', 'is_successful', 'confidence_score', 'failure_reason', 'created_at']);

foreach ($logs as $log) {
    echo "{$log->created_at} | {$log->verification_type} | " .
         ($log->is_successful ? '✅' : '❌') . " | " .
         "confidence: {$log->confidence_score} | " .
         ($log->failure_reason ?? '-') . "\n";
}
```

### Useful SQL Queries

```sql
-- Cek enrollment status semua karyawan
SELECT e.full_name, e.employee_id,
       CASE WHEN fe.id IS NOT NULL THEN 'Terdaftar' ELSE 'Belum' END as face_status,
       fe.quality_score, fe.enrolled_at
FROM employees e
LEFT JOIN employee_face_embeddings fe ON fe.employee_id = e.id AND fe.is_active = 1 AND fe.deleted_at IS NULL
WHERE e.company_id = 1
ORDER BY face_status DESC, e.full_name;

-- Statistik verifikasi bulan ini
SELECT
    verification_type,
    COUNT(*) as total,
    SUM(is_successful) as success,
    SUM(NOT is_successful) as failed,
    ROUND(AVG(confidence_score), 4) as avg_confidence
FROM face_verification_logs
WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
GROUP BY verification_type;

-- Top 5 karyawan dengan verifikasi gagal terbanyak
SELECT e.full_name, COUNT(*) as failed_count,
       AVG(fvl.confidence_score) as avg_confidence
FROM face_verification_logs fvl
JOIN employees e ON e.id = fvl.employee_id
WHERE fvl.is_successful = 0
  AND fvl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY fvl.employee_id, e.full_name
ORDER BY failed_count DESC
LIMIT 5;
```

---

## 16. Latihan Praktik

### Latihan 1: Explore Database Schema (15 menit)

Jalankan query berikut dan pahami hasilnya:

```sql
-- 1. Lihat struktur tabel face_embeddings
DESCRIBE employee_face_embeddings;

-- 2. Lihat struktur tabel verification logs
DESCRIBE face_verification_logs;

-- 3. Cek company settings face recognition
SELECT id, name, enable_face_recognition, face_match_threshold,
       require_liveness_detection
FROM companies;

-- 4. Cek berapa karyawan yang sudah enroll
SELECT COUNT(*) as total_enrolled
FROM employee_face_embeddings
WHERE is_active = 1 AND deleted_at IS NULL;
```

### Latihan 2: Test API Endpoints via Postman/cURL (30 menit)

```bash
# 1. Login untuk dapat token
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"budi@gemilang.co.id","password":"password123"}' \
  | jq -r '.data.token')

echo "Token: $TOKEN"

# 2. Cek status enrollment
curl -s http://localhost:8000/api/v1/face-recognition/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .

# 3. Coba enroll (dengan dummy embedding)
curl -s -X POST http://localhost:8000/api/v1/face-recognition/enroll \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "embedding_data": {
      "version": "1.0",
      "model": "test",
      "embedding": '$(python3 -c "import json; print(json.dumps([0.5]*128))")'
    }
  }' | jq .

# 4. Cek status lagi (harus enrolled: true)
curl -s http://localhost:8000/api/v1/face-recognition/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .

# 5. Verify dengan embedding yang sama (harus match)
curl -s -X POST http://localhost:8000/api/v1/face-recognition/verify \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "descriptors": '$(python3 -c "import json; print(json.dumps([0.5]*128))")',
    "verification_type": "clock_in"
  }' | jq .

# 6. Verify dengan embedding berbeda (harus not match)
curl -s -X POST http://localhost:8000/api/v1/face-recognition/verify \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "descriptors": '$(python3 -c "import json; print(json.dumps([-0.5]*128))")',
    "verification_type": "clock_in"
  }' | jq .

# 7. Hapus enrollment
curl -s -X DELETE http://localhost:8000/api/v1/face-recognition/enrollment \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .
```

### Latihan 3: Jalankan Automated Tests (15 menit)

```bash
# 1. Jalankan semua face recognition tests
php artisan test --compact --filter="FaceRecognition"

# 2. Jalankan API test saja
php artisan test --compact tests/Feature/Api/FaceRecognitionApiTest.php

# 3. Jalankan service test saja
php artisan test --compact tests/Feature/FaceRecognition/FaceRecognitionServiceTest.php

# 4. Lihat hasilnya — semua harus PASS ✅
```

### Latihan 4: Web Admin Face Enrollment (20 menit)

1. Login ke web admin sebagai Admin
2. Navigasi ke menu **Face Recognition** (atau **Pendaftaran Wajah**)
3. Lihat daftar karyawan dengan status enrollment
4. Klik **Daftarkan** pada karyawan yang belum terdaftar
5. Upload foto atau gunakan kamera
6. Verifikasi bahwa status berubah menjadi "Terdaftar"
7. Coba **Reset** wajah karyawan
8. Verifikasi di database bahwa data ter-soft-delete

### Latihan 5: Ubah Threshold & Test (15 menit)

1. Buka **Settings > Attendance**
2. Ubah **Face Match Threshold** ke 95% (0.95)
3. Coba verifikasi via API — apakah lebih sering gagal?
4. Ubah kembali ke 70% (0.70)
5. Coba verifikasi lagi — apakah berhasil?
6. **Pertanyaan**: Berapa threshold ideal untuk perusahaan Anda?

### Latihan 6: Baca & Pahami Kode (30 menit)

Buka dan baca file-file berikut. Tulis catatan tentang apa yang Anda pahami:

```
1. app/Services/FaceRecognitionService.php
   → Pahami setiap method dan flow-nya

2. app/Http/Controllers/Api/V1/FaceRecognitionController.php
   → Pahami OpenAPI annotations dan validation rules

3. app/Http/Controllers/Api/V1/AttendanceController.php
   → Cari bagian face verification di clock-in/clock-out

4. app/Models/EmployeeFaceEmbedding.php
   → Pahami relasi, casts, dan scope

5. tests/Feature/FaceRecognition/FaceRecognitionServiceTest.php
   → Pahami test structure dan assertions
```

### Latihan 7: Cosine Similarity Hands-On (20 menit)

Gunakan tinker untuk eksperimen:

```bash
php artisan tinker
```

```php
$service = new \App\Services\FaceRecognitionService();

// Test 1: Embedding identik → harus ~1.0
$a = array_fill(0, 128, 0.5);
$b = array_fill(0, 128, 0.5);
echo "Identik: " . $service->calculateSimilarity($a, $b) . "\n";

// Test 2: Sedikit berbeda → harus > 0.9
$c = array_fill(0, 128, 0.5);
$c[0] = 0.6; $c[1] = 0.4; $c[2] = 0.55;
echo "Mirip: " . $service->calculateSimilarity($a, $c) . "\n";

// Test 3: Sangat berbeda → harus < 0.5
$d = array_map(fn() => rand(-100, 100) / 100, range(1, 128));
echo "Random: " . $service->calculateSimilarity($a, $d) . "\n";

// Test 4: Berlawanan → harus -1.0
$e = array_fill(0, 128, -0.5);
echo "Berlawanan: " . $service->calculateSimilarity($a, $e) . "\n";
```

**Pertanyaan untuk diskusi:**
1. Mengapa GajiPro menggunakan cosine similarity, bukan Euclidean distance?
2. Apa keuntungan client-side verification dibanding server-side?
3. Bagaimana jika karyawan mengubah penampilan drastis (potong rambut, pakai kacamata)?
4. Apa implikasi hukum menyimpan data biometrik di Indonesia (UU PDP)?
5. Bagaimana cara handle karyawan yang tidak bisa didaftarkan (wajah sulit dikenali)?

---

## Ringkasan

| Komponen | File/Lokasi | Fungsi |
|----------|------------|--------|
| Service | `app/Services/FaceRecognitionService.php` | Business logic (enroll, verify, similarity) |
| Web Controller | `app/Http/Controllers/FaceRecognitionController.php` | Admin face management |
| API Controller | `app/Http/Controllers/Api/V1/FaceRecognitionController.php` | Mobile API endpoints |
| Model | `app/Models/EmployeeFaceEmbedding.php` | Face embedding data |
| Model | `app/Models/FaceVerificationLog.php` | Audit trail |
| View | `resources/views/face-recognition/` | Web admin UI |
| Settings | `resources/views/settings/attendance/` | Configuration UI |
| Tests | `tests/Feature/FaceRecognition/` | Automated tests |
| Tests | `tests/Feature/Api/FaceRecognitionApiTest.php` | API tests |

### Key Takeaways

1. **Face embedding** = representasi numerik wajah (128/192 float values)
2. **Cosine similarity** = cara membandingkan dua embedding (0-1, higher = more similar)
3. **Threshold** = batas minimum similarity untuk dianggap cocok (default 0.60, rekomendasi 0.70-0.80)
4. **Dua mode**: Client-side (Flutter matching lokal) vs Server-side (backend matching)
5. **Security**: Tenant isolation, file isolation, audit trail, UU PDP compliance
6. **Testing**: Service unit tests, API integration tests, tenant isolation tests
