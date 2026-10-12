# JagoGaji - Landing Page, Auth & Pricing Specification

## 1. LANDING PAGE

### 1.1 Struktur Sections

```
┌─────────────────────────────────────────────────────────────┐
│  NAVBAR (Sticky)                                            │
│  Logo | Fitur | Harga | Tentang | Kontak | [Login] [Daftar] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  HERO SECTION                                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  "Kelola Gaji & HR                                   │   │
│  │   Jadi Lebih Mudah"                                  │   │
│  │                                                       │   │
│  │  Solusi lengkap penggajian, kehadiran, dan          │   │
│  │  manajemen karyawan untuk bisnis Indonesia.          │   │
│  │                                                       │   │
│  │  [Coba Gratis 14 Hari]  [Lihat Demo]                │   │
│  │                                                       │   │
│  │  ✓ Tanpa kartu kredit  ✓ Setup 5 menit              │   │
│  └─────────────────────────────────────────────────────┘   │
│                        [Dashboard Preview Image]            │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  TRUSTED BY (Logo Companies)                                │
│  "Dipercaya 500+ perusahaan di Indonesia"                  │
│  [Logo1] [Logo2] [Logo3] [Logo4] [Logo5]                   │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  FEATURES SECTION                                           │
│  "Semua yang Anda Butuhkan dalam Satu Platform"            │
│                                                             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐       │
│  │ 👥       │ │ ⏰       │ │ 💰       │ │ 📊       │       │
│  │Karyawan  │ │Kehadiran │ │ Payroll  │ │ Laporan  │       │
│  │Kelola    │ │GPS &     │ │Gaji, PPh │ │Real-time │       │
│  │data      │ │Selfie    │ │BPJS auto │ │analytics │       │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘       │
│                                                             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐       │
│  │ 📅       │ │ 📱       │ │ 🔐       │ │ 🏢       │       │
│  │Cuti &    │ │Mobile    │ │Multi     │ │Multi     │       │
│  │Izin      │ │App       │ │Role      │ │Company   │       │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘       │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  HOW IT WORKS                                               │
│  "Mulai dalam 3 Langkah Mudah"                             │
│                                                             │
│  ① Daftar Akun ──→ ② Setup Perusahaan ──→ ③ Undang Tim    │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  FEATURES DETAIL (Alternating Left-Right)                   │
│                                                             │
│  [Image]     │  Kehadiran Realtime                         │
│              │  • Clock in/out dengan GPS                   │
│              │  • Validasi lokasi & selfie                  │
│              │  • Riwayat kehadiran lengkap                 │
│              │                                              │
│  ─────────────────────────────────────────────────────────  │
│              │                                              │
│  Payroll     │     [Image]                                 │
│  Otomatis    │                                              │
│  • Hitung gaji otomatis                                    │
│  • PPh 21 & BPJS terintegrasi                              │
│  • Slip gaji digital                                        │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  PRICING PREVIEW                                            │
│  "Harga Transparan, Tanpa Biaya Tersembunyi"               │
│                                                             │
│  ┌─────────┐  ┌─────────────┐  ┌─────────┐                 │
│  │ Starter │  │ Professional│  │Enterprise│                │
│  │         │  │  POPULAR    │  │          │                │
│  │ Rp 0    │  │ Rp 15.000   │  │ Custom   │                │
│  │ /bulan  │  │ /user/bulan │  │          │                │
│  │         │  │             │  │          │                │
│  │ 5 user  │  │ Unlimited   │  │ Unlimited│                │
│  │ Basic   │  │ Full Fitur  │  │ + Support│                │
│  │         │  │             │  │ Priority │                │
│  │[Mulai]  │  │ [Coba 14hr] │  │[Hubungi] │                │
│  └─────────┘  └─────────────┘  └─────────┘                 │
│                                                             │
│  [Lihat Perbandingan Lengkap →]                            │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  TESTIMONIALS                                               │
│  "Apa Kata Mereka?"                                        │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ "JagoGaji menghemat waktu HR kami 10 jam/minggu.    │  │
│  │  Payroll yang dulu ribet sekarang 1 klik saja."     │  │
│  │                                                      │  │
│  │  - Budi Santoso, HR Manager PT ABC                  │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  FAQ SECTION                                                │
│  "Pertanyaan yang Sering Diajukan"                         │
│                                                             │
│  ▼ Apakah data saya aman?                                  │
│  ▼ Bagaimana cara migrasi dari sistem lama?                │
│  ▼ Apakah bisa integrasi dengan sistem lain?               │
│  ▼ Bagaimana dukungan pelanggan?                           │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  CTA SECTION                                                │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  "Siap Tingkatkan Efisiensi HR Anda?"               │  │
│  │                                                      │  │
│  │  Mulai gratis hari ini. Tanpa kartu kredit.         │  │
│  │                                                      │  │
│  │  [Daftar Gratis]    [Jadwalkan Demo]                │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  FOOTER                                                     │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  JagoGaji          Produk        Perusahaan  Legal  │  │
│  │                    Fitur         Tentang     Privacy│  │
│  │  Solusi HR &       Harga         Blog        Terms  │  │
│  │  Payroll untuk     Integrasi     Karir       Cookie │  │
│  │  Indonesia         API Docs      Kontak             │  │
│  │                                                      │  │
│  │  📧 hello@jagogaji.com                              │  │
│  │  📱 +62 21 1234 5678                                │  │
│  │                                                      │  │
│  │  [FB] [IG] [LinkedIn] [Twitter]                     │  │
│  │                                                      │  │
│  │  © 2026 JagoGaji. All rights reserved.              │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Hero Section Content

**Headline Options:**
1. "Kelola Gaji & HR Jadi Lebih Mudah"
2. "Payroll & HR dalam Satu Platform"
3. "Solusi Penggajian Cerdas untuk Indonesia"

**Subheadline:**
"Platform lengkap untuk penggajian, kehadiran, cuti, dan manajemen karyawan. Otomatisasi PPh 21 & BPJS. Mulai gratis!"

**CTA Buttons:**
- Primary: "Coba Gratis 14 Hari" → Register page
- Secondary: "Lihat Demo" → Demo video modal atau demo page

**Trust Badges:**
- ✓ Tanpa kartu kredit
- ✓ Setup 5 menit
- ✓ Batalkan kapan saja

### 1.3 Features List

| Icon | Title | Description |
|------|-------|-------------|
| 👥 | Manajemen Karyawan | Data lengkap karyawan, dokumen, kontrak dalam satu tempat |
| ⏰ | Kehadiran GPS | Clock in/out dengan GPS & selfie, validasi lokasi otomatis |
| 💰 | Payroll Otomatis | Hitung gaji, PPh 21, BPJS, THR otomatis dalam hitungan menit |
| 📊 | Laporan Real-time | Dashboard analytics, export PDF/Excel, insights bisnis |
| 📅 | Cuti & Izin | Pengajuan online, approval workflow, tracking sisa cuti |
| 📱 | Mobile App | Aplikasi Flutter untuk karyawan (Android & iOS) |
| 🔐 | Multi-Role | Admin, HR, Manager, Finance dengan akses terkontrol |
| 🏢 | Multi-Company | Kelola banyak perusahaan dalam satu dashboard (SaaS) |

### 1.4 Design Specifications

```css
/* Hero Section */
.hero {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    /* atau dengan pattern/illustration */
    min-height: 90vh;
}

/* Section Spacing */
.section {
    padding: 80px 0; /* Desktop */
    padding: 48px 0; /* Mobile */
}

/* Feature Cards */
.feature-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}
```

---

## 2. AUTHENTICATION PAGES

### 2.1 Register Page

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  ┌─────────────────────┬───────────────────────────────┐   │
│  │                     │                               │   │
│  │   [JagoGaji Logo]   │      Buat Akun Baru          │   │
│  │                     │                               │   │
│  │   "Kelola HR &      │   ┌─────────────────────────┐│   │
│  │    Payroll dengan   │   │ Nama Lengkap            ││   │
│  │    Mudah"           │   │ [____________________]  ││   │
│  │                     │   └─────────────────────────┘│   │
│  │   ✓ Gratis 14 hari  │                               │   │
│  │   ✓ Tanpa kartu     │   ┌─────────────────────────┐│   │
│  │     kredit          │   │ Email                   ││   │
│  │   ✓ Full akses      │   │ [____________________]  ││   │
│  │                     │   └─────────────────────────┘│   │
│  │                     │                               │   │
│  │   [Dashboard        │   ┌─────────────────────────┐│   │
│  │    Preview          │   │ Nama Perusahaan         ││   │
│  │    Image]           │   │ [____________________]  ││   │
│  │                     │   └─────────────────────────┘│   │
│  │                     │                               │   │
│  │                     │   ┌─────────────────────────┐│   │
│  │                     │   │ No. Telepon             ││   │
│  │                     │   │ [+62] [______________]  ││   │
│  │                     │   └─────────────────────────┘│   │
│  │                     │                               │   │
│  │                     │   ┌─────────────────────────┐│   │
│  │                     │   │ Password                ││   │
│  │                     │   │ [____________________]👁││   │
│  │                     │   └─────────────────────────┘│   │
│  │                     │                               │   │
│  │                     │   ┌─────────────────────────┐│   │
│  │                     │   │ Konfirmasi Password     ││   │
│  │                     │   │ [____________________]👁││   │
│  │                     │   └─────────────────────────┘│   │
│  │                     │                               │   │
│  │                     │   ☐ Saya setuju dengan       │   │
│  │                     │     Syarat & Ketentuan dan   │   │
│  │                     │     Kebijakan Privasi        │   │
│  │                     │                               │   │
│  │                     │   [    Daftar Sekarang    ]  │   │
│  │                     │                               │   │
│  │                     │   ─────── atau ───────       │   │
│  │                     │                               │   │
│  │                     │   [G] Daftar dengan Google   │   │
│  │                     │                               │   │
│  │                     │   Sudah punya akun?          │   │
│  │                     │   [Masuk di sini]            │   │
│  │                     │                               │   │
│  └─────────────────────┴───────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Register Fields:**
| Field | Type | Validation |
|-------|------|------------|
| Nama Lengkap | text | required, min:3 |
| Email | email | required, unique |
| Nama Perusahaan | text | required, min:2 |
| No. Telepon | tel | required, regex:indonesia |
| Password | password | required, min:8, confirmed |
| Confirm Password | password | required, same:password |
| Terms Agreement | checkbox | required |

### 2.2 Login Page

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  ┌─────────────────────┬───────────────────────────────┐   │
│  │                     │                               │   │
│  │   [JagoGaji Logo]   │      Masuk ke Akun Anda      │   │
│  │                     │                               │   │
│  │   "Selamat Datang   │   ┌─────────────────────────┐│   │
│  │    Kembali!"        │   │ Email                   ││   │
│  │                     │   │ [____________________]  ││   │
│  │   [Illustration]    │   └─────────────────────────┘│   │
│  │                     │                               │   │
│  │                     │   ┌─────────────────────────┐│   │
│  │                     │   │ Password                ││   │
│  │                     │   │ [____________________]👁││   │
│  │                     │   └─────────────────────────┘│   │
│  │                     │                               │   │
│  │                     │   ☐ Ingat saya               │   │
│  │                     │              [Lupa Password?]│   │
│  │                     │                               │   │
│  │                     │   [        Masuk          ]  │   │
│  │                     │                               │   │
│  │                     │   ─────── atau ───────       │   │
│  │                     │                               │   │
│  │                     │   [G] Masuk dengan Google    │   │
│  │                     │                               │   │
│  │                     │   Belum punya akun?          │   │
│  │                     │   [Daftar gratis]            │   │
│  │                     │                               │   │
│  └─────────────────────┴───────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 2.3 Forgot Password Flow

```
1. Forgot Password Page
   - Input email
   - "Kirim Link Reset"

2. Email Sent Confirmation
   - "Link reset telah dikirim ke email Anda"
   - "Cek folder spam jika tidak menemukan"

3. Reset Password Page (via email link)
   - New Password
   - Confirm New Password
   - "Reset Password"

4. Success
   - "Password berhasil direset"
   - "Masuk dengan password baru"
```

### 2.4 Auth Design Specifications

```css
/* Auth Container */
.auth-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
}

/* Left Panel - Branding */
.auth-branding {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 48px;
}

/* Right Panel - Form */
.auth-form {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px;
    background: white;
}

/* Form Card */
.auth-card {
    width: 100%;
    max-width: 420px;
}

/* Mobile: Stack vertically */
@media (max-width: 768px) {
    .auth-container {
        grid-template-columns: 1fr;
    }
    .auth-branding {
        display: none; /* or show smaller version */
    }
}
```

---

## 3. PRICING PAGE

### 3.1 Pricing Structure

```
┌─────────────────────────────────────────────────────────────┐
│  NAVBAR                                                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  HEADER                                                     │
│  "Pilih Paket yang Sesuai untuk Bisnis Anda"               │
│  "Mulai gratis, upgrade kapan saja"                        │
│                                                             │
│  [ Bulanan ]  [ Tahunan (Hemat 20%) ]   ← Toggle            │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  PRICING CARDS                                              │
│                                                             │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐     │
│  │   STARTER     │ │  PROFESSIONAL │ │  ENTERPRISE   │     │
│  │               │ │   ⭐ POPULER  │ │               │     │
│  │   Gratis      │ │               │ │   Custom      │     │
│  │               │ │  Rp 15.000    │ │               │     │
│  │   Rp 0       │ │  /user/bulan  │ │   Hubungi     │     │
│  │   selamanya   │ │               │ │   Sales       │     │
│  │               │ │  Rp 12.000    │ │               │     │
│  │               │ │  /user/bulan  │ │               │     │
│  │               │ │  (tahunan)    │ │               │     │
│  │───────────────│ │───────────────│ │───────────────│     │
│  │               │ │               │ │               │     │
│  │ ✓ 5 karyawan │ │ ✓ Unlimited   │ │ ✓ Unlimited   │     │
│  │ ✓ Kehadiran  │ │   karyawan    │ │   karyawan    │     │
│  │ ✓ Cuti basic │ │ ✓ Semua fitur │ │ ✓ Semua fitur │     │
│  │ ✓ Laporan    │ │   Starter     │ │   Professional│     │
│  │   basic      │ │ ✓ Payroll     │ │ ✓ Dedicated   │     │
│  │              │ │   lengkap     │ │   support     │     │
│  │ ✗ Payroll    │ │ ✓ PPh 21 &    │ │ ✓ SLA 99.9%   │     │
│  │ ✗ PPh 21     │ │   BPJS        │ │ ✓ Custom      │     │
│  │ ✗ BPJS       │ │ ✓ Multi-role  │ │   integration │     │
│  │ ✗ Multi-role │ │ ✓ Export      │ │ ✓ On-premise  │     │
│  │              │ │   unlimited   │ │   option      │     │
│  │              │ │ ✓ API access  │ │ ✓ Training    │     │
│  │              │ │ ✓ Email       │ │   included    │     │
│  │              │ │   support     │ │               │     │
│  │              │ │               │ │               │     │
│  │ [Mulai      │ │ [Coba 14 Hari│ │ [Hubungi     │     │
│  │  Gratis]    │ │  Gratis]      │ │  Sales]       │     │
│  │              │ │               │ │               │     │
│  └───────────────┘ └───────────────┘ └───────────────┘     │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  FEATURE COMPARISON TABLE                                   │
│  "Perbandingan Fitur Lengkap"                              │
│                                                             │
│  ┌─────────────────────┬─────────┬────────────┬──────────┐ │
│  │ Fitur               │ Starter │Professional│Enterprise│ │
│  ├─────────────────────┼─────────┼────────────┼──────────┤ │
│  │ KARYAWAN            │         │            │          │ │
│  │ Jumlah karyawan     │ 5       │ Unlimited  │ Unlimited│ │
│  │ Data karyawan       │ ✓       │ ✓          │ ✓        │ │
│  │ Dokumen karyawan    │ ✗       │ ✓          │ ✓        │ │
│  │ Import/Export       │ ✗       │ ✓          │ ✓        │ │
│  ├─────────────────────┼─────────┼────────────┼──────────┤ │
│  │ KEHADIRAN           │         │            │          │ │
│  │ Clock in/out GPS    │ ✓       │ ✓          │ ✓        │ │
│  │ Selfie verification │ ✓       │ ✓          │ ✓        │ │
│  │ Multi lokasi        │ ✗       │ ✓          │ ✓        │ │
│  │ Shift management    │ ✗       │ ✓          │ ✓        │ │
│  ├─────────────────────┼─────────┼────────────┼──────────┤ │
│  │ CUTI & IZIN         │         │            │          │ │
│  │ Pengajuan cuti      │ ✓       │ ✓          │ ✓        │ │
│  │ Custom leave types  │ ✗       │ ✓          │ ✓        │ │
│  │ Approval workflow   │ 1 level │ Multi-level│ Custom   │ │
│  ├─────────────────────┼─────────┼────────────┼──────────┤ │
│  │ PAYROLL             │         │            │          │ │
│  │ Slip gaji           │ ✗       │ ✓          │ ✓        │ │
│  │ PPh 21 otomatis     │ ✗       │ ✓          │ ✓        │ │
│  │ BPJS otomatis       │ ✗       │ ✓          │ ✓        │ │
│  │ THR calculation     │ ✗       │ ✓          │ ✓        │ │
│  │ Bank transfer       │ ✗       │ ✗          │ ✓        │ │
│  ├─────────────────────┼─────────┼────────────┼──────────┤ │
│  │ LAPORAN             │         │            │          │ │
│  │ Dashboard           │ Basic   │ Advanced   │ Custom   │ │
│  │ Export PDF          │ ✓       │ ✓          │ ✓        │ │
│  │ Export Excel        │ ✗       │ ✓          │ ✓        │ │
│  │ Custom reports      │ ✗       │ ✗          │ ✓        │ │
│  ├─────────────────────┼─────────┼────────────┼──────────┤ │
│  │ SUPPORT             │         │            │          │ │
│  │ Email support       │ ✓       │ ✓          │ ✓        │ │
│  │ Chat support        │ ✗       │ ✓          │ ✓        │ │
│  │ Phone support       │ ✗       │ ✗          │ ✓        │ │
│  │ Dedicated manager   │ ✗       │ ✗          │ ✓        │ │
│  │ SLA                 │ -       │ 99.5%      │ 99.9%    │ │
│  └─────────────────────┴─────────┴────────────┴──────────┘ │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  FAQ PRICING                                                │
│                                                             │
│  ▼ Bagaimana cara upgrade paket?                           │
│  ▼ Apakah ada biaya setup?                                 │
│  ▼ Bagaimana jika karyawan saya bertambah?                 │
│  ▼ Apakah bisa downgrade?                                  │
│  ▼ Metode pembayaran apa saja yang tersedia?               │
│  ▼ Apakah ada diskon untuk NGO/startup?                    │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  CTA                                                        │
│  "Masih ragu? Jadwalkan demo gratis dengan tim kami"       │
│  [Jadwalkan Demo]                                          │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  FOOTER                                                     │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Pricing Plans Detail

#### Plan: STARTER (Free)
```
Harga: Rp 0 / bulan (selamanya gratis)
Target: Startup kecil, UMKM, freelancer

Batasan:
- Max 5 karyawan
- 1 admin only
- Basic features

Fitur Include:
✓ Data karyawan (5 max)
✓ Kehadiran GPS + Selfie
✓ Cuti & Izin (basic)
✓ Laporan kehadiran (basic)
✓ Mobile app akses
✓ Email support

Fitur Exclude:
✗ Payroll
✗ PPh 21 & BPJS
✗ Multi-role
✗ Export Excel
✗ API access
✗ Custom branding
```

#### Plan: PROFESSIONAL (Recommended)
```
Harga:
- Bulanan: Rp 15.000 / user / bulan
- Tahunan: Rp 12.000 / user / bulan (hemat 20%)

Minimum: 10 users
Target: SME, perusahaan menengah

Fitur Include:
✓ Semua fitur Starter
✓ Unlimited karyawan
✓ Payroll lengkap
✓ PPh 21 otomatis
✓ BPJS Kesehatan & TK
✓ THR calculation
✓ Multi-role (Admin, HR, Manager, Finance)
✓ Approval workflow (multi-level)
✓ Shift management
✓ Multi lokasi kantor
✓ Custom leave types
✓ Export PDF & Excel
✓ API access
✓ Email & Chat support
✓ 99.5% SLA
```

#### Plan: ENTERPRISE (Custom)
```
Harga: Custom (contact sales)
Minimum: 100 users
Target: Enterprise, korporat besar

Fitur Include:
✓ Semua fitur Professional
✓ Dedicated account manager
✓ Custom integrations
✓ SSO (Single Sign-On)
✓ On-premise option
✓ Custom reports
✓ Bank transfer integration
✓ White-label option
✓ Training & onboarding
✓ Phone support priority
✓ 99.9% SLA
✓ Custom contract terms
```

### 3.3 Add-ons (Optional)

| Add-on | Harga | Deskripsi |
|--------|-------|-----------|
| Extra Storage | Rp 50.000/bulan/10GB | Penyimpanan dokumen tambahan |
| White Label | Rp 500.000/bulan | Custom branding & domain |
| API Premium | Rp 200.000/bulan | Higher rate limits, webhooks |
| Training Session | Rp 1.500.000/session | 2 jam training online |

---

## 4. MOBILE RESPONSIVE

### 4.1 Landing Page Mobile
- Hero: Stack vertically, smaller image
- Features: 2 columns → 1 column
- Pricing: Horizontal scroll or stack
- Navigation: Hamburger menu

### 4.2 Auth Pages Mobile
- Full width form
- Hide left branding panel
- Show small logo on top

### 4.3 Pricing Mobile
- Stack pricing cards vertically
- Comparison table: horizontal scroll
- Sticky "Choose Plan" button

---

## 5. SEO & META

### 5.1 Landing Page
```html
<title>JagoGaji - Payroll & HR Software Indonesia | Kelola Gaji Jadi Mudah</title>
<meta name="description" content="Software payroll dan HR terlengkap untuk Indonesia. Hitung gaji, PPh 21, BPJS otomatis. Gratis 14 hari. Mulai sekarang!">
<meta name="keywords" content="payroll indonesia, software hr, aplikasi gaji, hitung pph 21, bpjs, absensi online">
```

### 5.2 Pricing Page
```html
<title>Harga JagoGaji - Mulai Gratis | Paket Payroll & HR</title>
<meta name="description" content="Pilih paket JagoGaji sesuai kebutuhan. Mulai gratis untuk 5 karyawan. Paket Professional mulai Rp 12.000/user/bulan.">
```

### 5.3 Register Page
```html
<title>Daftar JagoGaji - Coba Gratis 14 Hari</title>
<meta name="description" content="Daftar JagoGaji gratis. Tanpa kartu kredit. Setup 5 menit. Mulai kelola payroll dan HR dengan mudah.">
```

---

## 6. IMPLEMENTATION CHECKLIST

### Landing Page
- [ ] Navbar with sticky scroll
- [ ] Hero section with CTA
- [ ] Trusted by logos
- [ ] Features grid
- [ ] How it works steps
- [ ] Feature details (alternating)
- [ ] Pricing preview
- [ ] Testimonials slider
- [ ] FAQ accordion
- [ ] CTA section
- [ ] Footer

### Auth Pages
- [ ] Register form with validation
- [ ] Login form
- [ ] Forgot password flow
- [ ] Google OAuth (optional)
- [ ] Email verification
- [ ] Terms & Privacy links

### Pricing Page
- [ ] Monthly/Yearly toggle
- [ ] 3 pricing cards
- [ ] Feature comparison table
- [ ] FAQ section
- [ ] Contact sales form

---

*Document Version: 1.0*
*Created: 2026-02-11*
